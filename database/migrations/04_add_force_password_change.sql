-- Migration: Force Password Change für Admin Users
-- Datum: 2025-11-11

ALTER TABLE admin_users 
ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) DEFAULT 0 COMMENT 'Passwortänderung bei nächstem Login erforderlich' 
AFTER active;
