-- 0009_fix_numbering_terms_catalog.sql
-- Inhaltliche Korrekturen (surgical REPLACE, damit Redaktion unberuehrt bleibt):
--   1) UZwGE § 7: Absatz-Nummern (1)->(3)->(4)->(5) auf (1)-(4) gerade gezogen,
--      unvollendeten Satz in Abs. (3) [vorher (4)] vervollstaendigt.
--   2) AtG § 2: fehlende Nummer (3) ergaenzt ((4) -> (3)).
--   3) BeamtStG: "Bundeswehr / Feldsanitaeter" -> LSMD (kein Militaer, nur US-Police/Medical).
--   4) OWiG: "Soldat" -> "Polizist".
--   5) DB-Bussgeldkatalog: Wiederholungs-Strafe 100.000EUR -> 5.000EUR (war nicht gewollt).

-- 1) UZwGE § 7 ---------------------------------------------------------------
UPDATE laws
SET
  html = REPLACE(
           REPLACE(
             REPLACE(
               html,
               '<p>(3) Wenn eine Person, zur Vereitelung der Flucht',
               '<p>(2) Wenn eine Person, zur Vereitelung der Flucht'
             ),
             '<p>(4) Gegen eine Person, die mit Gewalt einen Gefangenen',
             '<p>(3) Gegen eine Person, die mit Gewalt einen Gefangenen'
           ),
           'versucht.</li></ol><p>(5) Schusswaffen dürfen gegen eine Menschenmenge nur dann gebraucht',
           'versucht.</li></ol><p>ist der Gebrauch von Schusswaffen seitens der Vollzugsbeamten zulässig.</p><p>(4) Schusswaffen dürfen gegen eine Menschenmenge nur dann gebraucht'
         ),
  updated_at = 1782518400001,
  updated_by = 'system'
WHERE slug = 'unmittelbarer-zwang-der-exekutive-uzwge';

-- 2) AtG § 2 -----------------------------------------------------------------
UPDATE laws
SET
  html = REPLACE(
           html,
           '<p>(4) Sollten Personen ins SG eingeliefert werden, über die ein Terrorstatus',
           '<p>(3) Sollten Personen ins SG eingeliefert werden, über die ein Terrorstatus'
         ),
  updated_at = 1782518400001,
  updated_by = 'system'
WHERE slug = 'antiterrorgesetz-atg';

-- 3) BeamtStG: Bundeswehr/Feldsanitaeter -> LSMD ----------------------------
UPDATE laws
SET
  html = REPLACE(
           html,
           'Mitarbeitern und Beamten der Bundeswehr, welche im medizinischen Dienst mit Ausnahme der Feldsanitäter tätig sind',
           'Mitarbeitern und Beamten des Los Santos Medical Department (LSMD), welche im medizinischen Dienst tätig sind'
         ),
  text = REPLACE(
           text,
           'Mitarbeitern und Beamten der Bundeswehr, welche im medizinischen Dienst mit Ausnahme der Feldsanitäter tätig sind',
           'Mitarbeitern und Beamten des Los Santos Medical Department (LSMD), welche im medizinischen Dienst tätig sind'
         ),
  updated_at = 1782518400001,
  updated_by = 'system'
WHERE slug = 'beamtenstatusgesetz-beamtstg';

-- 4) OWiG: Soldat -> Polizist -----------------------------------------------
UPDATE laws
SET
  html = REPLACE(html, 'der Amtsträger oder der Soldat zuständig ist', 'der Amtsträger oder der Polizist zuständig ist'),
  text = REPLACE(text, 'der Amtsträger oder der Soldat zuständig ist', 'der Amtsträger oder der Polizist zuständig ist'),
  updated_at = 1782518400001,
  updated_by = 'system'
WHERE slug = 'ordnungswidrigkeitengesetz-owig';

-- 5) DB-Bussgeldkatalog: 100.000EUR -> 5.000EUR -----------------------------
UPDATE laws
SET
  html = REPLACE(html, 'wiederholter Tat bis zu 100.000€', 'wiederholter Tat bis zu 5.000€'),
  text = REPLACE(text, 'wiederholter Tat bis zu 100.000€', 'wiederholter Tat bis zu 5.000€'),
  updated_at = 1782518400001,
  updated_by = 'system'
WHERE slug = 'bussgeldkatalog-los-santos';
