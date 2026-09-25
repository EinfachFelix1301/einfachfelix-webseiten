# einfachfelix — Webseiten

Alle Webseiten, die ich für den FiveM-Roleplay-Server **Bloodline** gebaut habe — in einem Repo.

> Das Projekt ist abgeschlossen und wird nicht mehr aktiv weiterentwickelt.
> Der Code liegt hier als Referenz/Portfolio. Es sind **keine** Zugangsdaten, Tokens, Datenbank-IDs
> oder Server-Configs enthalten — alles, was du zum Aufsetzen brauchst, steht als Vorlage (`*.example.*`) drin.

## Was drin ist

| Ordner | Webseite | Technik |
|---|---|---|
| [`bloodline.cc/`](bloodline.cc/) | **Hauptseite mit Regelwerk** — Single-Page mit allen Server-Regeln in Tabs, Änderungen live aus dem Regelwerk-Portal | HTML/CSS/JS |
| [`bloodline.cc/changelog/`](bloodline.cc/changelog/) | **Changelog** — Server-Updates aus einer JSON-Datei | HTML/JS |
| [`bloodline.cc/img/`](bloodline.cc/img/) | **Media-Gallery** — Bilder-Hosting fürs In-Game-Handy: Discord-Login, Rollen & Rechte, Ordner, Bild-Anträge, Upload-API fürs Handy, Loadingscreen-API | PHP 8 |
| [`bloodline.cc/transcript/`](bloodline.cc/transcript/) | **Ticket-Transcripts** — Archiv der Discord-Tickets, Upload per API vom Bot, Ansicht mit Discord-Login | PHP 8, MySQL |
| [`doj/`](doj/) | **Gesetzbuch + DOJ-Verwaltung** — öffentliches Gesetzbuch mit Bußgeldkatalog, Verwaltung mit Editor, Zugangs-Anträgen, Änderungsvorschlägen und Benutzerverwaltung | Cloudflare Worker + D1 |
| [`regelwerk/`](regelwerk/) | **Regelwerk-Portal** — Team-Tool zum Pflegen des Regelwerks (Editor, Vorschläge, Benutzer), liefert die Regeln per API an die Hauptseite | Cloudflare Worker + D1 |

Jeder Bereich hat eine eigene README mit den Setup-Schritten:
[`bloodline.cc/README.md`](bloodline.cc/README.md) · [`doj/README.md`](doj/README.md) · [`regelwerk/README.md`](regelwerk/README.md)

## Schnellstart

```bash
git clone https://github.com/EinfachFelix1301/einfachfelix-webseiten.git
cd einfachfelix-webseiten
```

- **Statische Seiten** (`bloodline.cc/index.html`, `changelog/`): direkt im Browser öffnen oder auf einen beliebigen Webspace legen.
- **PHP-Seiten** (`img/`, `transcript/`): Webspace mit PHP 8 + Apache, `config.example.php` nach `config.php` kopieren und ausfüllen.
- **Worker** (`doj/`, `regelwerk/`): Cloudflare-Account + `npx wrangler`, D1-Datenbank anlegen, Migrationen ausführen, deployen.

## Anpassen

Domains, Discord-IDs und Texte sind auf Bloodline zugeschnitten. In den Vorlagen stehen Platzhalter
(`example.com`, `DEINE_ID`, `REPLACE_WITH_D1_ID`, …), die durch eigene Werte ersetzt werden. Fest verdrahtete
Links auf `bloodline.cc` in HTML/JS (z. B. die API-URL des Regelwerks) per Suchen & Ersetzen anpassen.

## Sicherheit

- Echte Configs sind gitignored: `**/config.php`, `settings.json`, `keys.json`, `whitelist.json`, `.dev.vars`, Uploads und Transcripts.
- Worker-Secrets gehören in `wrangler secret put …`, nie in `wrangler.toml`.
- Der erste Admin der Worker wird nur angelegt, wenn das Secret `BOOTSTRAP_ADMIN_PASSWORD` gesetzt ist.
- Login-Bremse der Worker: nach 10 Fehlversuchen pro IP in 10 Minuten antwortet `/api/login` mit `429` (Tabelle `login_attempts`, kommt mit den Migrationen).
- Fremd-HTML aus Änderungsvorschlägen wird serverseitig (HTMLRewriter) und im Browser (DOMPurify) bereinigt.
- Transcripts: `api/get.php` nur mit Discord-Login (Admins alles, sonst nur eigene Tickets).
