<?php
/**
 * Discord Login initiieren
 */

session_start();

require_once __DIR__ . '/auth.php';

// Bereits eingeloggt?
if (DiscordAuth::isLoggedIn()) {
    header('Location: ./');
    exit;
}

// Redirect zu Discord OAuth
header('Location: ' . DiscordAuth::getLoginUrl());
exit;
