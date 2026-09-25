<?php
// ========================================
// BLOODLINE MEDIA — PANEL (Design-Export integriert)
// Galerie · Anträge · Whitelist
// ========================================
require_once 'config.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$noAccess = !canAccess();

$user = getUser();
$avatarUrl = getUserAvatar();
$meName = bl_norm_name($user['name'] ?? 'Admin');
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// --- Aktueller Ordner (relativ) ---
$rel = $_GET['path'] ?? '';
$rel = str_replace(['..', '\\'], '', $rel);
$rel = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', trim($rel, '/'));
$curDir = __DIR__ . ($rel !== '' ? '/' . $rel : '');

$imageExts = ['jpg','jpeg','png','gif','webp','svg','bmp'];
$skipRoot  = ['index.php','panel.php','api.php','config.php','auth.php','login.php','callback.php','logout.php','media.php','phone_upload.php','.htaccess','.user.ini','debug.log','whitelist.json','settings.json','antraege.json','dc_names.json','keys.json','css','js','api'];

$folders = [];
$images  = [];
if (!$noAccess && is_dir($curDir)) {
    foreach (@scandir($curDir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        if ($rel === '' && in_array($item, $skipRoot, true)) continue;
        if ($item[0] === '.') continue;
        $full = $curDir . '/' . $item;
        if (is_dir($full)) {
            $cnt = 0;
            foreach (@scandir($full) ?: [] as $s) {
                if (in_array(strtolower(pathinfo($s, PATHINFO_EXTENSION)), $imageExts, true)) $cnt++;
            }
            $folders[] = ['name' => $item, 'count' => $cnt];
        } elseif (in_array(strtolower(pathinfo($item, PATHINFO_EXTENSION)), $imageExts, true)) {
            $images[] = [
                'name'  => $item,
                'ext'   => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                'bytes' => filesize($full),
                'mtime' => filemtime($full),
            ];
        }
    }
    usort($folders, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    usort($images, fn($a, $b) => strcasecmp($a['name'], $b['name']));
}

// Öffentliche URL einer Datei im aktuellen Ordner
function bl_pub_url($rel, $name) {
    return MEDIA_PUBLIC_BASE . '/' . ($rel !== '' ? $rel . '/' : '') . rawurlencode($name);
}
function bl_human($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024) . ' KB';
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}
function bl_ago($ts) {
    $d = time() - (int)$ts;
    if ($d < 60) return 'gerade eben';
    if ($d < 3600) return 'vor ' . floor($d / 60) . ' Min';
    if ($d < 86400) return 'vor ' . floor($d / 3600) . ' Std';
    if ($d < 172800) return 'gestern';
    return 'vor ' . floor($d / 86400) . ' Tagen';
}
function bl_initial($s) { $s = trim((string)$s); return $s === '' ? '?' : mb_strtoupper(mb_substr($s, 0, 1)); }

// 3D-Ordner-Icon (SVG, referenziert geteilte Gradienten blfb/blff)
function bl_folder_svg() {
    return '<svg class="bl-fico" viewBox="0 0 64 52" aria-hidden="true">'
        . '<path d="M6 12a4 4 0 0 1 4-4h12l5 5h23a4 4 0 0 1 4 4v21a4 4 0 0 1-4 4H10a4 4 0 0 1-4-4z" fill="url(#blfb)"/>'
        . '<path d="M3 23h55a3 3 0 0 1 2.9 3.8l-4.6 17A4 4 0 0 1 52.4 47H12.1a4 4 0 0 1-3.9-3.1L3.1 26.9A3 3 0 0 1 3 23z" fill="url(#blff)"/>'
        . '<path d="M9 27h44" stroke="#fff" stroke-opacity="0.18" stroke-width="2" stroke-linecap="round"/>'
        . '</svg>';
}

// --- Anträge ---
$reqCounts = ['open' => 0, 'approved' => 0, 'rejected' => 0];
$requests  = [];
if (canManageAntraege()) {
    $all = loadAntraege();
    usort($all, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
    $pendingUrl = getAntragPendingUrl();
    $defaultTarget = getAntragTargetFolders()[0];
    foreach ($all as $a) {
        $st = ($a['status'] ?? 'pending');
        $status = $st === 'pending' ? 'open' : $st;
        if (isset($reqCounts[$status])) $reqCounts[$status]++;
        $img = null;
        if ($status === 'open' && !empty($a['pending_file'])) $img = $pendingUrl . $a['pending_file'];
        elseif ($status === 'approved' && !empty($a['final_url'])) $img = $a['final_url'];
        elseif (!empty($a['pending_file'])) $img = $pendingUrl . $a['pending_file'];
        $requests[] = [
            'id'      => $a['id'] ?? '',
            'status'  => $status,
            'file'    => ($a['wunschname'] ?? 'bild') . '.' . ($a['ext'] ?? 'png'),
            'folder'  => $status === 'approved' ? ($a['target_folder'] ?? $defaultTarget) : $defaultTarget,
            'img'     => $img,
            'sname'   => bl_norm_name($a['server_name'] ?? ($a['discord_name'] ?? 'Gast')),
            'discord' => trim(bl_norm_name($a['discord_name'] ?? '') . ' · ' . ($a['discord_id'] ?? ''), ' ·'),
            'note'    => $a['grund'] ?? '',
            'time'    => bl_ago($a['created_at'] ?? time()),
            'by'      => $a['reviewed_by'] ?? '',
        ];
    }
}
$pendingCount = $reqCounts['open'];

// --- Einstellungen (Rollen / User / Zielordner) ---
$setRoles = []; $setUsers = []; $setNames = []; $setAntrag = ['pending_folder' => '', 'target_folders' => []];
if (canManageUsers()) {
    $s = loadSettings();
    $setRoles  = $s['roles'] ?? [];
    $setUsers  = $s['users'] ?? [];
    $setAntrag = $s['antrag'] ?? $setAntrag;
    $setNames  = fetchDiscordUsernames(array_keys($setUsers));
    $setNames  = array_map('bl_norm_name', $setNames);
}

// --- Speicher ---
$diskTotal = @disk_total_space(__DIR__) ?: 0;
$diskFree  = @disk_free_space(__DIR__) ?: 0;
$diskUsed  = $diskTotal - $diskFree;
$diskPct   = $diskTotal > 0 ? min(100, round($diskUsed / $diskTotal * 100)) : 0;
$diskUsedGb = $diskTotal > 0 ? number_format($diskUsed / 1073741824, 1, ',', '.') : '0';
$diskTotGb  = $diskTotal > 0 ? number_format($diskTotal / 1073741824, 0, ',', '.') : '0';

// Breadcrumbs
$crumbs = $rel !== '' ? explode('/', $rel) : [];
$imgCount = count($images);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Panel · Bloodline Media Gallery</title>
<link rel="icon" type="image/png" href="https://bloodline.cc/img/bloodline/bl_transparent.png" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&amp;family=Rajdhani:wght@400;500;600;700&amp;family=Oxanium:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="<?php echo $basePath; ?>/css/bloodline.css?v=<?php echo @filemtime(__DIR__ . '/css/bloodline.css'); ?>" />
</head>
<body data-me="<?php echo htmlspecialchars($meName); ?>">
<?php if ($noAccess): ?>
  <div class="bl-wrap" style="max-width:520px;margin:15vh auto;text-align:center;">
    <div class="bl-eyebrow bl-eyebrow--wide" style="justify-content:center">Kein Zugriff</div>
    <h2 style="font-family:var(--bl-font-display);font-size:44px;margin:8px 0">NICHT AUF DER WHITELIST</h2>
    <p style="color:var(--bl-text-3)">Dein Discord-Account hat keinen Panel-Zugriff. Melde dich beim Team.</p>
    <p style="margin-top:20px"><a class="bl-btn" href="logout.php"><i class="fas fa-right-from-bracket"></i>Abmelden</a></p>
  </div>
<?php else: ?>

<div class="bl-app">

  <!-- SIDEBAR -->
  <aside class="bl-side">
    <a class="bl-mark bl-mark--sm" href="<?php echo $basePath; ?>/"><b>BLOOD</b><b>LINE</b><span>Media Gallery</span></a>

    <?php $view = in_array(($_GET['view'] ?? ''), ['requests','users'], true) ? $_GET['view'] : 'gallery'; ?>
    <nav class="bl-nav">
      <a href="<?php echo $basePath; ?>/panel.php" data-go="gallery" class="<?php echo $view==='gallery'?'is-active':''; ?>"><i class="fas fa-images"></i><em>Galerie</em></a>
      <?php if (canManageAntraege()): ?>
      <a href="<?php echo $basePath; ?>/panel.php?view=requests" data-go="requests" class="<?php echo $view==='requests'?'is-active':''; ?>"><i class="fas fa-shield-halved"></i><em>Anträge</em><span class="bl-badge"<?php echo $pendingCount > 0 ? '' : ' hidden'; ?>><?php echo (int)$pendingCount; ?></span></a>
      <?php endif; ?>
      <?php if (canManageUsers()): ?>
      <a href="<?php echo $basePath; ?>/panel.php?view=users" data-go="users" class="<?php echo $view==='users'?'is-active':''; ?>"><i class="fas fa-gear"></i><em>Einstellungen</em></a>
      <?php endif; ?>
    </nav>

    <div class="bl-side-foot">
      <div class="bl-quota">
        <span>Speicher</span>
        <div class="bl-bar"><i style="width:<?php echo (int)$diskPct; ?>%"></i></div>
        <b><?php echo $diskUsedGb; ?> GB von <?php echo $diskTotGb; ?> GB belegt</b>
      </div>
      <div class="bl-me">
        <div class="bl-avatar"><?php if ($avatarUrl): ?><img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="" style="width:100%;height:100%;border-radius:inherit;object-fit:cover"><?php else: echo htmlspecialchars(bl_initial($meName)); endif; ?></div>
        <div class="bl-me-body"><b><?php echo htmlspecialchars($meName); ?></b><span><?php echo isAdmin() ? 'Administrator' : 'Team'; ?></span></div>
        <a href="logout.php" title="Abmelden"><i class="fas fa-right-from-bracket"></i></a>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="bl-main">

    <header class="bl-head">
      <div class="bl-head-left">
        <div class="bl-eyebrow"><span id="bl-view-label">Dateibrowser</span></div>
        <nav class="bl-crumbs" id="bl-crumbs">
          <a href="<?php echo $basePath; ?>/panel.php"><i class="fas fa-house"></i></a>
          <?php $bp = ''; foreach ($crumbs as $c): $bp = ($bp ? $bp . '/' : '') . $c; ?>
            <span>/</span><a href="<?php echo $basePath; ?>/panel.php?path=<?php echo htmlspecialchars(rawurlencode($bp)); ?>"><?php echo htmlspecialchars($c); ?></a>
          <?php endforeach; ?>
        </nav>
      </div>
      <div class="bl-head-right" id="bl-head-tools">
        <label class="bl-search">
          <i class="fas fa-magnifying-glass"></i>
          <input id="bl-search" type="search" placeholder="Datei suchen…" />
        </label>
        <?php if (canUpload()): ?>
        <button class="bl-btn bl-btn--primary" id="bl-upload-open" type="button"><i class="fas fa-cloud-arrow-up"></i>Hochladen</button>
        <?php endif; ?>
      </div>
    </header>

    <main class="bl-body">

      <!-- ============ GALERIE ============ -->
      <section class="bl-view" data-view="gallery" id="bl-gallery">

        <div class="bl-dropnote" id="bl-dropnote" hidden>
          <i class="fas fa-cloud-arrow-up"></i>
          <b>Loslassen zum Hochladen nach <span id="bl-dropnote-target">/img</span></b>
          <span>oder auf einen Ordner ziehen</span>
        </div>

        <div class="bl-tiles">
          <div class="bl-card bl-tile"><span><i class="fas fa-folder"></i>Ordner</span><b><?php echo count($folders); ?></b></div>
          <div class="bl-card bl-tile"><span><i class="fas fa-image"></i>Bilder hier</span><b id="bl-image-count"><?php echo $imgCount; ?></b></div>
          <div class="bl-card bl-tile"><span><i class="fas fa-hourglass-half"></i>Offene Anträge</span><b><?php echo (int)$pendingCount; ?></b></div>
          <div class="bl-card bl-tile"><span><i class="fas fa-hard-drive"></i>Belegt</span><b><?php echo $diskUsedGb; ?> GB</b></div>
        </div>

        <div class="bl-selbar" id="bl-selbar" hidden>
          <div class="bl-selbar-left">
            <b id="bl-sel-count">0 ausgewählt</b>
            <span class="bl-link-quiet" id="bl-sel-clear">Auswahl aufheben</span>
          </div>
          <div class="bl-selbar-actions">
            <button class="bl-btn bl-btn--sm" id="bl-sel-copy" type="button"><i class="fas fa-copy"></i>Links kopieren</button>
            <?php if (canManageFolders()): ?><button class="bl-btn bl-btn--sm bl-btn--danger" id="bl-sel-delete" type="button"><i class="fas fa-trash"></i>Löschen</button><?php endif; ?>
          </div>
        </div>

        <!-- Ordner -->
        <div class="bl-eyebrow">Ordner <span class="bl-count"><?php echo count($folders); ?></span><span class="bl-rule"></span>
          <?php if (canManageFolders()): ?><button class="bl-btn bl-btn--xs" id="bl-folder-new" type="button"><i class="fas fa-folder-plus"></i>Neuer Ordner</button><?php endif; ?>
        </div>
        <svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
          <linearGradient id="blfb" x1="32" y1="6" x2="32" y2="46" gradientUnits="userSpaceOnUse"><stop stop-color="#b81824"/><stop offset="1" stop-color="#7d0f17"/></linearGradient>
          <linearGradient id="blff" x1="32" y1="22" x2="32" y2="50" gradientUnits="userSpaceOnUse"><stop stop-color="#ff5964"/><stop offset="1" stop-color="#d0202c"/></linearGradient>
        </defs></svg>
        <div class="bl-folders">
          <?php if (empty($folders)): ?>
            <p style="color:var(--bl-text-4);font-size:14px;padding:6px 2px">Keine Unterordner.</p>
          <?php else: foreach ($folders as $f): $fp = ($rel !== '' ? $rel . '/' : '') . $f['name']; ?>
          <a class="bl-card bl-folder" href="<?php echo $basePath; ?>/panel.php?path=<?php echo htmlspecialchars(rawurlencode($fp)); ?>" data-folder="<?php echo htmlspecialchars($fp); ?>">
            <span class="bl-folder-icon"><?php echo bl_folder_svg(); ?></span>
            <span class="bl-folder-body"><b><?php echo htmlspecialchars($f['name']); ?></b><span><?php echo (int)$f['count']; ?> Bilder</span></span>
            <?php if (canManageFolders()): ?><button class="bl-folder-del" data-folder-del type="button" title="Ordner löschen"><i class="fas fa-trash"></i></button><?php endif; ?>
            <i class="fas fa-chevron-right bl-folder-arrow"></i>
          </a>
          <?php endforeach; endif; ?>
        </div>

        <!-- Bilder -->
        <div class="bl-eyebrow" style="margin-top:32px">Bilder <span class="bl-count" id="bl-image-total"><?php echo $imgCount; ?></span><span class="bl-rule"></span>
          <div class="bl-sorts">
            <span class="bl-sort is-active" data-sort="name">Name</span>
            <span class="bl-sort" data-sort="newest">Neueste</span>
            <span class="bl-sort" data-sort="size">Größe</span>
          </div>
        </div>
        <div class="bl-grid" id="bl-grid">
          <?php if (empty($images)): ?>
            <p style="color:var(--bl-text-4);font-size:14px;padding:6px 2px">Keine Bilder in diesem Ordner.</p>
          <?php else: foreach ($images as $im): $url = bl_pub_url($rel, $im['name']); ?>
          <article class="bl-card bl-item" data-name="<?php echo htmlspecialchars($im['name']); ?>" data-url="<?php echo htmlspecialchars($url); ?>" data-date="<?php echo date('Y-m-d', $im['mtime']); ?>" data-bytes="<?php echo (int)$im['bytes']; ?>">
            <span class="bl-item-check"><i class="fas fa-check"></i></span>
            <img class="bl-item-thumb" src="<?php echo htmlspecialchars($url); ?>" alt="" loading="lazy" />
            <div class="bl-item-body">
              <div class="bl-item-name"><span class="bl-ext"><?php echo strtoupper($im['ext']); ?></span><b><?php echo htmlspecialchars($im['name']); ?></b></div>
              <div class="bl-item-meta"><?php echo bl_human($im['bytes']); ?> · <?php echo date('d.m.Y', $im['mtime']); ?></div>
              <div class="bl-item-actions">
                <button class="bl-btn bl-btn--primary bl-btn--grow" data-act="copy" type="button"><i class="fas fa-copy"></i>Link</button>
                <button class="bl-icon-btn" data-act="open" type="button"><i class="fas fa-arrow-up-right-from-square"></i></button>
                <?php if (canManageFolders()): ?>
                <button class="bl-icon-btn" data-act="rename" type="button"><i class="fas fa-pen"></i></button>
                <button class="bl-icon-btn bl-icon-btn--danger" data-act="delete" type="button"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
              </div>
            </div>
          </article>
          <?php endforeach; endif; ?>
        </div>
      </section>

      <?php if (canManageAntraege()): ?>
      <!-- ============ ANTRÄGE ============ -->
      <section class="bl-view" data-view="requests" hidden>
        <div class="bl-view-head">
          <div>
            <h2>Bild-Anträge</h2>
            <p>Freigeben landet direkt im Zielordner. Ablehnen benachrichtigt den Spieler im Discord.</p>
          </div>
          <div class="bl-tabs">
            <span class="bl-tab is-active" data-filter="open">Offen <i><?php echo $reqCounts['open']; ?></i></span>
            <span class="bl-tab" data-filter="approved">Freigegeben <i><?php echo $reqCounts['approved']; ?></i></span>
            <span class="bl-tab" data-filter="rejected">Abgelehnt <i><?php echo $reqCounts['rejected']; ?></i></span>
          </div>
        </div>

        <div class="bl-reqs">
          <?php foreach ($requests as $r):
            $pillCls = $r['status'] === 'open' ? ' bl-pill--open' : ($r['status'] === 'approved' ? ' bl-pill--ok' : '');
            $pillTxt = $r['status'] === 'open' ? 'Offen' : ($r['status'] === 'approved' ? 'Freigegeben' : 'Abgelehnt');
            $doneTxt = $r['status'] === 'approved' ? ('Freigegeben von ' . $r['by']) : ($r['status'] === 'rejected' ? ('Abgelehnt von ' . $r['by']) : '');
          ?>
          <article class="bl-card bl-req<?php echo $r['status'] === 'open' ? ' is-open' : ''; ?>" data-status="<?php echo $r['status']; ?>" data-id="<?php echo htmlspecialchars($r['id']); ?>" data-file="<?php echo htmlspecialchars($r['file']); ?>" data-folder="<?php echo htmlspecialchars($r['folder']); ?>"<?php echo $r['status'] !== 'open' ? ' hidden' : ''; ?>>
            <div class="bl-req-shot">
              <?php if ($r['img']): ?><img src="<?php echo htmlspecialchars($r['img']); ?>" alt="" loading="lazy" /><?php endif; ?>
              <span class="bl-pill<?php echo $pillCls; ?>"><?php echo $pillTxt; ?></span>
            </div>
            <div class="bl-req-body">
              <div class="bl-req-user">
                <div class="bl-avatar"><?php echo htmlspecialchars(bl_initial($r['sname'])); ?></div>
                <div class="bl-req-user-body"><b><?php echo htmlspecialchars($r['sname']); ?></b><span><?php echo htmlspecialchars($r['discord'] ?: 'ohne Login'); ?></span></div>
                <div class="bl-req-time"><?php echo htmlspecialchars($r['time']); ?></div>
              </div>
              <div class="bl-req-facts">
                <div><i class="fas fa-file-image"></i><em><?php echo htmlspecialchars($r['file']); ?></em></div>
                <div><i class="fas fa-folder"></i><?php echo htmlspecialchars(str_replace('/', ' / ', $r['folder'])); ?></div>
                <?php if ($r['note'] !== ''): ?><div class="bl-note"><i class="fas fa-quote-left"></i><?php echo htmlspecialchars($r['note']); ?></div><?php endif; ?>
              </div>
              <div class="bl-req-actions"<?php echo $r['status'] !== 'open' ? ' hidden' : ''; ?>>
                <button class="bl-btn bl-btn--sm bl-btn--ok bl-btn--grow" data-req="approve" type="button"><i class="fas fa-check"></i>Freigeben</button>
                <button class="bl-btn bl-btn--sm bl-btn--danger bl-btn--grow" data-req="reject" type="button"><i class="fas fa-xmark"></i>Ablehnen</button>
              </div>
              <div class="bl-req-done"<?php echo $r['status'] === 'open' ? ' hidden' : ''; ?>>
                <span><?php echo htmlspecialchars($doneTxt); ?></span>
                <button class="bl-btn bl-btn--sm" data-req="reopen" type="button">Zurücksetzen</button>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <div class="bl-empty" id="bl-reqs-empty"<?php echo $reqCounts['open'] > 0 ? ' hidden' : ''; ?>>
          <i class="fas fa-inbox"></i>
          <h3>Keine Anträge</h3>
          <p>In dieser Ansicht liegt gerade nichts zur Prüfung.</p>
        </div>
      </section>
      <?php endif; ?>

      <?php if (canManageUsers()): ?>
      <!-- ============ EINSTELLUNGEN ============ -->
      <section class="bl-view bl-narrow" data-view="users" hidden>
        <h2>Einstellungen</h2>
        <p>Rollen mit Rechten, Zugriff pro Discord-ID und die Zielordner der Anträge.</p>

        <!-- Zielordner -->
        <div class="bl-set-block">
          <div class="bl-eyebrow">Zielordner der Anträge</div>
          <p class="bl-set-hint">Eingereicht-Ordner = wo neue Anträge zuerst landen. Zielordner = wohin das Team bei der Freigabe verschieben darf (ein Ordner pro Zeile, relativ, z.&nbsp;B. <code>phone/ic_bilder</code>).</p>
          <label class="bl-set-label">Eingereicht-Ordner</label>
          <input class="bl-field" id="bl-set-pending" type="text" value="<?php echo htmlspecialchars($setAntrag['pending_folder'] ?? ''); ?>" placeholder="phone/eingereicht" />
          <label class="bl-set-label" style="margin-top:12px">Erlaubte Zielordner</label>
          <textarea class="bl-field" id="bl-set-targets" rows="3"><?php echo htmlspecialchars(implode("\n", $setAntrag['target_folders'] ?? [])); ?></textarea>
          <div style="margin-top:12px"><button class="bl-btn bl-btn--primary bl-btn--sm" id="bl-set-folders-save" type="button"><i class="fas fa-floppy-disk"></i>Zielordner speichern</button></div>
        </div>

        <!-- Rollen -->
        <div class="bl-set-block">
          <div class="bl-eyebrow">Rollen &amp; Rechte</div>
          <p class="bl-set-hint">Häkchen = Recht der Rolle. <b>Panel ansehen · Bilder hochladen · Ordner verwalten · Anträge freigeben · Einstellungen</b>. Rollen weist du unten den Usern zu.</p>
          <div class="bl-roles">
            <?php foreach ($setRoles as $rk => $role): ?>
            <div class="bl-card bl-role" data-role="<?php echo htmlspecialchars($rk); ?>">
              <div class="bl-role-head">
                <span class="bl-role-dot" style="background:<?php echo htmlspecialchars($role['color'] ?? '#ff3b3b'); ?>"></span>
                <b><?php echo htmlspecialchars($role['label'] ?? $rk); ?></b>
                <span class="bl-role-key">#<?php echo htmlspecialchars($rk); ?></span>
              </div>
              <div class="bl-perms">
                <?php foreach (ALL_PERMS as $p): ?>
                <label class="bl-check"><input type="checkbox" data-perm="<?php echo $p; ?>"<?php echo in_array($p, $role['perms'] ?? [], true) ? ' checked' : ''; ?>> <?php echo htmlspecialchars(PERM_LABELS[$p] ?? $p); ?></label>
                <?php endforeach; ?>
              </div>
              <div class="bl-role-actions">
                <button class="bl-btn bl-btn--sm bl-btn--ok" data-role-save type="button"><i class="fas fa-floppy-disk"></i>Speichern</button>
                <button class="bl-btn bl-btn--sm bl-btn--danger" data-role-del type="button"><i class="fas fa-trash"></i>Löschen</button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="bl-add" style="margin-top:14px">
            <input class="bl-field" id="bl-role-key" type="text" placeholder="key (z. B. moderator)" style="max-width:180px" />
            <input class="bl-field" id="bl-role-label" type="text" placeholder="Name (z. B. Moderator)" />
            <input type="color" id="bl-role-color" value="#ff3b3b" style="width:46px;height:44px;border:none;background:none;padding:0;cursor:pointer" />
            <button class="bl-btn bl-btn--primary" id="bl-role-add" type="button"><i class="fas fa-plus"></i>Rolle</button>
          </div>
        </div>

        <!-- User -->
        <div class="bl-set-block">
          <div class="bl-eyebrow">User &amp; Rollen</div>
          <p class="bl-set-hint">Discord-ID eintragen und Rollen zuweisen (Chips anklicken). Ohne Rolle = kein Panel-Zugriff.</p>
          <div class="bl-add">
            <input class="bl-field" id="bl-user-id" type="text" inputmode="numeric" placeholder="Discord-ID (17–19 Ziffern)" />
            <button class="bl-btn bl-btn--primary" id="bl-user-add" type="button"><i class="fas fa-user-plus"></i>Hinzufügen</button>
          </div>
          <div class="bl-list">
            <?php foreach ($setUsers as $uid => $uroles): $uroles = (array)$uroles; ?>
            <div class="bl-row bl-user-row" data-id="<?php echo htmlspecialchars((string)$uid); ?>">
              <div class="bl-avatar"><?php echo htmlspecialchars(bl_initial($setNames[$uid] ?? (string)$uid)); ?></div>
              <div class="bl-row-body">
                <div class="bl-row-name"><b><?php echo htmlspecialchars($setNames[$uid] ?? ('User ' . substr((string)$uid, -4))); ?></b></div>
                <div class="bl-row-id"><?php echo htmlspecialchars((string)$uid); ?></div>
                <div class="bl-role-chips">
                  <?php foreach ($setRoles as $rk => $role): $on = in_array($rk, $uroles, true); ?>
                  <label class="bl-chip<?php echo $on ? ' on' : ''; ?>" style="<?php echo $on ? 'border-color:' . htmlspecialchars($role['color'] ?? '#ff3b3b') : ''; ?>"><input type="checkbox" data-role-chip="<?php echo htmlspecialchars($rk); ?>"<?php echo $on ? ' checked' : ''; ?> hidden><?php echo htmlspecialchars($role['label'] ?? $rk); ?></label>
                  <?php endforeach; ?>
                </div>
              </div>
              <button class="bl-btn bl-btn--sm bl-btn--ok" data-user-save type="button">Speichern</button>
              <i class="fas fa-trash" data-user-del></i>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php if (canUpload()): ?>
<!-- UPLOAD-MODAL -->
<div class="bl-modal" id="bl-upload-modal" data-close-modal hidden>
  <div class="bl-modal-box">
    <div class="bl-modal-head">
      <div class="bl-eyebrow"><b>Dateien hochladen</b></div>
      <div class="bl-modal-close" data-close-modal><i class="fas fa-xmark"></i></div>
    </div>
    <div class="bl-modal-body">
      <div class="bl-drop" id="bl-upload-drop">
        <i class="fas fa-cloud-arrow-up"></i>
        <b>Dateien hier ablegen</b>
        <span>oder klicken zum Auswählen · mehrere gleichzeitig · max. 1024 MB</span>
        <input type="file" multiple />
      </div>
      <div class="bl-queue" id="bl-upload-queue" hidden></div>
    </div>
    <div class="bl-modal-foot">
      <div class="bl-modal-target">
        <i class="fas fa-folder-open"></i>
        <span id="bl-upload-target">Ziel: <?php echo htmlspecialchars('/img/' . $rel); ?></span>
        <span class="bl-pill bl-pill--ok">Ohne Antrag</span>
      </div>
      <div class="bl-modal-actions">
        <button class="bl-btn bl-btn--sm" id="bl-upload-clear" type="button">Leeren</button>
        <button class="bl-btn bl-btn--sm bl-btn--primary" id="bl-upload-go" type="button"><i class="fas fa-cloud-arrow-up"></i>Hochladen</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- BILD-VORSCHAU (Lightbox) -->
<div class="bl-lightbox" id="bl-lightbox" hidden>
  <div class="bl-lb-backdrop" data-lb-close></div>
  <div class="bl-lb-box">
    <div class="bl-lb-head">
      <b id="bl-lb-name"></b>
      <div class="bl-lb-actions">
        <button class="bl-btn bl-btn--sm bl-btn--primary" id="bl-lb-copy" type="button"><i class="fas fa-copy"></i>Link</button>
        <a class="bl-btn bl-btn--sm" id="bl-lb-open" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i>Öffnen</a>
        <button class="bl-btn bl-btn--sm" data-lb-close type="button"><i class="fas fa-xmark"></i></button>
      </div>
    </div>
    <div class="bl-lb-stage"><img id="bl-lb-img" src="" alt="" /></div>
    <div class="bl-lb-foot" id="bl-lb-url"></div>
  </div>
</div>

<!-- Bestätigen (Löschen) -->
<div class="bl-modal" id="bl-confirm" data-confirm-close hidden>
  <div class="bl-modal-box bl-confirm-box">
    <div class="bl-confirm-ic"><i class="fas fa-trash-can"></i></div>
    <h3 id="bl-confirm-title">Löschen?</h3>
    <p id="bl-confirm-text">Diese Aktion kann nicht rückgängig gemacht werden.</p>
    <div class="bl-confirm-actions">
      <button class="bl-btn" data-confirm-close type="button">Abbrechen</button>
      <button class="bl-btn bl-btn--danger" id="bl-confirm-ok" type="button"><i class="fas fa-trash"></i>Löschen</button>
    </div>
  </div>
</div>

<!-- Duplikate gefunden -->
<div class="bl-modal" id="bl-dup" hidden>
  <div class="bl-modal-box bl-confirm-box">
    <div class="bl-confirm-ic"><i class="fas fa-triangle-exclamation"></i></div>
    <h3><span id="bl-dup-count">0</span> Datei(en) existieren bereits</h3>
    <p>Diese Dateien liegen schon im Zielordner:</p>
    <ul class="bl-dup-list" id="bl-dup-list"></ul>
    <div class="bl-confirm-actions">
      <button class="bl-btn" data-dup="cancel" type="button">Abbrechen</button>
      <button class="bl-btn" data-dup="skip" type="button"><i class="fas fa-forward"></i>Überspringen</button>
      <button class="bl-btn bl-btn--danger" data-dup="overwrite" type="button"><i class="fas fa-rotate"></i>Überschreiben</button>
    </div>
  </div>
</div>

<!-- Eingabe (Umbenennen / Ordner anlegen / Grund …) -->
<div class="bl-modal" id="bl-prompt" hidden>
  <div class="bl-modal-box bl-confirm-box">
    <div class="bl-confirm-ic"><i class="fas fa-pen" id="bl-prompt-ic"></i></div>
    <h3 id="bl-prompt-title">Eingabe</h3>
    <p id="bl-prompt-text" hidden></p>
    <input type="text" id="bl-prompt-input" class="bl-field bl-prompt-input" autocomplete="off" />
    <div class="bl-confirm-actions">
      <button class="bl-btn" data-prompt-close type="button">Abbrechen</button>
      <button class="bl-btn bl-btn--primary" id="bl-prompt-ok" type="button"><i class="fas fa-check"></i>OK</button>
    </div>
  </div>
</div>

<div class="bl-toast" id="bl-toast" hidden><span></span></div>

<script>window.BL_CFG = { apiUrl: '<?php echo $basePath; ?>/api.php', imgBase: '<?php echo $basePath; ?>', path: '<?php echo htmlspecialchars($rel, ENT_QUOTES); ?>', allowedExt: <?php echo json_encode(array_values(ALLOWED_EXTENSIONS)); ?> };</script>
<script src="<?php echo $basePath; ?>/js/bloodline.js?v=<?php echo @filemtime(__DIR__ . "/js/bloodline.js"); ?>"></script>
<?php endif; ?>
</body>
</html>
