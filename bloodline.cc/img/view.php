<?php
// ========================================
// BLOODLINE GALLERY - VIDEO VIEWER
// ÖFFENTLICH - keine Auth, keine Session, kein Whitelist-Check.
// Jeder mit dem Link kann das Video ansehen.
// ========================================

require_once __DIR__ . '/config.php';

$basePath = '/img';
$baseUrl  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

/**
 * ?f= für Query-String: Ordner mit normalen /, pro Pfad-Segment nur urlencode (Leerzeichen → +, kein %2F).
 * Funktioniert überall ohne mod_rewrite; alte Links mit %2F im einen Parameter funktionieren weiter.
 */
function view_query_f_param($rel) {
    return implode('/', array_map('urlencode', explode('/', $rel)));
}
function view_page_url($baseUrl, $basePath, $rel) {
    return $baseUrl . $basePath . '/view.php?f=' . view_query_f_param($rel);
}

// Datei-Parameter sanitizen (robust via realpath)
$rel = $_GET['f'] ?? '';
$rel = str_replace(['\\', "\0"], '/', $rel);
$rel = ltrim($rel, '/');

$videoExts = ['mp4', 'webm', 'mov', 'avi'];

if ($rel === '' || strpos($rel, '..') !== false) {
    http_response_code(404);
    echo 'Ungültiger Pfad.';
    exit;
}

$base = realpath(__DIR__);
$abs  = realpath(__DIR__ . '/' . $rel);

if (!$abs || strpos($abs, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($abs)) {
    http_response_code(404);
    echo 'Video nicht gefunden: ' . htmlspecialchars($rel);
    exit;
}

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
if (!in_array($ext, $videoExts, true)) {
    http_response_code(404);
    echo 'Kein unterstütztes Video.';
    exit;
}

$canonicalPageUrl = view_page_url($baseUrl, $basePath, $rel);

// URL-encodete Pfad-Segmente (damit Leerzeichen/Sonderzeichen funktionieren)
$encodedRel = implode('/', array_map('rawurlencode', explode('/', $rel)));

// Link-Vorschau-Crawler (z. B. Discord, Telegram) → Weiterleitung zur Roh-Datei für natives Video.
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/discordbot|twitterbot|telegrambot|facebookexternalhit|slackbot|whatsapp/i', $ua)) {
    header('Location: ' . $basePath . '/' . $encodedRel, true, 302);
    exit;
}

$name     = basename($rel);
$mtime    = filemtime($abs);
$fsize    = filesize($abs);
$mime     = $ext === 'mov' ? 'video/quicktime' : ($ext === 'avi' ? 'video/x-msvideo' : 'video/' . $ext);
$fileUrl  = $basePath . '/' . $encodedRel . '?v=' . $mtime;
$fullUrl  = $baseUrl . $basePath . '/' . $encodedRel;

function fmtSize($b) {
    $u = ['B','KB','MB','GB'];
    $i = 0;
    while ($b >= 1024 && $i < count($u)-1) { $b /= 1024; $i++; }
    return round($b, 2) . ' ' . $u[$i];
}

$displayName = sanitize_upload_stem_video(pathinfo($name, PATHINFO_FILENAME)) . '.' . $ext;
$ogDescription = $displayName . ' · ' . fmtSize($fsize) . ' · Bloodline Gallery';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($displayName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#D32F2F">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalPageUrl); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo htmlspecialchars($basePath); ?>/icon.svg">

    <!-- Open Graph: Titel & Beschreibung für Link-Vorschau in Chats -->
    <meta property="og:type" content="video.other">
    <meta property="og:title" content="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="Bloodline Gallery">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalPageUrl); ?>">
    <meta property="og:locale" content="de_DE">
    <meta property="og:video" content="<?php echo htmlspecialchars($fullUrl); ?>">
    <meta property="og:video:secure_url" content="<?php echo htmlspecialchars($fullUrl); ?>">
    <meta property="og:video:type" content="<?php echo $mime; ?>">
    <meta property="og:video:width" content="1280">
    <meta property="og:video:height" content="720">
    <meta name="twitter:card" content="player">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:player:width" content="1280">
    <meta name="twitter:player:height" content="720">
    <meta name="twitter:player:stream" content="<?php echo htmlspecialchars($fullUrl); ?>">
    <meta name="twitter:player:stream:content_type" content="<?php echo $mime; ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,500&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            overflow: hidden;
            background: #050505;
            color: #ececec;
            font-family: 'DM Sans', system-ui, sans-serif;
            font-optical-sizing: auto;
            -webkit-font-smoothing: antialiased;
        }
        .vp {
            height: 100%;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(211, 47, 47, 0.14), transparent),
                radial-gradient(ellipse 50% 40% at 100% 100%, rgba(211, 47, 47, 0.06), transparent);
        }
        .vp-stage {
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .vp-stage video {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.06),
                0 24px 64px rgba(0,0,0,0.65),
                0 0 80px rgba(211,47,47,0.12);
        }
        .vp-bar {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px calc(14px + env(safe-area-inset-bottom, 0));
            background: rgba(8,8,8,0.88);
            border-top: 1px solid rgba(211,47,47,0.22);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .vp-name {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: clamp(15px, 2.8vw, 18px);
            letter-spacing: 0.03em;
            color: #fff;
            line-height: 1.25;
            word-break: break-word;
            min-width: 0;
        }
        .vp-name span {
            display: block;
            margin-top: 4px;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,82,82,0.9);
        }
        .vp-back {
            flex-shrink: 0;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 600;
            color: #FF5252;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(211,47,47,0.35);
            background: rgba(211,47,47,0.08);
            transition: background 0.2s, border-color 0.2s;
        }
        .vp-back:hover {
            background: rgba(211,47,47,0.16);
            border-color: rgba(255,82,82,0.5);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="vp">
        <div class="vp-stage">
            <video controls playsinline preload="metadata">
                <source src="<?php echo htmlspecialchars($fileUrl); ?>" type="<?php echo $mime; ?>">
            </video>
        </div>
        <div class="vp-bar">
            <div class="vp-name">
                <?php echo htmlspecialchars($displayName); ?>
                <span>Bloodline Gallery</span>
            </div>
            <a class="vp-back" href="<?php echo htmlspecialchars($basePath . '/'); ?>">Gallery</a>
        </div>
    </div>
</body>
</html>
