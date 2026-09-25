<?php
/**
 * Discord OAuth2 Callback
 */

session_start();

require_once __DIR__ . '/auth.php';

// Fehler prüfen
if (isset($_GET['error'])) {
    header('Location: ./?error=oauth');
    exit;
}

// Code prüfen
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($code)) {
    header('Location: ./?error=no_code');
    exit;
}

// State verifizieren (CSRF-Schutz)
if (!DiscordAuth::verifyState($state)) {
    header('Location: ./?error=invalid_state');
    exit;
}

// Token holen
$tokenData = DiscordAuth::getToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    header('Location: ./?error=token');
    exit;
}

// User-Daten holen
$user = DiscordAuth::getUser($tokenData['access_token']);

if (!$user || !isset($user['id'])) {
    header('Location: ./?error=user');
    exit;
}

// User einloggen (neue Session-ID gegen Session-Fixation)
session_regenerate_id(true);
DiscordAuth::login($user);

// Redirect zur Hauptseite
header('Location: ./');
exit;
