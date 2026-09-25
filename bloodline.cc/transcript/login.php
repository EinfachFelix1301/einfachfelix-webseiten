<?php
/**
 * Discord Login initiieren
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Bereits eingeloggt?
if (DiscordAuth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Redirect zu Discord OAuth
header('Location: ' . DiscordAuth::getLoginUrl());
exit;
