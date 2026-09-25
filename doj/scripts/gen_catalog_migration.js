// Generiert Migration 0010: DB-Katalog 1:1 aus public/bussgeldkatalog.js + los-santos-tabelle.js,
// plus WaffG Kat. E entfernen. Output -> migrations/0010_sync_catalog_remove_katE.sql
const fs = require("fs");
const path = require("path");

const root = path.join(__dirname, "..");
const win = {};
global.window = win;
new Function("window", fs.readFileSync(path.join(root, "public/bussgeldkatalog.js"), "utf8"))(win);
new Function("window", fs.readFileSync(path.join(root, "public/los-santos-tabelle.js"), "utf8"))(win);

const KAT = win.BUSSGELDKATALOG || [];
const TAB = win.LOS_SANTOS_TABELLE || [];

const esc = (s) => String(s ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
const cell = (s) => esc(s).replace(/\n/g, "<br>");
const sqlq = (s) => String(s).replace(/'/g, "''"); // SQLite single-quote escape

const ts = 1782518400002;

let html = `<h2><strong>Bußgeldkatalog Los Santos</strong></h2>`;
html += `<p>Hafteinheit (HE) = 1 Minute · Maximalhaft 45 Min · in besonders schweren Fällen bis 60 Min mit Genehmigung durch Polizei Rang 10+</p>`;

const lines = ["Bußgeldkatalog Los Santos"];

for (const sec of KAT) {
  html += `<h3>[${esc(sec.code)}] ${esc(sec.title)}</h3>`;
  html += `<table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody>`;
  lines.push(`[${sec.code}] ${sec.title}`);
  for (const e of sec.entries) {
    html += `<tr><td>${cell(e.paragraph)}</td><td>${cell(e.bedeutung)}</td><td>${cell(e.strafe)}</td><td>${cell(e.he)}</td><td>${cell(e.sonstiges)}</td></tr>`;
    lines.push([e.paragraph, e.bedeutung, e.strafe, e.he, e.sonstiges].filter(Boolean).join(" | "));
  }
  html += `</tbody></table>`;
}

// Schadensersatz / ZIV
html += `<h3>[ZIV] Schadensersatztabelle (zivilrechtliche Ansprüche)</h3>`;
html += `<p>Höchstwerte für zivilrechtliche Schadenersatzansprüche. Berechnung als Vielfaches des Höchst-Bußgeldes der jeweiligen Rechtsgrundlage.</p>`;
html += `<table><thead><tr><th>Rechtsgrundlage</th><th>Tatbestand</th><th>Anspruchshöhe</th><th>Bemerkung</th></tr></thead><tbody>`;
lines.push("[ZIV] Schadensersatztabelle");
for (const e of TAB) {
  html += `<tr><td>${cell(e.rechtsgrundlage)}</td><td>${cell(e.tatbestand)}</td><td>${cell(e.anspruch)}</td><td>${cell(e.bemerkung)}</td></tr>`;
  lines.push([e.rechtsgrundlage, e.tatbestand, e.anspruch, e.bemerkung].filter(Boolean).join(" | "));
}
html += `</tbody></table>`;

const text = lines.join("\n");

const outName = process.argv[2] || "0010_sync_catalog_remove_katE.sql";
const sql = `-- ${outName}
-- AUTO-GENERIERT von scripts/gen_catalog_migration.js
-- DB-Bussgeldkatalog (Archiv, Kategorie "Katalog") 1:1 aus public/bussgeldkatalog.js
-- + public/los-santos-tabelle.js neu aufgebaut -> identisch zur angezeigten Version.

UPDATE laws
SET html = '${sqlq(html)}',
    text = '${sqlq(text)}',
    word_count = ${text.split(/\s+/).filter(Boolean).length},
    updated_at = ${ts},
    updated_by = 'system'
WHERE slug = 'bussgeldkatalog-los-santos';
`;

fs.writeFileSync(path.join(root, "migrations/" + outName), sql, "utf8");
console.log("written migrations/" + outName + "  (html " + html.length + " chars, " + KAT.length + " sections, " + TAB.length + " ZIV rows)");
