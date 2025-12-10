-- Aktualisierte Raumdaten basierend auf rooms.md

-- Räume aktualisieren
UPDATE rooms SET 
    name = 'CLUB27',
    description = 'Perfekt für Lesungen, Konzerte, Podiumsdiskussionen oder Empfänge. Mit Bar, Küche, Klavier & Kamin, Außenterrasse, Highspeed-WLAN, Musikanlage, Mikrofone, Profi-Raumbeschallung, Beamer (HDMI) mit Leinwand.',
    capacity = 80,
    price_member = 0.00,
    price_non_member = 500.00,
    google_calendar_id = 'club27@group.calendar.google.com'
WHERE id = 1;

UPDATE rooms SET 
    name = 'Tagungsraum',
    description = 'Ideal für Meetings und Workshops. Kostenfrei für Mitglieder, inklusive Technik & Workshopkoffer. Techniknutzung gegen Aufpreis für Nicht-Mitglieder.',
    capacity = 20,
    price_member = 0.00,
    price_non_member = 100.00,
    google_calendar_id = 'tagungsraum@group.calendar.google.com'
WHERE id = 2;

UPDATE rooms SET 
    name = 'Club-Lounge',
    description = 'Gemütlich. Intim. Inspirierend. Perfekt für kleine Runden, Meetings und vertrauliche Gespräche. Mit Sofas, Sesseln, Plattenspieler, 60-Zoll-Fernseher, Highspeed-WLAN und Getränkekühlschrank.',
    capacity = 20,
    price_member = 0.00,
    price_non_member = 100.00,
    google_calendar_id = 'club-lounge@group.calendar.google.com'
WHERE id = 3;

-- Alte Optionen löschen
DELETE FROM booking_options;

-- Neue Optionen einfügen basierend auf rooms.md

-- CLUB27 spezifische Servicepauschale
INSERT INTO booking_options (name, description, price, category, active) VALUES
('CLUB27: Servicepauschale', 'Personal & Reinigung (nur für CLUB27 Mitgliederbuchungen)', 250.00, 'service', 1);

-- Zusätzliche Services (für alle Räume)
INSERT INTO booking_options (name, description, price, category, active) VALUES
('Technikeinweisung & Rundgang', 'Einweisung in die Technik mit Rundgang durch die Räumlichkeiten', 50.00, 'service', 1),
('Technikcheck am Veranstaltungstag', 'Technischer Support am Tag der Veranstaltung', 100.00, 'service', 1);

-- Catering-Optionen (ab 20 Personen, außer anders angegeben)
INSERT INTO booking_options (name, description, price, category, active) VALUES
('Brötchen, Obst & Gebäck', 'Ab 20 Personen, Preis pro Person', 15.00, 'food', 1),
('Sektempfang', 'Ab 20 Personen, Preis pro Person', 5.00, 'beverages', 1),
('Fingerfood', 'Ab 30 Personen, Preis pro Person', 12.00, 'food', 1),
('Heißgetränke', 'Kaffee und Tee, ab 10 Personen, Preis pro Person', 7.50, 'beverages', 1),
('Italienisches Gebäck/Butterkekse', 'Ab 20 Personen, Preis pro Person', 3.00, 'food', 1);

-- Getränke per Selbstabrechnung (für Tagungsraum und Club-Lounge)
INSERT INTO booking_options (name, description, price, category, active) VALUES
('Kaltgetränke (Selbstabrechnung)', 'Getränkekühlschrank mit Vertrauenskasse', 0.00, 'beverages', 1);
