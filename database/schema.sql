-- Raumbuchungssystem Datenbankstruktur
-- Erstellt: 2025-11-10

-- Räume Tabelle
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT,
    price_member DECIMAL(10,2) NOT NULL,
    price_non_member DECIMAL(10,2) NOT NULL,
    google_calendar_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Buchungsoptionen/Extras Tabelle
CREATE TABLE IF NOT EXISTS booking_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('beverages', 'food', 'equipment', 'service') DEFAULT 'food',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Buchungen Tabelle
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(50),
    is_member TINYINT(1) DEFAULT 0,
    num_persons INT,
    room_price DECIMAL(10,2) NOT NULL,
    options_price DECIMAL(10,2) DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'rejected', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    google_event_id VARCHAR(255),
    confirmation_token VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    confirmed_by VARCHAR(100),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    INDEX idx_room_date (room_id, booking_date),
    INDEX idx_status (status),
    INDEX idx_token (confirmation_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gebuchte Optionen Tabelle (Many-to-Many Relation)
CREATE TABLE IF NOT EXISTS booking_options_selected (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    option_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES booking_options(id) ON DELETE RESTRICT,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin Benutzer Tabelle (optional für zukünftige Erweiterung)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log Tabelle für Änderungen
CREATE TABLE IF NOT EXISTS booking_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    user VARCHAR(100),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Beispieldaten für Räume
INSERT INTO rooms (name, description, capacity, price_member, price_non_member, google_calendar_id) VALUES
('CLUB27', 'Perfekt für Lesungen, Konzerte, Podiumsdiskussionen oder Empfänge. Mit Bar, Küche, Klavier & Kamin, Außenterrasse, Highspeed-WLAN, Musikanlage, Mikrofone, Profi-Raumbeschallung, Beamer (HDMI) mit Leinwand.', 80, 0.00, 500.00, 'club27@group.calendar.google.com'),
('Tagungsraum', 'Ideal für Meetings und Workshops. Kostenfrei für Mitglieder, inklusive Technik & Workshopkoffer. Techniknutzung gegen Aufpreis für Nicht-Mitglieder.', 20, 0.00, 100.00, 'tagungsraum@group.calendar.google.com'),
('Club-Lounge', 'Gemütlich. Intim. Inspirierend. Perfekt für kleine Runden, Meetings und vertrauliche Gespräche. Mit Sofas, Sesseln, Plattenspieler, 60-Zoll-Fernseher, Highspeed-WLAN und Getränkekühlschrank.', 20, 0.00, 100.00, 'club-lounge@group.calendar.google.com');

-- Beispieldaten für Buchungsoptionen
INSERT INTO booking_options (name, description, price, category) VALUES
('CLUB27: Servicepauschale', 'Personal & Reinigung (nur für CLUB27 Mitgliederbuchungen)', 250.00, 'service'),
('Technikeinweisung & Rundgang', 'Einweisung in die Technik mit Rundgang durch die Räumlichkeiten', 50.00, 'service'),
('Technikcheck am Veranstaltungstag', 'Technischer Support am Tag der Veranstaltung', 100.00, 'service'),
('Brötchen, Obst & Gebäck', 'Ab 20 Personen, Preis pro Person', 15.00, 'food'),
('Sektempfang', 'Ab 20 Personen, Preis pro Person', 5.00, 'beverages'),
('Fingerfood', 'Ab 30 Personen, Preis pro Person', 12.00, 'food'),
('Heißgetränke', 'Kaffee und Tee, ab 10 Personen, Preis pro Person', 7.50, 'beverages'),
('Italienisches Gebäck/Butterkekse', 'Ab 20 Personen, Preis pro Person', 3.00, 'food'),
('Kaltgetränke (Selbstabrechnung)', 'Getränkekühlschrank mit Vertrauenskasse', 0.00, 'beverages');

-- Standard Admin User (Passwort: admin123 - BITTE ÄNDERN!)
-- Passwort-Hash für 'admin123'
INSERT INTO admin_users (username, password_hash, email, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Administrator');
