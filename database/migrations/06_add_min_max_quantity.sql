-- Migration: Min/Max Quantity für Booking Options
-- Datum: 2025-11-11

ALTER TABLE booking_options 
ADD COLUMN IF NOT EXISTS min_quantity INT DEFAULT 1 COMMENT 'Mindestmenge für diese Option' 
AFTER allow_quantity;

ALTER TABLE booking_options 
ADD COLUMN IF NOT EXISTS max_quantity INT DEFAULT NULL COMMENT 'Maximalmenge für diese Option (NULL = unbegrenzt)' 
AFTER min_quantity;
