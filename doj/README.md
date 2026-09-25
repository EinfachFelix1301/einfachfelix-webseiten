# DOJ — Gesetzbuch + Verwaltung

Gesetzbuch des Department of Justice für Bloodline. Ein einziger **Cloudflare Worker** (`src/worker.js`)
mit **D1**-Datenbank und statischen Assets (`public/`), der zwei Hosts bedient:

| Host (`wrangler.toml`) | Seite |
|---|---|
| `PUBLIC_HOST` | Öffentliches Gesetzbuch mit Bußgeldkatalog und Gesetzen als Word-Dokumente zum Download (nur lesen) |
| `ADMIN_HOST` | Verwaltung: Login, Editor für Gesetze und Bußgeldkatalog, Zugangs-Anträge, Änderungsvorschläge, Benutzer |

## Rollen & Ablauf

1. Wer mitarbeiten will, stellt in der Verwaltung unter **Zugang beantragen** einen Antrag.
2. Ein Admin nimmt ihn unter **Anträge** an, der Benutzer wird `editor`.
3. Editoren bearbeiten und erstellen Gesetze. Löschen sowie Benutzer und Anträge verwalten nur Admins.
4. Änderungsvorschläge landen bei den Admins, die sie übernehmen oder verwerfen.
5. Das Gesetzbuch liest live aus D1, ein Re-Deploy ist nicht nötig.

Login per Benutzername + Passwort oder **Discord OAuth** (Zugang über Rollen in einer Discord-Guild).

## Setup

Voraussetzungen: Node.js 18+, ein Cloudflare-Account und eine Domain in Cloudflare.

```bash
cd doj
npm install
npx wrangler login

npm run db:create            # D1-Datenbank anlegen
```

1. Die ausgegebene `database_id` in `wrangler.toml` bei `REPLACE_WITH_D1_ID` eintragen.
2. In `wrangler.toml` die Hosts (`routes`, `PUBLIC_HOST`, `ADMIN_HOST`) auf deine Domain setzen.
3. Für den Discord-Login `DISCORD_CLIENT_ID`, `DISCORD_GUILD_ID`, `DISCORD_ROLE_IDS` und `DISCORD_REDIRECT_URI`
   (`https://<ADMIN_HOST>/auth/callback`) eintragen. Ohne Discord-Login die Werte leer lassen.
4. Migrationen (Schema, vorbefüllte Gesetze und Bußgeldkatalog) ausführen, Secrets setzen, deployen:

```bash
npm run db:migrate
npx wrangler secret put BOOTSTRAP_ADMIN_PASSWORD    # Passwort für den ersten Admin "admin"
npx wrangler secret put DISCORD_CLIENT_SECRET       # nur mit Discord-Login
npx wrangler secret put BOT_SECRET                  # nur für Bot-Login: /api/bot/login liefert Einmal-Links (5 min gültig)

npm run deploy
```

Beim ersten Aufruf wird der Benutzer `admin` mit dem Passwort aus `BOOTSTRAP_ADMIN_PASSWORD` angelegt,
solange noch kein Benutzer existiert. Danach im Portal unter **Konto** ein eigenes Passwort setzen.

## Lokal entwickeln

```bash
npm run db:migrate:local
echo 'BOOTSTRAP_ADMIN_PASSWORD="lokal-test-123"' > .dev.vars
npm run dev
```

## Struktur

```
src/worker.js        API, Routing, Weiche zwischen Public- und Admin-Host
src/auth.js          Passwort-Hashing, Sessions, Bootstrap-Admin
public/              Gesetzbuch (index.html, app.js), Verwaltung (admin.html, admin.js), Bußgeldkatalog
public/documents/    Gesetze als Word-/Excel-Dateien
migrations/          D1-Schema und Seed-Daten
scripts/             Generatoren (Bußgeldkatalog-Migration, MDT-Sync)
```
