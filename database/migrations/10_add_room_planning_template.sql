-- Migration: Veranstaltungsplanungs-Template pro Raum
-- Erstellt: 2025-12-10
-- Beschreibung: Fügt planning_template Feld zu rooms Tabelle hinzu

SET NAMES utf8mb4;

ALTER TABLE rooms 
ADD COLUMN planning_template TEXT NULL AFTER manager_email;

-- Standard-Template für alle existierenden Räume
UPDATE rooms SET 
    planning_template = 'Um Ihre Veranstaltung besser planen zu können, füllen Sie bitte folgende Punkte aus:

Veranstaltungstitel:

Programmablauf:
  Aufbau:
  Einlass:
  Beginn:
  Ende:
  Abbau:

Besondere Wünsche oder Anmerkungen:

Öffentliche Veranstaltung: Ja/Nein
Wenn Ja, soll die Veranstaltung auf unsere Website: Schicken Sie uns gerne einen Text und Bild per E-Mail.'
WHERE planning_template IS NULL;
