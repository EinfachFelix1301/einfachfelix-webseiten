-- Discord-Anzeigename (statt nur discord_<id>) fuer die Benutzer-Anzeige
ALTER TABLE users ADD COLUMN display_name TEXT;
