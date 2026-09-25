<?php
/**
 * Logout
 */

session_start();

require_once __DIR__ . '/auth.php';

DiscordAuth::logout();

header('Location: ./');
exit;
