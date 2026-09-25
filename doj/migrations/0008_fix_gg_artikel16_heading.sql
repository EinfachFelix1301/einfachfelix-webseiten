-- 0008_fix_gg_artikel16_heading.sql
-- Grundgesetz [GG]: Strukturfehler im HTML reparieren.
--   1) Die Klausel "(2) Wegen einer mit Strafe bedrohten Handlung…" war faelschlich als
--      <h3>-Ueberschrift ausgezeichnet -> tauchte als Muell-Eintrag im Inhaltsverzeichnis auf.
--   2) "Artikel 16" war ein <p><strong> statt einer <h2>-Ueberschrift -> fehlte komplett im TOC.
-- Surgical REPLACE, damit spaetere redaktionelle Aenderungen unberuehrt bleiben.

UPDATE laws
SET
  html = REPLACE(
    html,
    '<h3>(2) Wegen einer mit Strafe bedrohten Handlung darf ein Regierungsmitglied erst nach einer Verurteilung durch einen Richter suspendiert oder des Amtes enthoben werden.</h3><p><strong>Artikel 16</strong></p><p>[Demokratische Grundordnung]</p>',
    '<p>(2) Wegen einer mit Strafe bedrohten Handlung darf ein Regierungsmitglied erst nach einer Verurteilung durch einen Richter suspendiert oder des Amtes enthoben werden.</p><h2>Artikel 16</h2><p><em>[Demokratische Grundordnung]</em></p>'
  ),
  updated_at = 1782518400000,
  updated_by = 'system'
WHERE slug = 'grundgesetz-los-santos-gg';
