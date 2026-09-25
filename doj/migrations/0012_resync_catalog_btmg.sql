-- 0012_resync_catalog_btmg.sql
-- AUTO-GENERIERT von scripts/gen_catalog_migration.js
-- DB-Bussgeldkatalog (Archiv, Kategorie "Katalog") 1:1 aus public/bussgeldkatalog.js
-- + public/los-santos-tabelle.js neu aufgebaut -> identisch zur angezeigten Version.

UPDATE laws
SET html = '<h2><strong>Bußgeldkatalog Los Santos</strong></h2><p>Hafteinheit (HE) = 1 Minute · Maximalhaft 45 Min · in besonders schweren Fällen bis 60 Min mit Genehmigung durch Polizei Rang 10+</p><h3>[StGb] Strafgesetzbuch</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§13 Abs. 1</td><td>Raub</td><td>$1.600</td><td>10</td><td></td></tr><tr><td>§13 Abs. 2</td><td>Raub in besonders schweren Fall</td><td>$2.400</td><td>20</td><td></td></tr><tr><td>§14 Abs. 1</td><td>Erpressung</td><td>$1.800</td><td>5</td><td></td></tr><tr><td>§15 Abs. 1</td><td>Betrug</td><td>$2.200</td><td>5</td><td></td></tr><tr><td>§16 Abs. 1</td><td>Leichte Körperverletzung</td><td>$600</td><td>-</td><td></td></tr><tr><td>§16 Abs. 2</td><td>Gefährliche Körperverletzung</td><td>$1.800</td><td>5</td><td></td></tr><tr><td>§16 Abs. 3</td><td>Schwere Körperverletzung</td><td>$2.800</td><td>10</td><td></td></tr><tr><td>§16 Abs. 4</td><td>Fahrlässige Körperverletzung</td><td>$1.200</td><td>10</td><td></td></tr><tr><td>§17 Abs. 1</td><td>Versuchter Mord</td><td>$5.000</td><td>20</td><td></td></tr><tr><td>§17 Abs. 2</td><td>Mehrfach versuchter Mord / besondere Schwere der Tat</td><td>$7.500</td><td>25</td><td></td></tr><tr><td>§18 Abs. 1</td><td>Freiheitsentzug</td><td>$3.000</td><td>5</td><td></td></tr><tr><td>§19 Abs. 1</td><td>Verfassungswidrige Handlung</td><td>$9.000</td><td>10</td><td></td></tr><tr><td>§20 Abs. 1</td><td>Beweismittelfälschung</td><td>$1.500</td><td>15</td><td></td></tr><tr><td>§21 Abs. 1</td><td>Falsche Beweisaussage</td><td>$1.500</td><td>15</td><td></td></tr><tr><td>§21 Abs. 2</td><td>Meineid</td><td>$2.000</td><td>15</td><td></td></tr><tr><td>§22 Abs. 1</td><td>Entweichung von Gefangenen</td><td>doppelte reguläre Haftzeit und Geldstrafe</td><td></td><td></td></tr><tr><td>§23 Abs. 1</td><td>Beihilfe zum Gefängnisausbruch</td><td>$1.800</td><td>5</td><td></td></tr><tr><td>§24 Abs. 1</td><td>Strafvereitelung</td><td>$1.800</td><td>5</td><td></td></tr><tr><td>§25 Abs. 1</td><td>Geiselnahme</td><td>$3.500</td><td>5</td><td></td></tr><tr><td>§26 Abs. 1</td><td>Widerstand gegen Vollstreckungsbeamte</td><td>$900</td><td>2</td><td></td></tr><tr><td>§27 Abs. 2</td><td>Widerstand gegen Anweisungen</td><td>$300</td><td>-</td><td></td></tr><tr><td>§28 Abs. 1</td><td>Unbefugtes Betreten von Speerbezirken</td><td>$250</td><td>2</td><td>evtl. Platzverweis sollte die Person diesem nicht nachkommen = Haftzeit</td></tr><tr><td>§29 Abs. 1</td><td>Hehlerei</td><td>$700</td><td>2</td><td></td></tr><tr><td>§30 Abs. 1</td><td>Brandstiftung</td><td>$1.500</td><td>2</td><td>Aufkommen für den Sachschaden</td></tr><tr><td>§31 Abs. 1</td><td>Nachstellung / Belästigung</td><td>$400</td><td>2</td><td></td></tr></tbody></table><h3>[WaffG] Waffengesetz</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§15 Abs. 1</td><td>WaffG Straftaten / widersetzen des Waffenverbotes</td><td>$1.000</td><td>15 - 20</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 2</td><td>WaffG Straftaten / widersetzen der Entwaffnung</td><td>$1.500</td><td></td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 3</td><td>WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie A ohne Genehmigung</td><td>$1.200</td><td>15 - 20</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 4</td><td>WaffG Straftaten / Handel mit Waffen der Kategorie A ohne Genehmigung</td><td>$2.200</td><td>20 - 25</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 5</td><td>WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie B ohne Genehmigung</td><td>$2.500</td><td>15 - 25</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 6</td><td>WaffG Straftaten / Handel mit Waffen der Kategorie B ohne Genehmigung</td><td>$4.500</td><td>25 - 30</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 7</td><td>WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie C ohne Genehmigung</td><td>$5.000</td><td>15 - 30</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 8</td><td>WaffG Straftaten / Handel mit Waffen der Kategorie C ohne Genehmigung</td><td>$8.000</td><td>30 - 40</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 10</td><td>WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie D (vollautomatische- oder Schrotwaffen) ohne Genehmigung</td><td>$6.000</td><td>20 - 35</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 11</td><td>WaffG Straftaten / Handel mit Waffen der Kategorie D (vollautomatische- oder Schrotwaffen) ohne Genehmigung</td><td>$10.000</td><td>35 - 45</td><td>Waffe/n einziehen</td></tr><tr><td>§15 Abs. 9</td><td>WaffG Straftaten / Zweckentfremdung der Waffenscheine</td><td>$2.500</td><td>15 - 20</td><td></td></tr><tr><td>§16 Abs. 1</td><td>Waffenteile</td><td>$800</td><td>15 - 20</td><td>Waffenteile einziehen</td></tr><tr><td>§17 Abs. 6</td><td>WaffG Waffenscheininhaber / Verwenden einer Schusswaffe außerhalb der Schießstätte</td><td>$1.800</td><td>15 - 20</td><td>Waffenschein einziehen</td></tr></tbody></table><h3>[BtmG] Betäubungsmittelgesetz</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§7 Abs. 1a</td><td>Handel mit Betäubungsmitteln</td><td>$1.000</td><td>15 - 20</td><td>zzgl. $250 Aufschlag pro Drogeneinheit</td></tr><tr><td>§7 Abs. 1b</td><td>Wer für Betäubungsmittel wirbt</td><td>$800</td><td>-</td><td></td></tr><tr><td>§7 Abs. 1c</td><td>Falsche Angaben um an eine Verschreibung für Btm zu kommen</td><td>$1.200</td><td>-</td><td></td></tr><tr><td>§7 Abs. 1d</td><td>Besitz von Btm über dem Eigenbedarf</td><td>$900</td><td>-</td><td></td></tr></tbody></table><h3>[StVO] Straßenverkehrsordnung</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§2 Abs. 1</td><td>Fahren abseits befestigter Strassen</td><td>$80</td><td>-</td><td></td></tr><tr><td>§3 Abs. 1</td><td>Überschreiten der Höchstgeschw. 5-20 kmh</td><td>$50</td><td>-</td><td></td></tr><tr><td>§3 Abs. 1</td><td>Überschreiten der Höchstgeschw. 20-50 kmh</td><td>$120</td><td>-</td><td></td></tr><tr><td>§3 Abs. 1</td><td>Überschreiten der Höchstgeschw. 50-100 kmh</td><td>$250</td><td>-</td><td></td></tr><tr><td>§3 Abs. 1</td><td>Überschreiten der Höchstgeschw. 100+ kmh</td><td>$400</td><td>-</td><td></td></tr><tr><td>§3 Abs. 1</td><td>Überschreiten der Höchstgeschw.100+ kmh außerorts</td><td>$350</td><td>-</td><td></td></tr><tr><td>§4 Abs. 1</td><td>Missachten der Vorfahrt</td><td>$100</td><td>-</td><td></td></tr><tr><td>§4 Abs. 2</td><td>Missachten der Verkehrszeichen</td><td>$100</td><td>-</td><td></td></tr><tr><td>§5 Abs. 1</td><td>Abstand zu gering</td><td>$100</td><td>-</td><td></td></tr><tr><td>§6 Abs. 2</td><td>Überhol. der den Verkehr gefärdet</td><td>$250</td><td>-</td><td>evtl. Führerschein entzug</td></tr><tr><td>§6 Abs. 3</td><td>Nicht ausreich. Mindestabstand beim Überhol.</td><td>$180</td><td>-</td><td></td></tr><tr><td>§7 Abs. 1-2</td><td>Fahren ohne Licht</td><td>$75</td><td>-</td><td></td></tr><tr><td>§8 Abs. 1</td><td>Missbrauch von Signaleinrichtung</td><td>$300</td><td>-</td><td></td></tr><tr><td>§9 Abs. 1-4</td><td>Unzulässiges Halten und Parken</td><td>$150</td><td>-</td><td>evtl. Abschleppen</td></tr><tr><td>§10 Abs. 1</td><td>Fahren ohne Warnw. Verband. und Warndreieck</td><td>$100</td><td>-</td><td></td></tr><tr><td>§11 Abs. 1</td><td>Widerstand gegen Weisungen der Exekutive</td><td>$250</td><td>-</td><td></td></tr><tr><td>§12 Abs. 1</td><td>Das Fahren ohne Fahrerlaubnis</td><td>$350</td><td>-</td><td></td></tr><tr><td>§14 Abs. 2</td><td>Verstoß Rechtsfahrgebot</td><td>$120</td><td>-</td><td></td></tr><tr><td>§15 Abs. 1-2</td><td>Gefährliches Fahrverhalten</td><td>$500</td><td>-</td><td>evtl. Führerschein entzug</td></tr><tr><td>§16 Abs. 1</td><td>Verkehsbehinderung</td><td>$150</td><td>-</td><td></td></tr><tr><td>§18 Abs. 1</td><td>Fahren ohne Schutzhelm</td><td>$50</td><td>-</td><td></td></tr><tr><td>§19 Abs. 1</td><td>Fahrerflucht</td><td>$500</td><td>-</td><td></td></tr><tr><td>§21 Abs.</td><td>Illegales Tuning</td><td>$700</td><td></td><td>Ausbau des illegalen Tunings</td></tr></tbody></table><h3>[LVO] Luftverkehrsordnung</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§2 Abs. 1 &amp; 2</td><td>Mindestflughöhe und Sicherheitsabstand</td><td>$300</td><td>-</td><td>-</td></tr><tr><td>§3 Abs. 1</td><td>unangemessene Fluggeschwindigkeit</td><td>$400</td><td>-</td><td>-</td></tr><tr><td>§4 Abs. 1</td><td>unzulässiges Landen</td><td>$600</td><td>-</td><td>-</td></tr><tr><td>§5 Abs. 1</td><td>Fliegen unter Alkohol, Drogen</td><td>$800</td><td>-</td><td>-</td></tr><tr><td>§7 Abs. 1</td><td>Widerstand gegen Weisungen der Exekutive</td><td>$500</td><td>-</td><td>-</td></tr><tr><td>§8 Abs. 1</td><td>Fliegen ohne Fluglizenz</td><td>$900</td><td>-</td><td></td></tr><tr><td>§8 Abs. 2</td><td>Missachtung der LVO im schweren Fall</td><td>-</td><td>-</td><td>Entzug der Fluglizenz</td></tr><tr><td>§9 Abs. 1</td><td>Gefährliches Flugverhalten</td><td>$700</td><td>-</td><td>-</td></tr><tr><td>§9 Abs. 1</td><td>Gefährliches Flugverhalten mit Gefährdung</td><td>$900</td><td>-</td><td>-</td></tr><tr><td>§10</td><td>Fliegen in einer Flugverbotszone</td><td>$800</td><td>-</td><td>Entzug der Fluglizenz</td></tr><tr><td>§11</td><td>Fallschirmspringen in Flugverbotszone, etc.</td><td>$500</td><td>-</td><td>-</td></tr></tbody></table><h3>[UZwGE] Unmittelbarer Zwang der Exekutive</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§9 Abs. 3.1</td><td>Einsatzkosten I</td><td>$250</td><td>-</td><td></td></tr><tr><td>§9 Abs. 3.2</td><td>Einsatzkosten II</td><td>$500</td><td>-</td><td></td></tr><tr><td>§9 Abs. 3.3</td><td>Einsatzkosten III</td><td>$750</td><td>-</td><td></td></tr><tr><td>§9 Abs. 3.4</td><td>Einsatzkosten IV</td><td>$1.000</td><td>-</td><td></td></tr></tbody></table><h3>[OWiG] Ordnungswidrigkeitengesetz</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§3 Abs. 1</td><td>Sachbeschädigung</td><td>$100 - $300</td><td>-</td><td>muss für entstandenen Sachschaden aufkommen</td></tr><tr><td>§4 Abs. 1</td><td>Beleidigung</td><td>$100 - $250</td><td>-</td><td></td></tr><tr><td>§5 Abs. 1</td><td>Missbräuchlicher Dispatch</td><td>$250 - $600</td><td>-</td><td>Kosten für den Einsatz müssen übernommen werden<br>bei wiederholter Tat bis zu $5.000</td></tr><tr><td>§6 Abs. 1</td><td>Erregung öffentlichen Ärgernisses</td><td>$200 - $500</td><td>-</td><td></td></tr><tr><td>§7 Abs. 1</td><td>Verbotene Ausübung der Prostitution</td><td>$500 - $1.000</td><td>-</td><td></td></tr><tr><td>§8 Abs. 1</td><td>Identität kann nicht festgestellt werden</td><td>$300 - $700</td><td>-</td><td></td></tr><tr><td>§9 Abs. 1</td><td>Unrichtige Angabe oder Angabe verweigert bzgl. <br>Vor-, Familien- oder Geburtsnamen, den Ort oder Tag seiner Geburt, seinen Familienstand,<br>seinen Beruf, seinen Wohnort, seine Wohnung oder seine Staatsangehörigkeit</td><td>$300 - $700</td><td>-</td><td>Wenn nicht nach anderen Vorschriften geahndet werden kann</td></tr><tr><td>§10 Abs. 1</td><td>Tierquälerei</td><td>$250 - $1.000</td><td>-</td><td></td></tr><tr><td>§11</td><td>nicht genehmigter Protest</td><td>$500 - $1.000</td><td>-</td><td></td></tr><tr><td>§12 Abs. 1</td><td>Unzulässiger Lärm</td><td>$150 - $400</td><td>-</td><td>Wenn nicht nach anderen Vorschriften geahndet werden kann</td></tr><tr><td>§13 Abs. 1</td><td>Belästigung der Allgemeinheit</td><td>$200 - $500</td><td>-</td><td>Wenn nicht nach anderen Vorschriften geahndet werden kann</td></tr><tr><td>§14</td><td>Verschmutzung der Umwelt</td><td>$100 - $1.500</td><td>-</td><td></td></tr><tr><td>§15</td><td>Anordnung auf Erzwingungshaft</td><td>-</td><td>pro $500 Ordnungsgeld = 1 HE</td><td></td></tr><tr><td>§16 Abs. 1</td><td>Üble Nachrede</td><td>$300 - $700</td><td></td><td></td></tr><tr><td>§17 Abs. 1</td><td>Diebstahl</td><td>$600 - $1.200</td><td>10</td><td></td></tr><tr><td>§17 Abs. 2</td><td>Diebstahl in besonders schwerem Fall</td><td>$1.200 - $2.000</td><td>15</td><td></td></tr><tr><td>§18 Abs. 1</td><td>Dokumentenfälschung</td><td>$1.200 - $2.000</td><td></td><td></td></tr><tr><td>§18 Abs. 2</td><td>Dokumentenfälschung / Schwarzgeld</td><td>$1.500 - $3.000</td><td></td><td></td></tr><tr><td>§19 Abs. 1-2</td><td>Glücksspiel</td><td>$300 - $600</td><td></td><td></td></tr><tr><td>§20 Abs. 1</td><td>Amtsanmaßung</td><td>$1.500 - $3.000</td><td></td><td></td></tr><tr><td>§21 Abs. 1</td><td>Behinderung der Justiz</td><td>$700 - $1.500</td><td></td><td></td></tr><tr><td>§22 Abs. 1</td><td>Behinderung der Behördenarbeit</td><td>$700 - $1.500</td><td></td><td></td></tr><tr><td>§23 Abs. 1</td><td>Drohung</td><td>$1.000 - $2.000</td><td></td><td></td></tr><tr><td>§24 Abs. 1</td><td>Hausfriedensbruch</td><td>$800 - $1.500</td><td></td><td></td></tr><tr><td>§25 Abs. 1</td><td>Vermummungsverbot in bestimmten Räumlichkeiten</td><td>$500 - $1.000</td><td></td><td></td></tr></tbody></table><h3>[AkG] Antikorruptionsgesetz</h3><table><thead><tr><th>§ Paragraph</th><th>Bedeutung</th><th>Geldstrafe</th><th>Hafteinheiten</th><th>Sonstige Sanktionen</th></tr></thead><tbody><tr><td>§1 Abs. 1</td><td>Bestechung</td><td>$2.500 - $5.000</td><td>15 - 30</td><td></td></tr><tr><td>§2 Abs. 1</td><td>Bestechlichkeit</td><td>$2.500 - $5.000</td><td>15 - 30</td><td></td></tr><tr><td>§2 Abs. 1.1</td><td>Bestechlichkeit / nach Verlassen des Dienstes</td><td>$3.500</td><td>15 - 30</td><td></td></tr><tr><td>§3 Abs. 1</td><td>Vorteilsgewährung</td><td>$2.000 - $4.000</td><td>15 - 30</td><td></td></tr><tr><td>§4 Abs. 1</td><td>Vorteilsannahme</td><td>$2.000 - $4.000</td><td>15 - 30</td><td></td></tr><tr><td>§4 Abs. 1.1</td><td>Vorteilsannahme / nach Verlassen des Dienstes</td><td>$3.000</td><td>15 - 30</td><td></td></tr><tr><td>§5 Abs. 1</td><td>Rechtsbeugung</td><td>$5.000</td><td>-</td><td>kann bis zu 3 Tage vom Dienst suspendiert werden</td></tr><tr><td>§6 Abs. 1</td><td>Aussageerpressung</td><td>$7.000 - $10.000</td><td>40 - 60</td><td></td></tr><tr><td>§6 Abs. 2</td><td>Aussageerpressung / minder schwerer Fall</td><td>$4.000 - $7.000</td><td>15 - 30</td><td></td></tr><tr><td>§7 Abs. 1</td><td>Verfolgung Unschuldiger</td><td>$6.000 - $9.000</td><td>30 - 50</td><td></td></tr><tr><td>§7 Abs. 1</td><td>Verfolgung Unschuldiger / minder schwerer Fall</td><td>$3.000 - $5.000</td><td>15 - 25</td><td></td></tr><tr><td>§7 Abs. 2</td><td>Verfolgung Unschuldiger / Immunität</td><td>$5.000 - $8.000</td><td>20 - 40</td><td></td></tr><tr><td>§8 Abs. 1</td><td>Vollstreckung gegen Unschuldige</td><td>$8.000 - $12.000</td><td>25 - 50</td><td></td></tr><tr><td>§8 Abs. 1</td><td>Vollstreckung gegen Unschuldige / minder schwerer Fall</td><td>$5.000 - $8.000</td><td>15 - 25</td><td></td></tr><tr><td>§8 Abs. 2</td><td>Vollstreckung gegen Unschuldige / leichtfertig</td><td>$6.000</td><td>-</td><td></td></tr><tr><td>§8 Abs. 3</td><td>Vollstreckung gegen Unschuldige / Fälle abgesehen von Abs. 1</td><td>$7.000 - $10.000</td><td>25 - 50</td><td></td></tr><tr><td>§9 Abs. 1</td><td>Falschbeurkundung beim Amt</td><td>$5.000</td><td>-</td><td></td></tr><tr><td>§10 Abs. 1</td><td>Amtsmissbrauch</td><td>$8.000</td><td>30 - 60</td><td></td></tr><tr><td>§10 Abs. 3</td><td>Herausgabe von Informationen oder Dienstequipment</td><td>$7.000</td><td>fest 60</td><td></td></tr><tr><td>§10 Abs. 3</td><td>Fahrlässige Herausgabe von Informationen</td><td>$4.000 - $6.000</td><td>15 - 30</td><td></td></tr><tr><td>§10 Abs. 5</td><td>Amtsgewaltsmittbrauch</td><td>$9.000</td><td>30 - 60</td><td></td></tr></tbody></table><h3>[ZIV] Schadensersatztabelle (zivilrechtliche Ansprüche)</h3><p>Höchstwerte für zivilrechtliche Schadenersatzansprüche. Berechnung als Vielfaches des Höchst-Bußgeldes der jeweiligen Rechtsgrundlage.</p><table><thead><tr><th>Rechtsgrundlage</th><th>Tatbestand</th><th>Anspruchshöhe</th><th>Bemerkung</th></tr></thead><tbody><tr><td>§3 OWiG</td><td>Sachbeschädigung</td><td>bis zum 15-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§4 OWiG</td><td>Beleidigung</td><td>bis zum 20-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§16 OWiG</td><td>Üble Nachrede</td><td>bis zum 40-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§17 OWiG</td><td>Diebstahl</td><td>bis zum 40-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 10 % der gestohlenen Summe sowie Ersatz des materiellen Schadens</td></tr><tr><td>§18 OWiG</td><td>Dokumentenfälschung</td><td>bis zum 25-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§15 StGB</td><td>Betrug</td><td>bis zum 75-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 10 % der betrogenen Summe sowie Ersatz des materiellen Schadens</td></tr><tr><td>§16 StGB</td><td>Schwere Körperverletzung</td><td>bis zum 50-fachen Höchst-Bußgeld</td><td>zzgl. bis zu 24-stündiger Verdienstausfall des festen Gehalts — maximal 2 Mio.</td></tr><tr><td>§17 StGB</td><td>Versuchter Mord</td><td>bis zum 10-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§30 StGB</td><td>Brandstiftung</td><td>bis zum 25-fachen Höchst-Bußgeld</td><td>Sowie Ersatz des materiellen Schadens</td></tr><tr><td>§2 DSGVO</td><td>Verschwiegenheit Interna</td><td>bis zum 25-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§3 DSGVO</td><td>Recht am geistigen Eigentum</td><td>bis zum 50-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§4 DSGVO</td><td>Aufnahme ohne Einwilligung</td><td>bis zum 30-fachen Höchst-Bußgeld</td><td></td></tr><tr><td>§5 DSGVO</td><td>Betriebs- oder Geschäftsgeheimnisse offenbart</td><td>bis zum 15-fachen Höchst-Bußgeld</td><td></td></tr></tbody></table>',
    text = 'Bußgeldkatalog Los Santos
[StGb] Strafgesetzbuch
§13 Abs. 1 | Raub | $1.600 | 10
§13 Abs. 2 | Raub in besonders schweren Fall | $2.400 | 20
§14 Abs. 1 | Erpressung | $1.800 | 5
§15 Abs. 1 | Betrug | $2.200 | 5
§16 Abs. 1 | Leichte Körperverletzung | $600 | -
§16 Abs. 2 | Gefährliche Körperverletzung | $1.800 | 5
§16 Abs. 3 | Schwere Körperverletzung | $2.800 | 10
§16 Abs. 4 | Fahrlässige Körperverletzung | $1.200 | 10
§17 Abs. 1 | Versuchter Mord | $5.000 | 20
§17 Abs. 2 | Mehrfach versuchter Mord / besondere Schwere der Tat | $7.500 | 25
§18 Abs. 1 | Freiheitsentzug | $3.000 | 5
§19 Abs. 1 | Verfassungswidrige Handlung | $9.000 | 10
§20 Abs. 1 | Beweismittelfälschung | $1.500 | 15
§21 Abs. 1 | Falsche Beweisaussage | $1.500 | 15
§21 Abs. 2 | Meineid | $2.000 | 15
§22 Abs. 1 | Entweichung von Gefangenen | doppelte reguläre Haftzeit und Geldstrafe
§23 Abs. 1 | Beihilfe zum Gefängnisausbruch | $1.800 | 5
§24 Abs. 1 | Strafvereitelung | $1.800 | 5
§25 Abs. 1 | Geiselnahme | $3.500 | 5
§26 Abs. 1 | Widerstand gegen Vollstreckungsbeamte | $900 | 2
§27 Abs. 2 | Widerstand gegen Anweisungen | $300 | -
§28 Abs. 1 | Unbefugtes Betreten von Speerbezirken | $250 | 2 | evtl. Platzverweis sollte die Person diesem nicht nachkommen = Haftzeit
§29 Abs. 1 | Hehlerei | $700 | 2
§30 Abs. 1 | Brandstiftung | $1.500 | 2 | Aufkommen für den Sachschaden
§31 Abs. 1 | Nachstellung / Belästigung | $400 | 2
[WaffG] Waffengesetz
§15 Abs. 1 | WaffG Straftaten / widersetzen des Waffenverbotes | $1.000 | 15 - 20 | Waffe/n einziehen
§15 Abs. 2 | WaffG Straftaten / widersetzen der Entwaffnung | $1.500 | Waffe/n einziehen
§15 Abs. 3 | WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie A ohne Genehmigung | $1.200 | 15 - 20 | Waffe/n einziehen
§15 Abs. 4 | WaffG Straftaten / Handel mit Waffen der Kategorie A ohne Genehmigung | $2.200 | 20 - 25 | Waffe/n einziehen
§15 Abs. 5 | WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie B ohne Genehmigung | $2.500 | 15 - 25 | Waffe/n einziehen
§15 Abs. 6 | WaffG Straftaten / Handel mit Waffen der Kategorie B ohne Genehmigung | $4.500 | 25 - 30 | Waffe/n einziehen
§15 Abs. 7 | WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie C ohne Genehmigung | $5.000 | 15 - 30 | Waffe/n einziehen
§15 Abs. 8 | WaffG Straftaten / Handel mit Waffen der Kategorie C ohne Genehmigung | $8.000 | 30 - 40 | Waffe/n einziehen
§15 Abs. 10 | WaffG Straftaten / Führen oder Besitz einer Waffe der Kategorie D (vollautomatische- oder Schrotwaffen) ohne Genehmigung | $6.000 | 20 - 35 | Waffe/n einziehen
§15 Abs. 11 | WaffG Straftaten / Handel mit Waffen der Kategorie D (vollautomatische- oder Schrotwaffen) ohne Genehmigung | $10.000 | 35 - 45 | Waffe/n einziehen
§15 Abs. 9 | WaffG Straftaten / Zweckentfremdung der Waffenscheine | $2.500 | 15 - 20
§16 Abs. 1 | Waffenteile | $800 | 15 - 20 | Waffenteile einziehen
§17 Abs. 6 | WaffG Waffenscheininhaber / Verwenden einer Schusswaffe außerhalb der Schießstätte | $1.800 | 15 - 20 | Waffenschein einziehen
[BtmG] Betäubungsmittelgesetz
§7 Abs. 1a | Handel mit Betäubungsmitteln | $1.000 | 15 - 20 | zzgl. $250 Aufschlag pro Drogeneinheit
§7 Abs. 1b | Wer für Betäubungsmittel wirbt | $800 | -
§7 Abs. 1c | Falsche Angaben um an eine Verschreibung für Btm zu kommen | $1.200 | -
§7 Abs. 1d | Besitz von Btm über dem Eigenbedarf | $900 | -
[StVO] Straßenverkehrsordnung
§2 Abs. 1 | Fahren abseits befestigter Strassen | $80 | -
§3 Abs. 1 | Überschreiten der Höchstgeschw. 5-20 kmh | $50 | -
§3 Abs. 1 | Überschreiten der Höchstgeschw. 20-50 kmh | $120 | -
§3 Abs. 1 | Überschreiten der Höchstgeschw. 50-100 kmh | $250 | -
§3 Abs. 1 | Überschreiten der Höchstgeschw. 100+ kmh | $400 | -
§3 Abs. 1 | Überschreiten der Höchstgeschw.100+ kmh außerorts | $350 | -
§4 Abs. 1 | Missachten der Vorfahrt | $100 | -
§4 Abs. 2 | Missachten der Verkehrszeichen | $100 | -
§5 Abs. 1 | Abstand zu gering | $100 | -
§6 Abs. 2 | Überhol. der den Verkehr gefärdet | $250 | - | evtl. Führerschein entzug
§6 Abs. 3 | Nicht ausreich. Mindestabstand beim Überhol. | $180 | -
§7 Abs. 1-2 | Fahren ohne Licht | $75 | -
§8 Abs. 1 | Missbrauch von Signaleinrichtung | $300 | -
§9 Abs. 1-4 | Unzulässiges Halten und Parken | $150 | - | evtl. Abschleppen
§10 Abs. 1 | Fahren ohne Warnw. Verband. und Warndreieck | $100 | -
§11 Abs. 1 | Widerstand gegen Weisungen der Exekutive | $250 | -
§12 Abs. 1 | Das Fahren ohne Fahrerlaubnis | $350 | -
§14 Abs. 2 | Verstoß Rechtsfahrgebot | $120 | -
§15 Abs. 1-2 | Gefährliches Fahrverhalten | $500 | - | evtl. Führerschein entzug
§16 Abs. 1 | Verkehsbehinderung | $150 | -
§18 Abs. 1 | Fahren ohne Schutzhelm | $50 | -
§19 Abs. 1 | Fahrerflucht | $500 | -
§21 Abs. | Illegales Tuning | $700 | Ausbau des illegalen Tunings
[LVO] Luftverkehrsordnung
§2 Abs. 1 & 2 | Mindestflughöhe und Sicherheitsabstand | $300 | - | -
§3 Abs. 1 | unangemessene Fluggeschwindigkeit | $400 | - | -
§4 Abs. 1 | unzulässiges Landen | $600 | - | -
§5 Abs. 1 | Fliegen unter Alkohol, Drogen | $800 | - | -
§7 Abs. 1 | Widerstand gegen Weisungen der Exekutive | $500 | - | -
§8 Abs. 1 | Fliegen ohne Fluglizenz | $900 | -
§8 Abs. 2 | Missachtung der LVO im schweren Fall | - | - | Entzug der Fluglizenz
§9 Abs. 1 | Gefährliches Flugverhalten | $700 | - | -
§9 Abs. 1 | Gefährliches Flugverhalten mit Gefährdung | $900 | - | -
§10 | Fliegen in einer Flugverbotszone | $800 | - | Entzug der Fluglizenz
§11 | Fallschirmspringen in Flugverbotszone, etc. | $500 | - | -
[UZwGE] Unmittelbarer Zwang der Exekutive
§9 Abs. 3.1 | Einsatzkosten I | $250 | -
§9 Abs. 3.2 | Einsatzkosten II | $500 | -
§9 Abs. 3.3 | Einsatzkosten III | $750 | -
§9 Abs. 3.4 | Einsatzkosten IV | $1.000 | -
[OWiG] Ordnungswidrigkeitengesetz
§3 Abs. 1 | Sachbeschädigung | $100 - $300 | - | muss für entstandenen Sachschaden aufkommen
§4 Abs. 1 | Beleidigung | $100 - $250 | -
§5 Abs. 1 | Missbräuchlicher Dispatch | $250 - $600 | - | Kosten für den Einsatz müssen übernommen werden
bei wiederholter Tat bis zu $5.000
§6 Abs. 1 | Erregung öffentlichen Ärgernisses | $200 - $500 | -
§7 Abs. 1 | Verbotene Ausübung der Prostitution | $500 - $1.000 | -
§8 Abs. 1 | Identität kann nicht festgestellt werden | $300 - $700 | -
§9 Abs. 1 | Unrichtige Angabe oder Angabe verweigert bzgl. 
Vor-, Familien- oder Geburtsnamen, den Ort oder Tag seiner Geburt, seinen Familienstand,
seinen Beruf, seinen Wohnort, seine Wohnung oder seine Staatsangehörigkeit | $300 - $700 | - | Wenn nicht nach anderen Vorschriften geahndet werden kann
§10 Abs. 1 | Tierquälerei | $250 - $1.000 | -
§11 | nicht genehmigter Protest | $500 - $1.000 | -
§12 Abs. 1 | Unzulässiger Lärm | $150 - $400 | - | Wenn nicht nach anderen Vorschriften geahndet werden kann
§13 Abs. 1 | Belästigung der Allgemeinheit | $200 - $500 | - | Wenn nicht nach anderen Vorschriften geahndet werden kann
§14 | Verschmutzung der Umwelt | $100 - $1.500 | -
§15 | Anordnung auf Erzwingungshaft | - | pro $500 Ordnungsgeld = 1 HE
§16 Abs. 1 | Üble Nachrede | $300 - $700
§17 Abs. 1 | Diebstahl | $600 - $1.200 | 10
§17 Abs. 2 | Diebstahl in besonders schwerem Fall | $1.200 - $2.000 | 15
§18 Abs. 1 | Dokumentenfälschung | $1.200 - $2.000
§18 Abs. 2 | Dokumentenfälschung / Schwarzgeld | $1.500 - $3.000
§19 Abs. 1-2 | Glücksspiel | $300 - $600
§20 Abs. 1 | Amtsanmaßung | $1.500 - $3.000
§21 Abs. 1 | Behinderung der Justiz | $700 - $1.500
§22 Abs. 1 | Behinderung der Behördenarbeit | $700 - $1.500
§23 Abs. 1 | Drohung | $1.000 - $2.000
§24 Abs. 1 | Hausfriedensbruch | $800 - $1.500
§25 Abs. 1 | Vermummungsverbot in bestimmten Räumlichkeiten | $500 - $1.000
[AkG] Antikorruptionsgesetz
§1 Abs. 1 | Bestechung | $2.500 - $5.000 | 15 - 30
§2 Abs. 1 | Bestechlichkeit | $2.500 - $5.000 | 15 - 30
§2 Abs. 1.1 | Bestechlichkeit / nach Verlassen des Dienstes | $3.500 | 15 - 30
§3 Abs. 1 | Vorteilsgewährung | $2.000 - $4.000 | 15 - 30
§4 Abs. 1 | Vorteilsannahme | $2.000 - $4.000 | 15 - 30
§4 Abs. 1.1 | Vorteilsannahme / nach Verlassen des Dienstes | $3.000 | 15 - 30
§5 Abs. 1 | Rechtsbeugung | $5.000 | - | kann bis zu 3 Tage vom Dienst suspendiert werden
§6 Abs. 1 | Aussageerpressung | $7.000 - $10.000 | 40 - 60
§6 Abs. 2 | Aussageerpressung / minder schwerer Fall | $4.000 - $7.000 | 15 - 30
§7 Abs. 1 | Verfolgung Unschuldiger | $6.000 - $9.000 | 30 - 50
§7 Abs. 1 | Verfolgung Unschuldiger / minder schwerer Fall | $3.000 - $5.000 | 15 - 25
§7 Abs. 2 | Verfolgung Unschuldiger / Immunität | $5.000 - $8.000 | 20 - 40
§8 Abs. 1 | Vollstreckung gegen Unschuldige | $8.000 - $12.000 | 25 - 50
§8 Abs. 1 | Vollstreckung gegen Unschuldige / minder schwerer Fall | $5.000 - $8.000 | 15 - 25
§8 Abs. 2 | Vollstreckung gegen Unschuldige / leichtfertig | $6.000 | -
§8 Abs. 3 | Vollstreckung gegen Unschuldige / Fälle abgesehen von Abs. 1 | $7.000 - $10.000 | 25 - 50
§9 Abs. 1 | Falschbeurkundung beim Amt | $5.000 | -
§10 Abs. 1 | Amtsmissbrauch | $8.000 | 30 - 60
§10 Abs. 3 | Herausgabe von Informationen oder Dienstequipment | $7.000 | fest 60
§10 Abs. 3 | Fahrlässige Herausgabe von Informationen | $4.000 - $6.000 | 15 - 30
§10 Abs. 5 | Amtsgewaltsmittbrauch | $9.000 | 30 - 60
[ZIV] Schadensersatztabelle
§3 OWiG | Sachbeschädigung | bis zum 15-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§4 OWiG | Beleidigung | bis zum 20-fachen Höchst-Bußgeld
§16 OWiG | Üble Nachrede | bis zum 40-fachen Höchst-Bußgeld
§17 OWiG | Diebstahl | bis zum 40-fachen Höchst-Bußgeld | zzgl. bis zu 10 % der gestohlenen Summe sowie Ersatz des materiellen Schadens
§18 OWiG | Dokumentenfälschung | bis zum 25-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§15 StGB | Betrug | bis zum 75-fachen Höchst-Bußgeld | zzgl. bis zu 10 % der betrogenen Summe sowie Ersatz des materiellen Schadens
§16 StGB | Schwere Körperverletzung | bis zum 50-fachen Höchst-Bußgeld | zzgl. bis zu 24-stündiger Verdienstausfall des festen Gehalts — maximal 2 Mio.
§17 StGB | Versuchter Mord | bis zum 10-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§30 StGB | Brandstiftung | bis zum 25-fachen Höchst-Bußgeld | Sowie Ersatz des materiellen Schadens
§2 DSGVO | Verschwiegenheit Interna | bis zum 25-fachen Höchst-Bußgeld
§3 DSGVO | Recht am geistigen Eigentum | bis zum 50-fachen Höchst-Bußgeld
§4 DSGVO | Aufnahme ohne Einwilligung | bis zum 30-fachen Höchst-Bußgeld
§5 DSGVO | Betriebs- oder Geschäftsgeheimnisse offenbart | bis zum 15-fachen Höchst-Bußgeld',
    word_count = 1987,
    updated_at = 1782518400002,
    updated_by = 'system'
WHERE slug = 'bussgeldkatalog-los-santos';
