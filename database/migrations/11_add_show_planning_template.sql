-- Migration: Veranstaltungsplanung aktivieren/deaktivieren
-- Erstellt: 2025-12-10
-- Beschreibung: Fügt show_planning_template Boolean zu rooms Tabelle hinzu

SET NAMES utf8mb4;

ALTER TABLE rooms 
ADD COLUMN show_planning_template TINYINT(1) DEFAULT 1 AFTER planning_template;
