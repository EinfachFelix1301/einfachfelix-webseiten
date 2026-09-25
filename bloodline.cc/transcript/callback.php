<?php
/**
 * Discord OAuth2 Callback
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Fehler prüfen
if (isset($_GET['error'])) {
    header('Location: index.php?error=oauth');
    exit;
}

// Code prüfen
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code)) {
    header('Location: index.php?error=no_code');
    exit;
}

// Regelwerk-Login: OAuth über diese registrierte Redirect-URI durchschleifen.
// rw_-States gehören zum Regelwerk-Portal -> Code dorthin weiterreichen.
if (strpos($state, 'rw_') === 0) {
    header('Location: https://regelwerk.bloodline.cc/auth/callback?code=' . urlencode($code) . '&state=' . urlencode($state));
    exit;
}

// DOJ-Login: doj_-States gehören zum DOJ-Verwaltungsportal -> Code dorthin weiterreichen.
if (strpos($state, 'doj_') === 0) {
    header('Location: https://doj.bloodline.cc/auth/callback?code=' . urlencode($code) . '&state=' . urlencode($state));
    exit;
}

// State verifizieren (CSRF-Schutz)
if (!DiscordAuth::verifyState($state)) {
    header('Location: index.php?error=invalid_state');
    exit;
}

// Token holen
$tokenData = DiscordAuth::getToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    header('Location: index.php?error=token');
    exit;
}

// User-Daten holen
$user = DiscordAuth::getUser($tokenData['access_token']);

if (!$user || !isset($user['id'])) {
    header('Location: index.php?error=user');
    exit;
}

// User einloggen (neue Session-ID gegen Session-Fixation)
session_regenerate_id(true);
DiscordAuth::login($user);

// Redirect zur Hauptseite
header('Location: index.php');
exit;
