<?php
/**
 * Konfiguration für das Transcript-System
 * WICHTIG: Diese Datei enthält sensible Daten!
 */

// Datenbank-Konfiguration
define("DB_HOST", "localhost");
define("DB_NAME", "transcripts");
define("DB_USER", "transcript");
define("DB_PASS", "CHANGE_ME_DB_PASS");

// API-Konfiguration
define("API_KEY", "CHANGE_ME_API_KEY"); // Wird vom Bot zum Upload verwendet

// Discord OAuth2 Konfiguration
define("DISCORD_CLIENT_ID", "DEINE_DISCORD_CLIENT_ID");
define("DISCORD_CLIENT_SECRET", "CHANGE_ME_DISCORD_CLIENT_SECRET");
define("DISCORD_REDIRECT_URI", "https://example.com/transcript/callback.php");

// Seiten-Konfiguration
define("SITE_URL", "https://example.com/transcript");
define("SITE_NAME", "Bloodline Transcripts");

// Admin Discord IDs (können alle Transcripts sehen)
// WICHTIG: IDs als Strings definieren wegen 64-bit Discord Snowflakes
define("ADMIN_IDS", ["123456789012345678"]);

// Session-Konfiguration
ini_set("session.cookie_httponly", 1);
ini_set("session.cookie_secure", 1);
ini_set("session.use_strict_mode", 1);
