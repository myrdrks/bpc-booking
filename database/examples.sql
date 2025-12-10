-- Beispiel: Weitere Optionen hinzufügen

INSERT INTO booking_options (name, description, price, category) VALUES
('Prosecco', 'Prosecco pro Flasche', 12.00, 'beverages'),
('Mittagessen', 'Warmes Mittagessen pro Person', 15.00, 'food'),
('Abendessen', 'Buffet-Abendessen pro Person', 22.00, 'food'),
('Mikrofon-Anlage', 'Mikrofon und Verstärker', 45.00, 'equipment'),
('Präsentationsset', 'Beamer, Leinwand, Laptop', 50.00, 'equipment'),
('Catering-Service', 'Kompletter Service mit Personal', 150.00, 'service'),
('Reinigung Extra', 'Zusätzliche Endreinigung', 30.00, 'service');

-- Beispiel: Weiteren Raum hinzufügen
INSERT INTO rooms (name, description, capacity, price_member, price_non_member, google_calendar_id) 
VALUES ('Raum 4', 'Kleiner Besprechungsraum', 6, 30.00, 50.00, 'raum4@example.com');

-- Beispiel: Admin-Benutzer hinzufügen
-- Passwort-Hash für 'sicheres_passwort' generieren mit:
-- php -r "echo password_hash('sicheres_passwort', PASSWORD_DEFAULT);"
INSERT INTO admin_users (username, password_hash, email, full_name) 
VALUES ('ihr_username', '$2y$10$...ihr_hash_hier...', 'admin@ihre-domain.de', 'Ihr Name');

-- Beispiel: Statistik-Abfrage - Anzahl Buchungen pro Raum
SELECT 
    r.name as raum,
    COUNT(*) as anzahl_buchungen,
    SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as bestätigt,
    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as offen,
    SUM(b.total_price) as gesamtumsatz
FROM bookings b
JOIN rooms r ON b.room_id = r.id
WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY r.id, r.name
ORDER BY anzahl_buchungen DESC;

-- Beispiel: Zukünftige Buchungen anzeigen
SELECT 
    b.id,
    r.name as raum,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.customer_name,
    b.status,
    b.total_price
FROM bookings b
JOIN rooms r ON b.room_id = r.id
WHERE b.booking_date >= CURDATE()
    AND b.status = 'confirmed'
ORDER BY b.booking_date, b.start_time;

-- Beispiel: Umsatz pro Monat
SELECT 
    DATE_FORMAT(booking_date, '%Y-%m') as monat,
    COUNT(*) as anzahl_buchungen,
    SUM(total_price) as umsatz,
    AVG(total_price) as durchschnitt
FROM bookings
WHERE status = 'confirmed'
GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
ORDER BY monat DESC
LIMIT 12;
