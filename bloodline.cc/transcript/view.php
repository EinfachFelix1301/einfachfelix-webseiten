<?php
/**
 * Transcript anzeigen
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// ID prüfen
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header('Location: index.php');
    exit;
}

// Transcript laden
try {
    $db = Database::getInstance();
    $transcript = $db->getTranscript($id);
} catch (Exception $e) {
    $transcript = null;
}

if (!$transcript) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nicht gefunden - <?= SITE_NAME ?></title>
        <link rel="icon" type="image/png" href="https://bloodline.cc/img/bloodline/bl_transparent.png">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error-page">
                <h1>404</h1>
                <p>Transcript nicht gefunden.</p>
                <a href="index.php" class="btn btn-primary">Zurück zur Übersicht</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Zugriffsprüfung
$isLoggedIn = DiscordAuth::isLoggedIn();
$isAdmin = $isLoggedIn && DiscordAuth::isAdmin();
$userId = DiscordAuth::getUserId();

$canView = $isAdmin || ($isLoggedIn && $userId == $transcript['user_id']);

if (!$canView) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Zugriff verweigert - <?= SITE_NAME ?></title>
        <link rel="icon" type="image/png" href="https://bloodline.cc/img/bloodline/bl_transparent.png">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error-page">
                <h1>403</h1>
                <p>Du hast keinen Zugriff auf dieses Transcript.</p>
                <?php if (!$isLoggedIn): ?>
                    <a href="login.php" class="btn btn-primary">Mit Discord anmelden</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary">Zurück zur Übersicht</a>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// HTML-Content ausgeben
// Gespeichertes Transcript-HTML in eigener (opaker) Origin rendern: Skripte des
// Transcript-Generators laufen weiter, haben aber keinen Zugriff auf Cookies/Session
// und keine Same-Origin-Requests gegen bloodline.cc (Panel/API).
header('Content-Type: text/html; charset=utf-8');
header('Content-Security-Policy: sandbox allow-scripts allow-popups allow-popups-to-escape-sandbox allow-downloads');
header('X-Content-Type-Options: nosniff');
echo $transcript['html_content'];
