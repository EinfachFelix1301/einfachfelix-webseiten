-- 0013_fix_lvo_gg_headings.sql
-- Korrektur fehlerhafter Ueberschriften (Folgefehler aus 0011):
--   LVO hatte bereits ein §8 als "8 Flugerlaubnis" (ohne §) -> 0011 fuegte faelschlich
--   ein zweites "§ 8 Fluglizenz" ein (Dublette). Plus leeres "§ "-Heading.
--   1) Leeres "<h2>§ </h2>" entfernen.
--   2) "8 Flugerlaubnis" -> "§ 8 Flugerlaubnis" (echtes §8).
--   3) Dubletten-Block "§ 8 Fluglizenz" (aus 0011) wieder entfernen.
--   4) GG: leeres "<h2><strong> </strong></h2>" am Anfang entfernen.

UPDATE laws SET
  html = REPLACE(
           REPLACE(
             REPLACE(html, '<h2>§ </h2>', ''),
             '<h2>8 Flugerlaubnis </h2>',
             '<h2>§ 8 Flugerlaubnis</h2>'
           ),
           '<h2>§ 8 Fluglizenz</h2><p>(1) Das Führen eines Luftfahrzeugs ist ausschließlich mit einer gültigen Fluglizenz gestattet.</p><p>(2) Bei schwerwiegender oder wiederholter Missachtung dieser Verordnung kann die Fluglizenz entzogen werden.</p>',
           ''
         ),
  updated_at = 1782518400004, updated_by = 'system'
WHERE slug = 'luftverkehrs-ordnung-lvo';

UPDATE laws SET
  html = REPLACE(html, '<h2><strong> </strong></h2>', ''),
  updated_at = 1782518400004, updated_by = 'system'
WHERE slug = 'grundgesetz-los-santos-gg';
