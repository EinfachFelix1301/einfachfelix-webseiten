<?php
/**
 * Transcript als HTML-Datei herunterladen
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

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
    echo "Transcript nicht gefunden.";
    exit;
}

// Zugriffsprüfung
$isLoggedIn = DiscordAuth::isLoggedIn();
$isAdmin = $isLoggedIn && DiscordAuth::isAdmin();
$userId = DiscordAuth::getUserId();

$canView = $isAdmin || ($isLoggedIn && $userId == $transcript['user_id']);

if (!$canView) {
    http_response_code(403);
    echo "Zugriff verweigert.";
    exit;
}

// Als Datei senden
$filename = "transcript-{$transcript['ticket_number']}.html";

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($transcript['html_content']));

echo $transcript['html_content'];
exit;
