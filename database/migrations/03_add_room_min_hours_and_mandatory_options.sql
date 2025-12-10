-- Migration: Mindeststunden und Pflicht-Optionen für Räume
-- Datum: 2025-11-11

-- Füge min_hours zu rooms hinzu (Mindeststunden für Raummiete)
ALTER TABLE rooms 
ADD COLUMN min_hours DECIMAL(4,2) DEFAULT 1.00 COMMENT 'Mindeststunden für Raummiete' 
AFTER capacity;

-- Füge is_mandatory zu booking_options hinzu (Nicht-optionale Pauschalen)
ALTER TABLE booking_options 
ADD COLUMN is_mandatory TINYINT(1) DEFAULT 0 COMMENT 'Option ist Pflicht und kann nicht abgewählt werden' 
AFTER active;

-- Füge apply_to_rooms zu booking_options hinzu (Anwendung auf bestimmte Räume)
-- NULL = alle Räume, sonst komma-separierte Raum-IDs
ALTER TABLE booking_options 
ADD COLUMN apply_to_rooms VARCHAR(255) DEFAULT NULL COMMENT 'Komma-separierte Raum-IDs oder NULL für alle Räume' 
AFTER is_mandatory;
