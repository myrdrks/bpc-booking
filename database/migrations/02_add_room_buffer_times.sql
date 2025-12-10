-- Migration: Buffer-Zeiten für Räume
-- Datum: 2025-11-11
-- Beschreibung: Fügt buffer_before und buffer_after zu rooms hinzu

-- Füge Buffer-Spalten zu rooms hinzu
ALTER TABLE rooms 
ADD COLUMN buffer_before INT DEFAULT 15 COMMENT 'Pufferzeit vor der Buchung in Minuten' 
AFTER google_calendar_id;

ALTER TABLE rooms 
ADD COLUMN buffer_after INT DEFAULT 15 COMMENT 'Pufferzeit nach der Buchung in Minuten' 
AFTER buffer_before;
