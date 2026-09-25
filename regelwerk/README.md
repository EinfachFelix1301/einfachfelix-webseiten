# Regelwerk — Portal + statische Seite

Team-Tool zum Pflegen des Server-Regelwerks, aufgebaut wie das [DOJ-Portal](../doj/):
ein **Cloudflare Worker** (`src/worker.js`) mit **D1** und statischen Assets (`public/`).

- **Portal** (`ADMIN_HOST`): Login, Editor für Regeln, Änderungsvorschläge, Benutzerverwaltung
- **API** `/api/laws`: liefert die Regeln inklusive der letzten Änderungen. Die [Hauptseite](../bloodline.cc/) zeigt sie im Tab „Änderungen" an.
- `index.html` im Ordner-Root: eigenständige, statische Regelwerk-Seite, läuft ohne Backend

Login per Benutzername + Passwort oder **Discord OAuth** (nur mit einer bestimmten Rolle in der Guild).

## Setup

Voraussetzungen: Node.js 18+, ein Cloudflare-Account und eine Domain in Cloudflare.

```bash
cd regelwerk
npm install
npx wrangler login

npm run db:create            # D1-Datenbank anlegen
```

1. Die `database_id` in `wrangler.toml` bei `REPLACE_WITH_D1_ID` eintragen.
2. Hosts (`routes`, `PUBLIC_HOST`, `ADMIN_HOST`) auf deine Domain setzen.
3. Für den Discord-Login `DISCORD_CLIENT_ID`, `DISCORD_GUILD_ID`, `DISCORD_ROLE_ID` und
   `DISCORD_REDIRECT_URI` (`https://<ADMIN_HOST>/auth/callback`) eintragen.
4. Migrationen, Secrets, Deploy:

```bash
npm run db:migrate
npx wrangler secret put BOOTSTRAP_ADMIN_PASSWORD    # Passwort für den ersten Admin "admin"
npx wrangler secret put DISCORD_CLIENT_SECRET       # nur mit Discord-Login
npm run deploy
```

Der Benutzer `admin` wird beim ersten Aufruf angelegt, solange noch kein Benutzer existiert. Das Passwort danach im Portal ändern.

## Lokal entwickeln

```bash
npm run db:migrate:local
echo 'BOOTSTRAP_ADMIN_PASSWORD="lokal-test-123"' > .dev.vars
npm run dev
```
