-- Add bank columns to bookings table
ALTER TABLE bookings ADD COLUMN bank_code VARCHAR(50) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN bank_name VARCHAR(100) DEFAULT NULL; 