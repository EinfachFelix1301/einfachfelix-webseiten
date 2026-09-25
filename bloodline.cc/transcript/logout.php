<?php
/**
 * Logout
 */

session_start();

require_once __DIR__ . '/includes/auth.php';

DiscordAuth::logout();

header('Location: index.php');
exit;
