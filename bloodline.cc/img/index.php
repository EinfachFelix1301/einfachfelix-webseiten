<?php
// ========================================
// BLOODLINE MEDIA — LANDING (Design-Export integriert)
// ========================================
require_once 'config.php';

$user = getUser();
$loggedIn = isLoggedIn();
$canPanel = canAccess();
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// --- Statistiken ---
function bl_count_images($dir) {
    if (!is_dir($dir)) return 0;
    $n = 0;
    foreach (@scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp','svg','bmp'], true)) $n++;
    }
    return $n;
}
function bl_count_all_images($root) {
    $n = 0;
    $rii = @new RecursiveIteratorIterator(@new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    if ($rii) foreach ($rii as $f) {
        if ($f->isFile() && in_array(strtolower($f->getExtension()), ['jpg','jpeg','png','gif','webp','svg','bmp'], true)) $n++;
    }
    return $n;
}
function bl_count_folders($root) {
    $n = 0;
    foreach (@scandir($root) ?: [] as $f) {
        if ($f === '.' || $f === '..' || $f[0] === '.' || $f === 'api' || $f === 'css' || $f === 'js') continue;
        if (is_dir($root . '/' . $f)) $n++;
    }
    return $n;
}
$statFiles   = bl_count_all_images(__DIR__ . '/phone');
$statFolders = bl_count_folders(__DIR__);
$statPending = getPendingAntragCount();

// Hero-Kacheln: neueste Bilder NUR aus ic_bilder
$heroTiles = [];
$icDir = __DIR__ . '/phone/ic_bilder';
if (is_dir($icDir)) {
    $imgs = [];
    foreach (@scandir($icDir) ?: [] as $f) {
        if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp'], true)) {
            $imgs[$f] = filemtime($icDir . '/' . $f);
        }
    }
    arsort($imgs);
    foreach (array_slice(array_keys($imgs), 0, 6) as $f) {
        $heroTiles[] = MEDIA_PUBLIC_BASE . '/phone/ic_bilder/' . rawurlencode($f);
    }
}
$heroExample = $heroTiles[0] ?? (MEDIA_PUBLIC_BASE . '/phone/ic_bilder/epinephrine.png');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Bloodline · Media Gallery</title>
<link rel="icon" type="image/png" href="https://bloodline.cc/img/bloodline/bl_transparent.png" />
<meta name="description" content="Bilderverwaltung für das Phone — hochladen, freigeben, Link kopieren." />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&amp;family=Rajdhani:wght@400;500;600;700&amp;family=Oxanium:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="<?php echo $basePath; ?>/css/bloodline.css?v=<?php echo @filemtime(__DIR__ . "/css/bloodline.css"); ?>" />
</head>
<body>

<div class="bl-landing">

  <header class="bl-topbar">
    <a class="bl-mark" href="<?php echo $basePath; ?>/"><b>BLOOD</b><b>LINE</b><span>Media Gallery</span></a>
    <div class="bl-topbar-actions">
      <div class="bl-status">API online</div>
      <?php if ($loggedIn && $canPanel): ?>
        <a class="bl-btn" href="<?php echo $basePath; ?>/panel.php" target="_blank" rel="noopener"><i class="fas fa-table-cells-large"></i>Panel</a>
        <a class="bl-btn bl-btn--primary" href="logout.php"><i class="fas fa-right-from-bracket"></i>Abmelden</a>
      <?php else: ?>
        <a class="bl-btn bl-btn--primary" href="login.php"><i class="fab fa-discord"></i>Mit Discord anmelden</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- HERO -->
  <section class="bl-wrap bl-hero">
    <div>
      <div class="bl-eyebrow bl-eyebrow--wide">Bilderverwaltung für das Phone</div>
      <h1>HOCHLADEN.<br /><em>FREIGEBEN.</em><br />LINK KOPIEREN.</h1>
      <p>Wallpaper, Profilbilder, Gang-Logos und Fahrzeug-Skins für das Phone — an einem Ort. Spieler laden hoch, das Team gibt frei, der fertige Direktlink landet sofort im Script.</p>
      <div class="bl-cta-row">
        <?php if ($loggedIn && $canPanel): ?>
          <a class="bl-btn bl-btn--primary bl-btn--lg" href="<?php echo $basePath; ?>/panel.php" target="_blank" rel="noopener"><i class="fas fa-table-cells-large"></i>Zum Panel</a>
        <?php else: ?>
          <a class="bl-btn bl-btn--primary bl-btn--lg" href="login.php"><i class="fab fa-discord"></i>Mit Discord anmelden</a>
        <?php endif; ?>
        <a class="bl-btn bl-btn--lg" href="#einreichen"><i class="fas fa-cloud-arrow-up"></i>Bild einreichen</a>
      </div>
      <div class="bl-stats">
        <div class="bl-stat"><b><?php echo (int)$statFiles; ?></b><span>Dateien</span></div>
        <div class="bl-stat"><b><?php echo (int)$statFolders; ?></b><span>Ordner</span></div>
        <div class="bl-stat bl-stat--red"><b><?php echo (int)$statPending; ?></b><span>Offene Anträge</span></div>
        <div class="bl-stat"><b>1 GB</b><span>Pro Datei</span></div>
      </div>
    </div>

    <div class="bl-mock">
      <div class="bl-shell">
        <div class="bl-mock-bar">
          <span class="bl-dot bl-dot--red"></span>
          <span class="bl-dot"></span>
          <span class="bl-dot"></span>
          <span class="bl-mock-path">/img/phone/ic_bilder</span>
        </div>
        <div class="bl-mock-grid">
          <?php for ($i = 0; $i < 6; $i++): ?>
            <?php if (isset($heroTiles[$i])): ?>
              <img src="<?php echo htmlspecialchars($heroTiles[$i]); ?>" alt="" />
            <?php else: ?>
              <div style="aspect-ratio:1;border:1px dashed rgba(255,255,255,.1);border-radius:9px;background:rgba(255,255,255,.02)"></div>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <div class="bl-mock-foot">
          <div class="bl-mock-url"><i class="fas fa-link"></i><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $heroExample)); ?></div>
          <div class="bl-pill">Kopiert</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ÖFFENTLICHES EINREICHEN -->
  <section class="bl-wrap bl-section bl-split" id="einreichen">
    <div>
      <div class="bl-eyebrow bl-eyebrow--wide">Mit Discord-Login</div>
      <h2>BILD EINREICHEN.<br /><em>KURZ ANMELDEN.</em></h2>
      <p>Mit Discord anmelden, Bild reinziehen, absenden — fertig. Jede Einreichung landet als Antrag im festen Ordner und wird vom Team geprüft. Erst nach der Freigabe ist der Link im Phone nutzbar.</p>
      <ul class="bl-facts">
        <li><i class="fab fa-discord"></i>Anmeldung per Discord nötig</li>
        <li><i class="fas fa-lock"></i>Fester Zielordner: phone / eingereicht</li>
        <li><i class="fas fa-image"></i>PNG, JPG, WEBP, GIF · max. 1024 MB</li>
        <li><i class="fas fa-shield-halved"></i>Freigabe durch das Team, Ablehnung mit Grund</li>
      </ul>
    </div>

    <div class="bl-guest">
      <?php if (!$loggedIn): ?>
      <div class="bl-done" style="display:block">
        <div class="bl-done-mark"><i class="fab fa-discord"></i></div>
        <h3>Mit Discord anmelden</h3>
        <p>Zum Einreichen ist ein Discord-Login nötig. Danach kannst du sofort Bilder hochladen.</p>
        <a class="bl-btn bl-btn--primary" href="login.php"><i class="fab fa-discord"></i>Jetzt anmelden</a>
      </div>
      <?php else: ?>
      <div id="bl-guest-form">
        <div class="bl-drop" id="bl-guest-drop">
          <i class="fas fa-cloud-arrow-up"></i>
          <b>Bild hier ablegen</b>
          <span>oder klicken zum Auswählen</span>
          <input type="file" multiple accept="image/*" />
        </div>

        <div class="bl-queue" id="bl-guest-queue" hidden></div>

        <div class="bl-guest-grid">
          <input class="bl-field" id="bl-guest-name" type="text" placeholder="Dein Name im Server" value="<?php echo htmlspecialchars(bl_norm_name($user['name'] ?? '')); ?>" />
          <input class="bl-field" id="bl-guest-note" type="text" placeholder="Notiz für das Team (optional)" />
        </div>

        <div class="bl-guest-foot">
          <div class="bl-guest-target"><i class="fas fa-lock"></i>phone / eingereicht</div>
          <button class="bl-btn bl-btn--primary" id="bl-guest-submit" type="button"><i class="fas fa-paper-plane"></i>Antrag absenden</button>
        </div>
      </div>

      <div class="bl-done" id="bl-guest-done" hidden>
        <div class="bl-done-mark"><i class="fas fa-check"></i></div>
        <h3>Antrag eingereicht</h3>
        <p>Dein Bild liegt jetzt im Team-Panel zur Prüfung. Nach der Freigabe erscheint es in phone / eingereicht und der Link funktioniert im Phone.</p>
        <button class="bl-btn" id="bl-guest-again" type="button"><i class="fas fa-plus"></i>Weiteres Bild einreichen</button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ABLAUF -->
  <section class="bl-wrap bl-section">
    <div class="bl-eyebrow bl-eyebrow--wide">So läuft ein Bild durch</div>
    <div class="bl-steps">
      <article class="bl-card bl-step">
        <div class="bl-step-top"><i class="fas fa-cloud-arrow-up"></i><b>01</b></div>
        <h3>Spieler lädt hoch</h3>
        <p>Bild reinziehen, Notiz dazu — fertig. Dateinamen werden automatisch link-sicher gemacht.</p>
      </article>
      <article class="bl-card bl-step">
        <div class="bl-step-top"><i class="fas fa-shield-halved"></i><b>02</b></div>
        <h3>Team prüft den Antrag</h3>
        <p>Jede Einreichung landet als Antrag im Panel. Freigeben, ablehnen, in einen anderen Ordner verschieben.</p>
      </article>
      <article class="bl-card bl-step">
        <div class="bl-step-top"><i class="fas fa-mobile-screen"></i><b>03</b></div>
        <h3>Direkt im Phone</h3>
        <p>Freigegebene Bilder liegen sofort unter /img/phone/ — der Upload-Endpoint schickt die URL direkt zurück ins Script.</p>
      </article>
    </div>
  </section>

  <footer class="bl-foot">Bloodline · Media Gallery</footer>
</div>

<div class="bl-toast" id="bl-toast" hidden><span></span></div>

<script>window.BL_CFG = { apiUrl: '<?php echo $basePath; ?>/api.php', imgBase: '<?php echo $basePath; ?>' };</script>
<script src="<?php echo $basePath; ?>/js/bloodline.js?v=<?php echo @filemtime(__DIR__ . "/js/bloodline.js"); ?>"></script>
</body>
</html>
