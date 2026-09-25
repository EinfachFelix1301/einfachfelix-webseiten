<?php
// ========================================
// BLOODLINE GALLERY - KONFIGURATION (Vorlage)
// Kopieren nach config.php und ausfuellen. config.php ist gitignored.
// ========================================

// Session-Cookie-Parameter MÜSSEN vor session_start() gesetzt werden
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
// Session über alle bloodline.cc-Subdomains teilen (media.bloodline.cc <-> bloodline.cc)
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'example.com') !== false) {
    ini_set('session.cookie_domain', '.example.com');
}

session_start();

// Debug: true = API liefert _debug-Infos + debug.log; für Produktion auf false setzen
define('GALLERY_DEBUG', false);

// Maximale Upload-Größe in MB (muss zu upload_max_filesize / post_max_size in .user.ini passen)
define('MAX_UPLOAD_SIZE', 1024);

// Erlaubte Dateitypen
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'pdf', 'zip', 'rar', '7z', 'txt', 'json', 'xml', 'mp4', 'mp3', 'wav', 'ogg', 'webm', 'mov', 'avi', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv']);

// ========================================
// DISCORD OAUTH2 KONFIGURATION
// ========================================
define('DISCORD_CLIENT_ID', 'DEINE_DISCORD_CLIENT_ID');
define('DISCORD_CLIENT_SECRET', 'DEIN_DISCORD_CLIENT_SECRET');
define('DISCORD_BOT_TOKEN', 'DEIN_DISCORD_BOT_TOKEN');

// Redirect-URI dynamisch je Host (media.bloodline.cc -> /callback.php, bloodline.cc -> /img/callback.php)
// Beide URIs müssen im Discord Developer Portal als Redirects eingetragen sein.
$__host = $_SERVER['HTTP_HOST'] ?? 'example.com';
$__redirBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('DISCORD_REDIRECT_URI', 'https://' . $__host . $__redirBase . '/callback.php');

// ========================================
// SEITEN-KONFIGURATION
// ========================================
define('SITE_URL', 'https://media.example.com');
define('SITE_NAME', 'Media');

// Oeffentliche Basis-URL fuer Bilder-Direktlinks (lb_phone liest von hier).
// TODO: nach DNS + Cert fuer media.bloodline.cc auf 'https://media.bloodline.cc' umstellen (saubere Links ohne /img).
define('MEDIA_PUBLIC_BASE', 'https://example.com/img');

// (Session-Cookie-Parameter werden oben vor session_start() gesetzt)

// ========================================
// EINSTELLUNGEN / ROLLEN / RECHTE (settings.json)
// ========================================
// Verfuegbare Rechte (perms):
//   view      - Panel oeffnen / Gallery ansehen
//   upload    - Bilder in Gallery hochladen
//   folders   - Ordner anlegen/loeschen/umbenennen, Bilder verwalten
//   antraege  - Antraege sehen + genehmigen/ablehnen
//   settings  - Einstellungen verwalten (Rollen, User, Zielordner)
define('ALL_PERMS', ['view', 'upload', 'folders', 'antraege', 'settings']);
define('PERM_LABELS', [
    'view'     => 'Panel ansehen',
    'upload'   => 'Bilder hochladen',
    'folders'  => 'Ordner verwalten',
    'antraege' => 'Anträge freigeben',
    'settings' => 'Einstellungen',
]);

function defaultSettings() {
    return [
        'roles' => [
            'admin' => ['label' => 'Admin', 'color' => '#ff3b3b', 'perms' => ['view', 'upload', 'folders', 'antraege', 'settings']],
            'team'  => ['label' => 'Team',  'color' => '#ff9800', 'perms' => ['view', 'antraege']],
            'user'  => ['label' => 'User',  'color' => '#4caf50', 'perms' => ['view', 'upload']],
        ],
        'users' => new stdClass(), // discord_id => [rollen]
        'antrag' => [
            'pending_folder' => 'lb_phone/eingereicht',
            'target_folders' => ['lb_phone/ic_bilder'],
        ],
    ];
}

function loadSettings() {
    $file = __DIR__ . '/settings.json';
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data) && isset($data['roles'])) {
            // Sicherstellen dass antrag-Config existiert
            if (!isset($data['antrag'])) $data['antrag'] = defaultSettings()['antrag'];
            if (!isset($data['users']) || !is_array($data['users'])) $data['users'] = [];
            return $data;
        }
    }
    // Migration aus whitelist.json (einmalig)
    $s = defaultSettings();
    $s['users'] = [];
    $wlFile = __DIR__ . '/whitelist.json';
    if (file_exists($wlFile)) {
        $wl = json_decode(file_get_contents($wlFile), true) ?: [];
        foreach (($wl['users'] ?? []) as $id)  { $s['users'][(string)$id] = ['user']; }
        foreach (($wl['admins'] ?? []) as $id) { $s['users'][(string)$id] = ['admin']; }
    }
    saveSettings($s);
    return $s;
}

function saveSettings($data) {
    $file = __DIR__ . '/settings.json';
    if (isset($data['users']) && $data['users'] instanceof stdClass) {
        $data['users'] = (array) $data['users'];
    }
    return file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

// Rollen eines Users (Array von Rollen-Keys)
function getUserRoles($id) {
    $s = loadSettings();
    $users = $s['users'] ?? [];
    return $users[(string)$id] ?? [];
}

// Alle Rechte eines Users (aus seinen Rollen zusammengefasst)
function userPerms($id) {
    $s = loadSettings();
    $roles = $s['roles'] ?? [];
    $perms = [];
    foreach (getUserRoles($id) as $roleKey) {
        if (isset($roles[$roleKey]['perms'])) {
            $perms = array_merge($perms, $roles[$roleKey]['perms']);
        }
    }
    return array_values(array_unique($perms));
}

// ========================================
// BERECHTIGUNGEN
// ========================================

function isLoggedIn() {
    return isset($_SESSION['discord_user_id']);
}

function getUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['discord_user_id'],
        'name' => $_SESSION['discord_username'],
        'avatar' => $_SESSION['discord_avatar'] ?? null
    ];
}

function getUserAvatar() {
    if (!isLoggedIn() || empty($_SESSION['discord_avatar'])) {
        return null;
    }
    return 'https://cdn.discordapp.com/avatars/' . $_SESSION['discord_user_id'] . '/' . $_SESSION['discord_avatar'] . '.png';
}

// Prueft ob der eingeloggte User ein bestimmtes Recht hat
function hasPerm($perm) {
    if (!isLoggedIn()) return false;
    return in_array($perm, userPerms($_SESSION['discord_user_id']), true);
}

function canAccess()        { return hasPerm('view'); }
function isAdmin()          { return hasPerm('settings'); }
function canUpload()        { return hasPerm('upload'); }
function canManageFolders() { return hasPerm('folders'); }
function canManageUsers()   { return hasPerm('settings'); }
function canManageAntraege(){ return hasPerm('antraege'); }

function getAllowedExtensions() {
    return ALLOWED_EXTENSIONS;
}

// ---- Legacy-Shims (falls alter Code sie noch nutzt) ----
function getAdminIds() {
    $s = loadSettings();
    $out = [];
    foreach (($s['users'] ?? []) as $id => $roles) {
        if (in_array('admin', (array)$roles, true)) $out[] = (string)$id;
    }
    return $out;
}
function getAllowedIds() {
    $s = loadSettings();
    return array_map('strval', array_keys($s['users'] ?? []));
}

/** Dateiname-Stamm: kein #, +, Leerzeichen u. a. — nur A–Z, a–z, 0–9, -, _ (URLs & Embeds) */
function sanitize_upload_stem($stem) {
    $s = preg_replace('/[#+%&?=\s\(\)\[\]!$\'",;<>\\\|\.]+/u', '-', (string) $stem);
    $s = preg_replace('/[^a-zA-Z0-9_\-]/u', '', $s);
    $s = preg_replace('/-+/', '-', $s);
    $s = preg_replace('/_+/', '_', $s);
    $s = trim($s, '-_');
    if ($s === '' || $s === '.') {
        $s = 'file';
    }
    return $s;
}

function is_gallery_video_ext($ext) {
    return in_array(strtolower((string) $ext), ['mp4', 'webm', 'mov', 'avi'], true);
}

/**
 * Normalisiert „Fancy"-Unicode-Buchstaben (𝐁𝐨𝐥𝐝, 𝑰𝒕𝒂𝒍𝒊𝒄, 𝓢𝓬𝓻𝓲𝓹𝓽, ℱ𝔯𝔞𝔨𝔱𝔲𝔯,
 * Ｆｕｌｌｗｉｄｔｈ …) zurück auf normales ASCII, damit Namen in der echten Font erscheinen.
 */
function bl_norm_name($s) {
    $s = (string) $s;
    if ($s === '') return $s;

    // Codepoint eines UTF-8-Zeichens
    $ord = function ($c) {
        $b = unpack('N', mb_convert_encoding($c, 'UTF-32BE', 'UTF-8'));
        return $b[1];
    };
    // Startpunkte der Mathematical-Alphanumeric-Blöcke (je 26 Groß + 26 Klein)
    $starts = [0x1D400,0x1D434,0x1D468,0x1D49C,0x1D4D0,0x1D504,0x1D538,0x1D56C,0x1D5A0,0x1D5D4,0x1D608,0x1D63C,0x1D670];
    // Löcher (letterlike symbols) + Superscript/Modifier-Buchstaben -> ASCII
    $holes = [
        0x210E=>'h',0x212C=>'B',0x2130=>'E',0x2131=>'F',0x210B=>'H',0x2110=>'I',0x2112=>'L',0x2133=>'M',
        0x211B=>'R',0x212F=>'e',0x210A=>'g',0x2134=>'o',0x2102=>'C',0x210D=>'H',0x2115=>'N',0x2119=>'P',
        0x211A=>'Q',0x211D=>'R',0x2124=>'Z',0x212D=>'C',0x210C=>'H',0x2111=>'I',0x211C=>'R',0x2128=>'Z',
        // Superscript-Ziffern + i/n
        0x2070=>'0',0x00B9=>'1',0x00B2=>'2',0x00B3=>'3',0x2074=>'4',0x2075=>'5',0x2076=>'6',0x2077=>'7',
        0x2078=>'8',0x2079=>'9',0x2071=>'i',0x207F=>'n',
        // Modifier-Kleinbuchstaben (Phonetic/Spacing Modifier)
        0x1D43=>'a',0x1D47=>'b',0x1D9C=>'c',0x1D48=>'d',0x1D49=>'e',0x1DA0=>'f',0x1D4D=>'g',0x02B0=>'h',
        0x02B2=>'j',0x1D4F=>'k',0x02E1=>'l',0x1D50=>'m',0x1D52=>'o',0x1D56=>'p',0x02B3=>'r',0x02E2=>'s',
        0x1D57=>'t',0x1D58=>'u',0x1D5B=>'v',0x02B7=>'w',0x02E3=>'x',0x02B8=>'y',0x1DBB=>'z',0x1D45=>'a',
        // Modifier-Grossbuchstaben
        0x1D2C=>'A',0x1D2E=>'B',0x1D30=>'D',0x1D31=>'E',0x1D33=>'G',0x1D34=>'H',0x1D35=>'I',0x1D36=>'J',
        0x1D37=>'K',0x1D38=>'L',0x1D39=>'M',0x1D3A=>'N',0x1D3C=>'O',0x1D3E=>'P',0x1D3F=>'R',0x1D40=>'T',
        0x1D41=>'U',0x2C7D=>'V',0x1D42=>'W',0x1D2F=>'B',0x1D3B=>'N',
    ];

    $out = '';
    $len = mb_strlen($s, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($s, $i, 1, 'UTF-8');
        $cp = $ord($ch);
        $m = null;
        if ($cp >= 0xFF21 && $cp <= 0xFF3A) $m = chr($cp - 0xFF21 + 65);
        elseif ($cp >= 0xFF41 && $cp <= 0xFF5A) $m = chr($cp - 0xFF41 + 97);
        elseif ($cp >= 0xFF10 && $cp <= 0xFF19) $m = chr($cp - 0xFF10 + 48);
        elseif ($cp >= 0x1D7CE && $cp <= 0x1D7FF) $m = chr((($cp - 0x1D7CE) % 10) + 48);
        elseif (isset($holes[$cp])) $m = $holes[$cp];
        else {
            foreach ($starts as $st) {
                if ($cp >= $st && $cp <= $st + 25) { $m = chr($cp - $st + 65); break; }
                if ($cp >= $st + 26 && $cp <= $st + 51) { $m = chr($cp - $st - 26 + 97); break; }
            }
        }
        $out .= $m === null ? $ch : $m;
    }
    return $out;
}

// ========================================
// DISCORD USER LOOKUP (Bot API)
// ========================================
function fetchDiscordUsername($id) {
    $id = preg_replace('/[^0-9]/', '', (string) $id);
    if ($id === '') return null;

    $ch = curl_init('https://discord.com/api/v10/users/' . $id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_HTTPHEADER => ['Authorization: Bot ' . DISCORD_BOT_TOKEN],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;
    $data = json_decode($resp, true);
    if (!is_array($data)) return null;
    return $data['global_name'] ?? $data['username'] ?? null;
}

function fetchDiscordUsernames(array $ids) {
    $out = [];
    foreach (array_unique($ids) as $id) {
        $name = fetchDiscordUsername($id);
        if ($name !== null) $out[(string) $id] = $name;
    }
    return $out;
}

// ========================================
// GAME-DB (lb-phone Sync) — selbe MySQL wie FiveM
// ========================================
define('GAME_DB_HOST', '127.0.0.1');
define('GAME_DB_NAME', 'fivem');
define('GAME_DB_USER', 'fivem');
define('GAME_DB_PASS', 'CHANGE_ME');

function gamePdo() {
    static $pdo = null;
    if ($pdo !== null) return $pdo ?: null;
    try {
        $pdo = new PDO(
            'mysql:host=' . GAME_DB_HOST . ';dbname=' . GAME_DB_NAME . ';charset=utf8mb4',
            GAME_DB_USER, GAME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 4]
        );
    } catch (Throwable $e) {
        $pdo = false;
        return null;
    }
    return $pdo;
}

/**
 * Schiebt ein freigegebenes Bild ins lb-phone (Fotos-App) des Einreichers.
 * discord -> users.identifier -> phone_phones (zuletzt genutzt) -> phone_photos INSERT.
 * Best-effort: gibt Handynummer zurueck oder null. Bricht Freigabe nie ab.
 */
function syncPhotoToPhone($discordId, $url, $bytes = 0) {
    $discordId = preg_replace('/[^0-9]/', '', (string) $discordId);
    if ($discordId === '' || $url === '') return null;
    $pdo = gamePdo();
    if (!$pdo) return null;
    try {
        // Zuletzt genutztes Handy des Discord-Users
        $st = $pdo->prepare(
            'SELECT p.phone_number
             FROM users u
             JOIN phone_phones p ON p.owner_id = u.identifier
             WHERE u.discord = :d
             ORDER BY p.last_seen DESC
             LIMIT 1'
        );
        $st->execute([':d' => $discordId]);
        $phone = $st->fetchColumn();
        if (!$phone) return null;

        $ins = $pdo->prepare(
            'INSERT INTO phone_photos (phone_number, link, is_video, size)
             VALUES (:pn, :link, 0, :size)'
        );
        $ins->execute([':pn' => $phone, ':link' => $url, ':size' => (float) $bytes]);
        return $phone;
    } catch (Throwable $e) {
        return null;
    }
}


// ========================================
// MEDIA-ANTRAG-SYSTEM (IC-Handy Bilder)
// ========================================

// Erlaubte Bild-Typen fuer Antraege
define('ANTRAG_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Max Groesse Antrag-Bild in MB
define('ANTRAG_MAX_SIZE', 1024);

// Kategorien (key => Anzeigename)
define('ANTRAG_CATEGORIES', [
    'instapic' => 'InstaPic (Menyoo)',
    'werbung'  => 'Unternehmen / Werbung',
]);

// ---- Zielordner-Konfiguration (aus settings.json) ----

/** Relativer Pending-Ordner, z. B. "lb_phone/eingereicht" */
function getAntragPendingRel() {
    $s = loadSettings();
    $rel = $s['antrag']['pending_folder'] ?? 'lb_phone/eingereicht';
    return trim(str_replace(['..', '\\'], '', $rel), '/');
}

/** Erlaubte Ziel-Ordner (relativ) fuer die Freigabe */
function getAntragTargetFolders() {
    $s = loadSettings();
    $list = $s['antrag']['target_folders'] ?? ['lb_phone/ic_bilder'];
    $out = [];
    foreach ($list as $rel) {
        $rel = trim(str_replace(['..', '\\'], '', $rel), '/');
        if ($rel !== '') $out[] = $rel;
    }
    return $out ?: ['lb_phone/ic_bilder'];
}

function getAntragPendingDir()  { return __DIR__ . '/' . getAntragPendingRel(); }
function getAntragPendingUrl()  { return MEDIA_PUBLIC_BASE . '/' . getAntragPendingRel() . '/'; }
function antragFolderDir($rel)  { return __DIR__ . '/' . trim(str_replace(['..','\\'], '', $rel), '/'); }
function antragFolderUrl($rel)  { return MEDIA_PUBLIC_BASE . '/' . trim(str_replace(['..','\\'], '', $rel), '/') . '/'; }

function loadAntraege() {
    $file = __DIR__ . '/antraege.json';
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveAntraege($data) {
    $file = __DIR__ . '/antraege.json';
    return file_put_contents(
        $file,
        json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function getPendingAntragCount() {
    $count = 0;
    foreach (loadAntraege() as $a) {
        if (($a['status'] ?? '') === 'pending') $count++;
    }
    return $count;
}

function antragCategoryLabel($key) {
    $cats = ANTRAG_CATEGORIES;
    return $cats[$key] ?? $key;
}

/** Video: nur Kleinbuchstaben + Ziffern, alles andere entfernt (z. B. „2 Tag Mob“ → 2tagmob) */
function sanitize_upload_stem_video($stem) {
    $s = mb_strtolower((string) $stem, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/u', '', $s);
    if ($s === '') {
        $s = 'video';
    }
    return $s;
}
?>
