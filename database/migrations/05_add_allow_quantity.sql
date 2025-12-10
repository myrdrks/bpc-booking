-- Migration: Allow Quantity für Booking Options
-- Datum: 2025-11-11

ALTER TABLE booking_options 
ADD COLUMN IF NOT EXISTS allow_quantity TINYINT(1) DEFAULT 1 COMMENT 'Erlaubt Mengenangabe für diese Option' 
AFTER apply_to_rooms;
