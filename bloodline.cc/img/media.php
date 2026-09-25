<?php
// Alte Media-Seite -> ersetzt durch die neue Landing (index.php)
require_once __DIR__ . '/config.php';
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
header('Location: ' . ($base === '' ? '/' : $base . '/') . '#einreichen');
exit;
