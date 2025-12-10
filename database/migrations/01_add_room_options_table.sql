-- Migration: Room Options Mapping Tabelle
-- Datum: 2025-11-11
-- Beschreibung: Many-to-Many Relation zwischen Räumen und Optionen

-- Erstelle room_options Tabelle (Many-to-Many: Räume <-> Optionen)
CREATE TABLE IF NOT EXISTS room_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    option_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES booking_options(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_option (room_id, option_id),
    INDEX idx_room (room_id),
    INDEX idx_option (option_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Verknüpfung zwischen Räumen und ihren verfügbaren Optionen';
