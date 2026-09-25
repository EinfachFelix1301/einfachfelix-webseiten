-- 0015_catalog.sql
-- Bussgeldkatalog als bearbeitbare D1-Tabellen (Sektionen + Eintraege).
-- Seed 1:1 aus public/bussgeldkatalog.js (Stand dieser Migration).

CREATE TABLE IF NOT EXISTS catalog_sections (
  code TEXT PRIMARY KEY,
  title TEXT NOT NULL,
  position INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS catalog_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL,
  paragraph TEXT,
  bedeutung TEXT NOT NULL,
  strafe TEXT,
  he TEXT,
  sonstiges TEXT,
  position INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_catalog_entries_code ON catalog_entries(code);

INSERT INTO catalog_sections (code, title, position) VALUES ('StGb', 'Strafgesetzbuch', 0);
INSERT INTO catalog_sections (code, title, position) VALUES ('WaffG', 'Waffengesetz', 1);
INSERT INTO catalog_sections (code, title, position) VALUES ('BtmG', 'Betäubungsmittelgesetz', 2);
INSERT INTO catalog_sections (code, title, position) VALUES ('StVO', 'Straßenverkehrsordnung', 3);
INSERT INTO catalog_sections (code, title, position) VALUES ('LVO', 'Luftverkehrsordnung', 4);
INSERT INTO catalog_sections (code, title, position) VALUES ('UZwGE', 'Unmittelbarer Zwang der Exekutive', 5);
INSERT INTO catalog_sections (code, title, position) VALUES ('OWiG', 'Ordnungswidrigkeitengesetz', 6);
INSERT INTO catalog_sections (code, title, position) VALUES ('AkG', 'Antikorruptionsgesetz', 7);

INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§13 Abs. 1', 'Raub', '$1.600', '10', NULL, 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§13 Abs. 2', 'Raub in besonders schweren Fall', '$2.400', '20', NULL, 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§14 Abs. 1', 'Erpressung', '$1.800', '5', NULL, 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§15 Abs. 1', 'Betrug', '$2.200', '5', NULL, 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§16 Abs. 1', 'Leichte Körperverletzung', '$600', '-', NULL, 4);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§16 Abs. 2', 'Gefährliche Körperverletzung', '$1.800', '5', NULL, 5);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§16 Abs. 3', 'Schwere Körperverletzung', '$2.800', '10', NULL, 6);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§16 Abs. 4', 'Fahrlässige Körperverletzung', '$1.200', '10', NULL, 7);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§17 Abs. 1', 'Versuchter Mord', '$5.000', '20', NULL, 8);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§17 Abs. 2', 'Mehrfach versuchter Mord / besondere Schwere der Tat', '$7.500', '25', NULL, 9);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§18 Abs. 1', 'Freiheitsentzug', '$3.000', '5', NULL, 10);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§19 Abs. 1', 'Verfassungswidrige Handlung', '$9.000', '10', NULL, 11);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§20 Abs. 1', 'Beweismittelfälschung', '$1.500', '15', NULL, 12);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§21 Abs. 1', 'Falsche Beweisaussage', '$1.500', '15', NULL, 13);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§21 Abs. 2', 'Meineid', '$2.000', '15', NULL, 14);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§22 Abs. 1', 'Entweichung von Gefangenen', 'doppelte reguläre Haftzeit und Geldstrafe', NULL, NULL, 15);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§23 Abs. 1', 'Beihilfe zum Gefängnisausbruch', '$1.800', '5', NULL, 16);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§24 Abs. 1', 'Strafvereitelung', '$1.800', '5', NULL, 17);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§25 Abs. 1', 'Geiselnahme', '$3.500', '5', NULL, 18);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§26 Abs. 1', 'Widerstand gegen Vollstreckungsbeamte', '$900', '2', NULL, 19);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§27 Abs. 2', 'Widerstand gegen Anweisungen', '$300', '-', NULL, 20);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§28 Abs. 1', 'Unbefugtes Betreten von Speerbezirken', '$250', '2', 'evtl. Platzverweis sollte die Person diesem nicht nachkommen = Haftzeit', 21);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§29 Abs. 1', 'Hehlerei', '$700', '2', NULL, 22);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§30 Abs. 1', 'Brandstiftung', '$1.500', '2', 'Aufkommen für den Sachschaden', 23);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StGb', '§31 Abs. 1', 'Nachstellung / Belästigung', '$400', '2', NULL, 24);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 1', 'WaffG Straftaten / widersetzen des Waffenverbotes', '$1.000', '15 - 20', 'Waffe/n einziehen', 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 2', 'WaffG Straftaten / widersetzen der Entwaffnung', '$1.500', NULL, 'Waffe/n einziehen', 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 3', 'Illegaler Waffenbesitz A', '$1.200', '15 - 20', 'Waffe/n einziehen', 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 4', 'Illegaler Waffenhandel A', '$2.200', '20 - 25', 'Waffe/n einziehen', 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 5', 'Illegaler Waffenbesitz B', '$2.500', '15 - 25', 'Waffe/n einziehen', 4);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 6', 'Illegaler Waffenhandel B', '$4.500', '25 - 30', 'Waffe/n einziehen', 5);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 7', 'Illegaler Waffenbesitz C', '$5.000', '15 - 30', 'Waffe/n einziehen', 6);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 8', 'Illegaler Waffenhandel C', '$8.000', '30 - 40', 'Waffe/n einziehen', 7);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 10', 'Illegaler Waffenbesitz D', '$6.000', '20 - 35', 'Waffe/n einziehen', 8);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 11', 'Illegaler Waffenhandel D', '$10.000', '35 - 45', 'Waffe/n einziehen', 9);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§15 Abs. 9', 'WaffG Straftaten / Zweckentfremdung der Waffenscheine', '$2.500', '15 - 20', NULL, 10);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§16 Abs. 1', 'Waffenteile', '$800', '15 - 20', 'Waffenteile einziehen', 11);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('WaffG', '§17 Abs. 6', 'WaffG Waffenscheininhaber / Verwenden einer Schusswaffe außerhalb der Schießstätte', '$1.800', '15 - 20', 'Waffenschein einziehen', 12);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('BtmG', '§7 Abs. 1a', 'Handel mit Betäubungsmitteln', '$1.000', '15 - 20', 'zzgl. $250 Aufschlag pro Drogeneinheit', 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('BtmG', '§7 Abs. 1b', 'Wer für Betäubungsmittel wirbt', '$800', '-', NULL, 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('BtmG', '§7 Abs. 1c', 'Falsche Angaben um an eine Verschreibung für Btm zu kommen', '$1.200', '-', NULL, 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('BtmG', '§7 Abs. 1d', 'Besitz von Btm über dem Eigenbedarf', '$900', '-', NULL, 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§2 Abs. 1', 'Fahren abseits befestigter Strassen', '$80', '-', NULL, 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§3 Abs. 1', 'Überschreiten der Höchstgeschw. 5-20 kmh', '$50', '-', NULL, 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§3 Abs. 1', 'Überschreiten der Höchstgeschw. 20-50 kmh', '$120', '-', NULL, 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§3 Abs. 1', 'Überschreiten der Höchstgeschw. 50-100 kmh', '$250', '-', NULL, 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§3 Abs. 1', 'Überschreiten der Höchstgeschw. 100+ kmh', '$400', '-', NULL, 4);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§3 Abs. 1', 'Überschreiten der Höchstgeschw.100+ kmh außerorts', '$350', '-', NULL, 5);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§4 Abs. 1', 'Missachten der Vorfahrt', '$100', '-', NULL, 6);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§4 Abs. 2', 'Missachten der Verkehrszeichen', '$100', '-', NULL, 7);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§5 Abs. 1', 'Abstand zu gering', '$100', '-', NULL, 8);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§6 Abs. 2', 'Überhol. der den Verkehr gefärdet', '$250', '-', 'evtl. Führerschein entzug', 9);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§6 Abs. 3', 'Nicht ausreich. Mindestabstand beim Überhol.', '$180', '-', NULL, 10);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§7 Abs. 1-2', 'Fahren ohne Licht', '$75', '-', NULL, 11);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§8 Abs. 1', 'Missbrauch von Signaleinrichtung', '$300', '-', NULL, 12);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§9 Abs. 1-4', 'Unzulässiges Halten und Parken', '$150', '-', 'evtl. Abschleppen', 13);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§10 Abs. 1', 'Fahren ohne Warnw. Verband. und Warndreieck', '$100', '-', NULL, 14);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§11 Abs. 1', 'Widerstand gegen Weisungen der Exekutive', '$250', '-', NULL, 15);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§12 Abs. 1', 'Das Fahren ohne Fahrerlaubnis', '$350', '-', NULL, 16);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§14 Abs. 2', 'Verstoß Rechtsfahrgebot', '$120', '-', NULL, 17);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§15 Abs. 1-2', 'Gefährliches Fahrverhalten', '$500', '-', 'evtl. Führerschein entzug', 18);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§16 Abs. 1', 'Verkehsbehinderung', '$150', '-', NULL, 19);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§18 Abs. 1', 'Fahren ohne Schutzhelm', '$50', '-', NULL, 20);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§19 Abs. 1', 'Fahrerflucht', '$500', '-', NULL, 21);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('StVO', '§21 Abs.', 'Illegales Tuning', '$700', NULL, 'Ausbau des illegalen Tunings', 22);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§2 Abs. 1 & 2', 'Mindestflughöhe und Sicherheitsabstand', '$300', '-', '-', 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§3 Abs. 1', 'unangemessene Fluggeschwindigkeit', '$400', '-', '-', 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§4 Abs. 1', 'unzulässiges Landen', '$600', '-', '-', 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§5 Abs. 1', 'Fliegen unter Alkohol, Drogen', '$800', '-', '-', 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§7 Abs. 1', 'Widerstand gegen Weisungen der Exekutive', '$500', '-', '-', 4);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§8 Abs. 1', 'Fliegen ohne Fluglizenz', '$900', '-', NULL, 5);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§8 Abs. 2', 'Missachtung der LVO im schweren Fall', '-', '-', 'Entzug der Fluglizenz', 6);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§9 Abs. 1', 'Gefährliches Flugverhalten', '$700', '-', '-', 7);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§9 Abs. 1', 'Gefährliches Flugverhalten mit Gefährdung', '$900', '-', '-', 8);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§10', 'Fliegen in einer Flugverbotszone', '$800', '-', 'Entzug der Fluglizenz', 9);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('LVO', '§11', 'Fallschirmspringen in Flugverbotszone, etc.', '$500', '-', '-', 10);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('UZwGE', '§9 Abs. 3.1', 'Einsatzkosten I', '$250', '-', NULL, 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('UZwGE', '§9 Abs. 3.2', 'Einsatzkosten II', '$500', '-', NULL, 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('UZwGE', '§9 Abs. 3.3', 'Einsatzkosten III', '$750', '-', NULL, 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('UZwGE', '§9 Abs. 3.4', 'Einsatzkosten IV', '$1.000', '-', NULL, 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§3 Abs. 1', 'Sachbeschädigung', '$100 - $300', '-', 'muss für entstandenen Sachschaden aufkommen', 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§4 Abs. 1', 'Beleidigung', '$100 - $250', '-', NULL, 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§5 Abs. 1', 'Missbräuchlicher Dispatch', '$250 - $600', '-', 'Kosten für den Einsatz müssen übernommen werden
bei wiederholter Tat bis zu $5.000', 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§6 Abs. 1', 'Erregung öffentlichen Ärgernisses', '$200 - $500', '-', NULL, 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§7 Abs. 1', 'Verbotene Ausübung der Prostitution', '$500 - $1.000', '-', NULL, 4);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§8 Abs. 1', 'Identität kann nicht festgestellt werden', '$300 - $700', '-', NULL, 5);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§9 Abs. 1', 'Unrichtige Angabe oder Angabe verweigert bzgl. 
Vor-, Familien- oder Geburtsnamen, den Ort oder Tag seiner Geburt, seinen Familienstand,
seinen Beruf, seinen Wohnort, seine Wohnung oder seine Staatsangehörigkeit', '$300 - $700', '-', 'Wenn nicht nach anderen Vorschriften geahndet werden kann', 6);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§10 Abs. 1', 'Tierquälerei', '$250 - $1.000', '-', NULL, 7);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§11', 'nicht genehmigter Protest', '$500 - $1.000', '-', NULL, 8);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§12 Abs. 1', 'Unzulässiger Lärm', '$150 - $400', '-', 'Wenn nicht nach anderen Vorschriften geahndet werden kann', 9);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§13 Abs. 1', 'Belästigung der Allgemeinheit', '$200 - $500', '-', 'Wenn nicht nach anderen Vorschriften geahndet werden kann', 10);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§14', 'Verschmutzung der Umwelt', '$100 - $1.500', '-', NULL, 11);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§15', 'Anordnung auf Erzwingungshaft', '-', 'pro $500 Ordnungsgeld = 1 HE', NULL, 12);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§16 Abs. 1', 'Üble Nachrede', '$300 - $700', NULL, NULL, 13);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§17 Abs. 1', 'Diebstahl', '$600 - $1.200', '10', NULL, 14);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§17 Abs. 2', 'Diebstahl in besonders schwerem Fall', '$1.200 - $2.000', '15', NULL, 15);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§18 Abs. 1', 'Dokumentenfälschung', '$1.200 - $2.000', NULL, NULL, 16);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§18 Abs. 2', 'Dokumentenfälschung / Schwarzgeld', '$1.500 - $3.000', NULL, NULL, 17);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§19 Abs. 1-2', 'Glücksspiel', '$300 - $600', NULL, NULL, 18);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§20 Abs. 1', 'Amtsanmaßung', '$1.500 - $3.000', NULL, NULL, 19);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§21 Abs. 1', 'Behinderung der Justiz', '$700 - $1.500', NULL, NULL, 20);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§22 Abs. 1', 'Behinderung der Behördenarbeit', '$700 - $1.500', NULL, NULL, 21);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§23 Abs. 1', 'Drohung', '$1.000 - $2.000', NULL, NULL, 22);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§24 Abs. 1', 'Hausfriedensbruch', '$800 - $1.500', NULL, NULL, 23);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('OWiG', '§25 Abs. 1', 'Vermummungsverbot in bestimmten Räumlichkeiten', '$500 - $1.000', NULL, NULL, 24);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§1 Abs. 1', 'Bestechung', '$2.500 - $5.000', '15 - 30', NULL, 0);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§2 Abs. 1', 'Bestechlichkeit', '$2.500 - $5.000', '15 - 30', NULL, 1);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§2 Abs. 1.1', 'Bestechlichkeit / nach Verlassen des Dienstes', '$3.500', '15 - 30', NULL, 2);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§3 Abs. 1', 'Vorteilsgewährung', '$2.000 - $4.000', '15 - 30', NULL, 3);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§4 Abs. 1', 'Vorteilsannahme', '$2.000 - $4.000', '15 - 30', NULL, 4);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§4 Abs. 1.1', 'Vorteilsannahme / nach Verlassen des Dienstes', '$3.000', '15 - 30', NULL, 5);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§5 Abs. 1', 'Rechtsbeugung', '$5.000', '-', 'kann bis zu 3 Tage vom Dienst suspendiert werden', 6);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§6 Abs. 1', 'Aussageerpressung', '$7.000 - $10.000', '40 - 60', NULL, 7);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§6 Abs. 2', 'Aussageerpressung / minder schwerer Fall', '$4.000 - $7.000', '15 - 30', NULL, 8);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§7 Abs. 1', 'Verfolgung Unschuldiger', '$6.000 - $9.000', '30 - 50', NULL, 9);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§7 Abs. 1', 'Verfolgung Unschuldiger / minder schwerer Fall', '$3.000 - $5.000', '15 - 25', NULL, 10);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§7 Abs. 2', 'Verfolgung Unschuldiger / Immunität', '$5.000 - $8.000', '20 - 40', NULL, 11);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§8 Abs. 1', 'Vollstreckung gegen Unschuldige', '$8.000 - $12.000', '25 - 50', NULL, 12);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§8 Abs. 1', 'Vollstreckung gegen Unschuldige / minder schwerer Fall', '$5.000 - $8.000', '15 - 25', NULL, 13);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§8 Abs. 2', 'Vollstreckung gegen Unschuldige / leichtfertig', '$6.000', '-', NULL, 14);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§8 Abs. 3', 'Vollstreckung gegen Unschuldige / Fälle abgesehen von Abs. 1', '$7.000 - $10.000', '25 - 50', NULL, 15);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§9 Abs. 1', 'Falschbeurkundung beim Amt', '$5.000', '-', NULL, 16);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§10 Abs. 1', 'Amtsmissbrauch', '$8.000', '30 - 60', NULL, 17);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§10 Abs. 3', 'Herausgabe von Informationen oder Dienstequipment', '$7.000', 'fest 60', NULL, 18);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§10 Abs. 3', 'Fahrlässige Herausgabe von Informationen', '$4.000 - $6.000', '15 - 30', NULL, 19);
INSERT INTO catalog_entries (code, paragraph, bedeutung, strafe, he, sonstiges, position) VALUES ('AkG', '§10 Abs. 5', 'Amtsgewaltsmittbrauch', '$9.000', '30 - 60', NULL, 20);
