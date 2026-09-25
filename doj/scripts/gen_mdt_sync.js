// Sync MDT-Bussgeldkatalog (D1 items, appKey=bussgeldkatalog) mit Gesetze public/bussgeldkatalog.js.
// Erhaelt vorhandene id + typ (Match ueber gesetz+bedeutung); neue Eintraege bekommen frische id + typ-Default.
// Output -> <MDT_DIR>/migrations/015_sync_bussgeldkatalog.sql
// Pfade per Umgebungsvariable überschreibbar: GESETZE_DIR (dieses doj/-Projekt), MDT_DIR (MDT-Resource).
const fs = require("fs");
const path = require("path");

const GES = process.env.GESETZE_DIR || path.resolve(__dirname, "..");
const MDT = process.env.MDT_DIR || path.resolve(__dirname, "../../mdt");

const win = {};
new Function("window", fs.readFileSync(GES + "/public/bussgeldkatalog.js", "utf8"))(win);
const KAT = win.BUSSGELDKATALOG;

const cur = JSON.parse(fs.readFileSync(GES + "/scripts/_mdt_items.json", "utf8"))[0].results
  .map((r) => JSON.parse(r.data));

// Index bestehende: key = gesetz|bedeutung  (bedeutung = name ohne fuehrendes paragraph)
const norm = (s) => String(s || "").replace(/\s+/g, " ").trim().toLowerCase();
const bedeutungOf = (it) => {
  let n = String(it.name || "");
  if (it.paragraph && n.startsWith(it.paragraph)) n = n.slice(it.paragraph.length);
  return norm(n.replace(/^[-/|:\s]+/, ""));
};
const existing = new Map();          // key -> {id, typ}
const idsByCode = {};                // code -> max numeric suffix
for (const it of cur) {
  existing.set(it.gesetz + "|" + bedeutungOf(it), { id: it.id, typ: it.typ });
  const m = String(it.id).match(/_(\d+)$/);
  if (m) idsByCode[it.gesetz] = Math.max(idsByCode[it.gesetz] || -1, parseInt(m[1], 10));
}

const typDefault = (code) => ({ WaffG: "Waffen", StVO: "Verkehr", LVO: "Verkehr", StGb: "Gewalt" }[code] || "andere");

const nums = (s, money) => {
  s = String(s ?? "");
  let toks;
  if (money) toks = (s.match(/\d[\d.]*/g) || []).map((t) => parseInt(t.replace(/\./g, ""), 10));
  else toks = (s.match(/\d+/g) || []).map((t) => parseInt(t, 10));
  toks = toks.filter((n) => Number.isFinite(n));
  if (!toks.length) return [0, 0];
  return [Math.min(...toks), Math.max(...toks)];
};

const ts = "2026-06-26T12:00:00.000Z";
const sqlq = (s) => String(s).replace(/'/g, "''");
const rows = [];
let reused = 0, created = 0;

for (const sec of KAT) {
  for (const e of sec.entries) {
    const bed = norm(e.bedeutung);
    const key = sec.code + "|" + bed;
    let id, typ;
    const hit = existing.get(key);
    if (hit) { id = hit.id; typ = hit.typ; reused++; }
    else { idsByCode[sec.code] = (idsByCode[sec.code] || -1) + 1; id = sec.code + "_" + idsByCode[sec.code]; typ = typDefault(sec.code); created++; }
    const [bMin, bMax] = nums(e.strafe, true);
    const [hMin, hMax] = nums(e.he, false);
    const obj = {
      id, name: (e.paragraph + " " + e.bedeutung).trim(), paragraph: e.paragraph, gesetz: sec.code,
      haftzeit: hMin, maxHaftzeit: hMax, bussgeld: bMin, maxBussgeld: bMax, typ, notiz: e.sonstiges || "",
    };
    rows.push(`INSERT INTO items (id, appKey, data, createdAt, updatedAt) VALUES ('${sqlq(id)}', 'bussgeldkatalog', '${sqlq(JSON.stringify(obj))}', '${ts}', '${ts}');`);
  }
}

const sql = `-- 015_sync_bussgeldkatalog.sql
-- AUTO-GENERIERT von Gesetze/scripts/gen_mdt_sync.js
-- MDT-Bussgeldkatalog 1:1 an Gesetze public/bussgeldkatalog.js angeglichen.
-- typ + id bestehender Eintraege erhalten (Match ueber gesetz+bedeutung); neue: frische id + typ-Default.
-- Reused: ${reused}, Neu: ${created}, Gesamt: ${rows.length}.

DELETE FROM items WHERE appKey='bussgeldkatalog';
${rows.join("\n")}
`;

fs.writeFileSync(MDT + "/migrations/015_sync_bussgeldkatalog.sql", sql, "utf8");
console.log("written 015_sync_bussgeldkatalog.sql  reused=" + reused + " new=" + created + " total=" + rows.length);
