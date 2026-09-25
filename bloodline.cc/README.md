# bloodline.cc — Hauptseite, Changelog, Media-Gallery, Transcripts

## Hauptseite (`index.html`)

Single-Page mit dem kompletten Server-Regelwerk in Tabs und Unter-Tabs. Der Tab „Änderungen" lädt die letzten
Regel-Änderungen live aus dem [Regelwerk-Portal](../regelwerk/) (`/api/laws`).

**Setup:** Datei auf einen beliebigen Webspace legen und die API-URL des Regelwerk-Portals in `index.html` auf die eigene Domain ändern.

## Changelog (`changelog/`)

Liest `changelog.json` und zeigt die Updates als Timeline. Neue Einträge einfach in die JSON-Datei schreiben.

## Media-Gallery (`img/`)

Bilder-Hosting für das In-Game-Handy (lb-phone):

- Login mit Discord (OAuth2), Rollen mit Rechten (`view`, `upload`, `folders`, `antraege`, `settings`)
- Ordner anlegen, umbenennen und löschen, Drag-&-Drop-Upload, Direktlinks kopieren
- **Anträge:** Spieler reichen Bilder ein, das Team genehmigt sie in einen Zielordner
- `phone_upload.php`: Upload-Endpunkt für das Handy (API-Key im Header)
- `api/loadingscreen.php`: liefert Team-Liste und Changelog aus Discord für den FiveM-Loadingscreen (mit Cache)

**Voraussetzungen:** PHP 8.1+ mit `curl` und `fileinfo` (`pdo_mysql` nur für den Handy-Sync), Apache mit `mod_rewrite` und `mod_headers`.

**Setup:**

1. Ordner `img/` auf den Webspace kopieren.
2. `config.example.php` nach `config.php` kopieren und ausfüllen:
   - `DISCORD_CLIENT_ID` / `DISCORD_CLIENT_SECRET` aus dem [Discord Developer Portal](https://discord.com/developers/applications) → OAuth2.
     Als Redirect `https://deine-domain/img/callback.php` eintragen.
   - `DISCORD_BOT_TOKEN` (optional): nur für Anzeigenamen der User
   - `MEDIA_PUBLIC_BASE`: öffentliche URL des `img/`-Ordners
   - `GAME_DB_*` (optional): Datenbank des FiveM-Servers, wenn genehmigte Bilder direkt in die Handy-Galerie sollen
3. Ersten Admin anlegen: `settings.json` im `img/`-Ordner mit deiner Discord-ID erstellen.
   Die Rollen kannst du aus `defaultSettings()` in der `config.php` übernehmen:
   ```json
   {
     "roles": {
       "admin": { "label": "Admin", "color": "#ff3b3b", "perms": ["view", "upload", "folders", "antraege", "settings"] },
       "user":  { "label": "User",  "color": "#4caf50", "perms": ["view", "upload"] }
     },
     "users": { "123456789012345678": ["admin"] }
   }
   ```
   Weitere User und Rollen verwaltest du danach im Panel unter **Einstellungen**.
4. Für `phone_upload.php` die Umgebungsvariable `PHONE_API_KEY` setzen (z. B. `SetEnv PHONE_API_KEY …` im vHost).
   Ohne Key lehnt der Endpunkt alle Uploads ab.
5. Für den Loadingscreen `DISCORD_BOT_TOKEN` als Umgebungsvariable setzen und in `api/loadingscreen.php` Guild-, Channel- und Rollen-IDs eintragen.
6. Upload-Limits in `.user.ini` an `MAX_UPLOAD_SIZE` anpassen.

`settings.json`, `keys.json`, `antraege.json`, Uploads und Caches sind gitignored; `config.php`, `settings.json`, `keys.json` und `whitelist.json` sperrt zusätzlich die `.htaccess`.

## Ticket-Transcripts (`transcript/`)

Archiv für Discord-Ticket-Verläufe. Der [Discord-Bot](https://github.com/EinfachFelix1301/einfachfelix-discordbot)
lädt beim Schließen eines Tickets das HTML-Transcript per API hoch, Admins sehen die Transcripts nach dem Discord-Login.

**Setup:**

1. MySQL/MariaDB-Datenbank anlegen und `setup.sql` importieren:
   ```bash
   mysql -u root -p transcripts < setup.sql
   ```
2. `includes/config.example.php` nach `includes/config.php` kopieren und ausfüllen:
   - `DB_*`: Datenbank-Zugang
   - `API_KEY`: langer Zufallswert, derselbe kommt in die Bot-Config
   - `DISCORD_CLIENT_ID` / `DISCORD_CLIENT_SECRET` / `DISCORD_REDIRECT_URI` (`https://deine-domain/transcript/callback.php`)
   - `ADMIN_IDS`: Discord-IDs, die alle Transcripts sehen dürfen
3. Ordner `transcripts/` anlegen und für den Webserver-User beschreibbar machen.

> `callback.php` reicht OAuth-Antworten mit `rw_`- bzw. `doj_`-State an das Regelwerk- und DOJ-Portal weiter, damit
> alle drei Seiten eine Discord-App teilen können. Wer das nicht braucht, setzt in den Workern
> `DISCORD_REDIRECT_URI` direkt auf `https://<admin-host>/auth/callback`.
