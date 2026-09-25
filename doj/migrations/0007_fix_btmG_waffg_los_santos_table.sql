-- 0007_fix_btmG_waffg_los_santos_table.sql
-- Align fine catalog references with law text and include the civil Los Santos table in the catalog record.

UPDATE laws
SET
  html = REPLACE(REPLACE(html, 'Eigenbedarf (§ 8)', 'Eigenbedarf (§ 5)'), 'Eigenbedarf (nach § 8)', 'Eigenbedarf (nach § 5)'),
  text = REPLACE(REPLACE(text, 'Eigenbedarf (§ 8)', 'Eigenbedarf (§ 5)'), 'Eigenbedarf (nach § 8)', 'Eigenbedarf (nach § 5)'),
  updated_at = 1778602374064,
  updated_by = 'system'
WHERE slug = 'betaubungsmittelgesetz-btmg';

UPDATE laws
SET
  html = REPLACE(
    html,
    '<p><em>Sollte ein Paragraph rechtliche Fehler aufweisen',
    '<h2><strong>§ 9 Strafvorschriften</strong></h2><p>(1a) Wer mit Betäubungsmitteln handelt, ohne zugleich im Besitz einer schriftlichen Erlaubnis für den Erwerb oder den Handel zu sein.</p><p>(1b) Wer für Betäubungsmittel wirbt.</p><p>(1c) Wer unrichtige oder unvollständige Angaben macht, um für sich oder einen anderen die Verschreibung eines Betäubungsmittels zu erlangen.</p><p>(1d) Wer Betäubungsmittel über dem Eigenbedarf nach § 5 besitzt.</p><p><em>Sollte ein Paragraph rechtliche Fehler aufweisen'
  ),
  text = REPLACE(
    text,
    'Sollte ein Paragraph rechtliche Fehler aufweisen',
    '§ 9 Strafvorschriften

(1a) Wer mit Betäubungsmitteln handelt, ohne zugleich im Besitz einer schriftlichen Erlaubnis für den Erwerb oder den Handel zu sein.

(1b) Wer für Betäubungsmittel wirbt.

(1c) Wer unrichtige oder unvollständige Angaben macht, um für sich oder einen anderen die Verschreibung eines Betäubungsmittels zu erlangen.

(1d) Wer Betäubungsmittel über dem Eigenbedarf nach § 5 besitzt.

Sollte ein Paragraph rechtliche Fehler aufweisen'
  ),
  word_count = 573,
  ref_count = 7,
  updated_at = 1778602374064,
  updated_by = 'system'
WHERE slug = 'betaubungsmittelgesetz-btmg'
  AND html NOT LIKE '%§ 9 Strafvorschriften%';

UPDATE laws
SET
  html = REPLACE(html, '§1ff.', '§ 1 ff.'),
  text = REPLACE(text, '§1ff.', '§ 1 ff.'),
  updated_at = 1778602374064,
  updated_by = 'system'
WHERE slug = 'waffengesetz-waffg';

UPDATE laws
SET
  html = html || '<section class="sheet-block"><h2>Schadensersatztabelle Los Santos</h2><table><tr><th>Rechtsgrundlage</th><th>Tatbestand</th><th>Anspruchshöhe</th><th>Bemerkung</th></tr><tr><td>§3 OWiG</td><td>Sachbeschädigung</td><td>bis zum 15-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§4 OWiG</td><td>Beleidigung</td><td>bis zum 20-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§16 OWiG</td><td>Üble Nachrede</td><td>bis zum 40-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§17 OWiG</td><td>Diebstahl</td><td>bis zum 40-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 10 % der gestohlenen Summe sowie Ersatz des materiellen Schadens</td></tr><tr><td>§18 OWiG</td><td>Dokumentenfälschung</td><td>bis zum 25-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§15 StGB</td><td>Betrug</td><td>bis zum 75-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 10 % der betrogenen Summe sowie Ersatz des materiellen Schadens</td></tr><tr><td>§16 StGB</td><td>Schwere Körperverletzung</td><td>bis zum 50-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 24-stündiger Verdienstausfall des festen Gehalts — maximal 2 Mio.</td></tr><tr><td>§17 StGB</td><td>Versuchter Mord</td><td>bis zum 10-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§30 StGB</td><td>Brandstiftung</td><td>bis zum 25-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§2 DSGVO</td><td>Verschwiegenheit Interna</td><td>bis zum 25-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§3 DSGVO</td><td>Recht am geistigen Eigentum</td><td>bis zum 50-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§4 DSGVO</td><td>Aufnahme ohne Einwilligung</td><td>bis zum 30-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§5 DSGVO</td><td>Betriebs- oder Geschäftsgeheimnisse offenbart</td><td>bis zum 15-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§5 HGB</td><td>Betreiben eines Unternehmens ohne Lizenz</td><td>bis zum 10-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 5-fache monatliche Lizenzkosten — nur von Konkurrenten einzuklagen</td></tr><tr><td>§12 HGB</td><td>Verstoß gegen Markenschutz (Waren)</td><td>bis zum 10-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 3-fache 3-monatliche Lizenzkosten — nur von Lizenzinhaber einzuklagen</td></tr></table></section>',
  text = text || '

Schadensersatztabelle Los Santos
Rechtsgrundlage | Tatbestand | Anspruchshöhe | Bemerkung
§3 OWiG | Sachbeschädigung | bis zum 15-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§4 OWiG | Beleidigung | bis zum 20-fachen Höchst-Bußgeld |
§16 OWiG | Üble Nachrede | bis zum 40-fachen Höchst-Bußgeld |
§17 OWiG | Diebstahl | bis zum 40-fachen Höchst-Bußgeld | zzgl. bis zu 10 % der gestohlenen Summe sowie Ersatz des materiellen Schadens
§18 OWiG | Dokumentenfälschung | bis zum 25-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§15 StGB | Betrug | bis zum 75-fachen Höchst-Bußgeld | zzgl. bis zu 10 % der betrogenen Summe sowie Ersatz des materiellen Schadens
§16 StGB | Schwere Körperverletzung | bis zum 50-fachen Höchst-Bußgeld | zzgl. bis zu 24-stündiger Verdienstausfall des festen Gehalts — maximal 2 Mio.
§17 StGB | Versuchter Mord | bis zum 10-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§30 StGB | Brandstiftung | bis zum 25-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§2 DSGVO | Verschwiegenheit Interna | bis zum 25-fachen Höchst-Bußgeld |
§3 DSGVO | Recht am geistigen Eigentum | bis zum 50-fachen Höchst-Bußgeld |
§4 DSGVO | Aufnahme ohne Einwilligung | bis zum 30-fachen Höchst-Bußgeld |
§5 DSGVO | Betriebs- oder Geschäftsgeheimnisse offenbart | bis zum 15-fachen Höchst-Bußgeld |
§5 HGB | Betreiben eines Unternehmens ohne Lizenz | bis zum 10-fachen Höchst-Bußgeld | zzgl. bis zu 5-fache monatliche Lizenzkosten — nur von Konkurrenten einzuklagen
§12 HGB | Verstoß gegen Markenschutz (Waren) | bis zum 10-fachen Höchst-Bußgeld | zzgl. bis zu 3-fache 3-monatliche Lizenzkosten — nur von Lizenzinhaber einzuklagen',
  word_count = word_count + 213,
  updated_at = 1778602374064,
  updated_by = 'system'
WHERE slug = 'bussgeldkatalog-los-santos'
  AND html NOT LIKE '%Schadensersatztabelle Los Santos%';
