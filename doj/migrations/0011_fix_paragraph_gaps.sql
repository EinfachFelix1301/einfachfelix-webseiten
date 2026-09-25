-- 0011_fix_paragraph_gaps.sql
-- Luecken in der Paragraphen-Folge schliessen, damit die Nummerierung Sinn ergibt.
-- Die fehlenden §§ waren geloeschte Paragraphen, die der Bussgeldkatalog noch referenziert
-- (StVO §14 Rechtsfahrgebot, LVO §8 Fluglizenz). Daher: wiederherstellen statt umnummerieren.
--   StVO: §13 -> §15  => §14 Rechtsfahrgebot wieder eingefuegt.
--   LVO:  §7  -> §9   => §8 Fluglizenz wieder eingefuegt.
--   WaffG:§10 -> §12  => falsch als <h3> getaggter Abs.(11) zu <p>; §11 Waffen der Kategorie D eingefuegt.
--   BtmG: §6  -> §9   => §9 Strafvorschriften in §7 umbenannt (kein §7/§8 vorhanden/referenziert).

-- StVO: §14 Rechtsfahrgebot ------------------------------------------------
UPDATE laws SET
  html = REPLACE(html,
    '<h2><strong>§15 Gefährliches Fahrverhalten</strong></h2>',
    '<h2><strong>§ 14 Rechtsfahrgebot</strong></h2><p>(1) Fahrzeuge haben grundsätzlich so weit rechts wie möglich zu fahren. Die linke Fahrspur ist dem Überhol- und Vorbeifahrvorgang vorbehalten.</p><p>(2) Ein Verstoß gegen das Rechtsfahrgebot wird gemäß Bußgeldkatalog geahndet.</p><h2><strong>§ 15 Gefährliches Fahrverhalten</strong></h2>'),
  updated_at = 1782518400003, updated_by = 'system'
WHERE slug = 'strassenverkehrs-ordnung-stvo';

-- LVO: §8 Fluglizenz ------------------------------------------------------
UPDATE laws SET
  html = REPLACE(html,
    '<h2>§ 9 Gefährliches Flugverhalten</h2>',
    '<h2>§ 8 Fluglizenz</h2><p>(1) Das Führen eines Luftfahrzeugs ist ausschließlich mit einer gültigen Fluglizenz gestattet.</p><p>(2) Bei schwerwiegender oder wiederholter Missachtung dieser Verordnung kann die Fluglizenz entzogen werden.</p><h2>§ 9 Gefährliches Flugverhalten</h2>'),
  updated_at = 1782518400003, updated_by = 'system'
WHERE slug = 'luftverkehrs-ordnung-lvo';

-- WaffG: Abs.(11) korrigieren + §11 Waffen der Kategorie D ----------------
UPDATE laws SET
  html = REPLACE(html,
    '<h3>(11) Die Ausgabe von Schusswaffen der Kategorie C ist ausschließlich der Behörde oder den lizenzierten Händlern gestattet.</h3><h3><strong>§ 12 Erwerb, Besitz und Führen von Waffen</strong></h3>',
    '<p>(11) Die Ausgabe von Schusswaffen der Kategorie C ist ausschließlich der Behörde oder den lizenzierten Händlern gestattet.</p><h3><strong>§ 11 Waffen der Kategorie D</strong></h3><p>(1) Zur Kategorie D gehören alle vollautomatischen Waffen sowie Schrotwaffen.</p><p>(2) Der Erwerb, das Einführen, der Handel und das Führen von Waffen der Kategorie D sind grundsätzlich verboten.</p><p>(3) Das Führen solcher Waffen ist ausschließlich den staatlichen Institutionen der Exekutive vorbehalten und bedarf einer behördlichen Bewilligung.</p><p>(4) Ein Verstoß wird nach § 15 WaffG Straftaten geahndet; die betreffende Waffe wird eingezogen.</p><h3><strong>§ 12 Erwerb, Besitz und Führen von Waffen</strong></h3>'),
  updated_at = 1782518400003, updated_by = 'system'
WHERE slug = 'waffengesetz-waffg';

-- BtmG: §9 -> §7 ----------------------------------------------------------
UPDATE laws SET
  html = REPLACE(html, '<h2><strong>§ 9 Strafvorschriften</strong></h2>', '<h2><strong>§ 7 Strafvorschriften</strong></h2>'),
  text = REPLACE(text, '§ 9 Strafvorschriften', '§ 7 Strafvorschriften'),
  updated_at = 1782518400003, updated_by = 'system'
WHERE slug = 'betaubungsmittelgesetz-btmg';
