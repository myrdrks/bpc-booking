-- Migration: Raum-Manager Felder hinzufügen
-- Erstellt: 2025-11-12
-- Beschreibung: Fügt manager_name und manager_email zu rooms Tabelle hinzu

SET NAMES utf8mb4;

ALTER TABLE rooms 
ADD COLUMN manager_name VARCHAR(100) NULL AFTER google_calendar_id,
ADD COLUMN manager_email VARCHAR(100) NULL AFTER manager_name;

-- Beispielwerte für existierende Räume (optional anpassen)
UPDATE rooms SET 
    manager_name = 'Raumverantwortlicher', 
    manager_email = 'philip+bpc@myrdrks.de' 
WHERE manager_email IS NULL;
