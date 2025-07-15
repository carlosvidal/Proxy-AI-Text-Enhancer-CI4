-- Manual SQL script to fix buttons table structure
-- Execute this directly in SQLite if migration fails

-- Add temperature column if it doesn't exist
-- Note: SQLite doesn't support IF NOT EXISTS for ALTER TABLE, so we'll need to check manually

-- First, let's see the current table structure
.schema buttons

-- Add temperature column (run this only if temperature column doesn't exist)
ALTER TABLE buttons ADD COLUMN temperature DECIMAL(3,2) DEFAULT 0.70 NOT NULL;

-- Add active column (run this only if active column doesn't exist)  
ALTER TABLE buttons ADD COLUMN active TINYINT(1) DEFAULT 1 NOT NULL;

-- Check the updated structure
.schema buttons

-- Show all columns
PRAGMA table_info(buttons);