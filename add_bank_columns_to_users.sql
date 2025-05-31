-- Add bank columns to users table
ALTER TABLE users ADD COLUMN bank_code VARCHAR(50) DEFAULT NULL;
ALTER TABLE users ADD COLUMN bank_name VARCHAR(100) DEFAULT NULL; 