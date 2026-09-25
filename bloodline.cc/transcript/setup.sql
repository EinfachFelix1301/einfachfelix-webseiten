-- Bloodline Transcript Database Setup
-- Run this script to create the required database tables

-- Create database (if needed)
CREATE DATABASE IF NOT EXISTS bloodline_transcripts
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bloodline_transcripts;

-- Transcripts table
CREATE TABLE IF NOT EXISTS transcripts (
    id VARCHAR(36) PRIMARY KEY,
    ticket_number VARCHAR(10) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'support',
    html_content LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    closed_at DATETIME NOT NULL,
    closed_by BIGINT UNSIGNED DEFAULT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_closed_at (closed_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins table (optional - can also use config.php)
CREATE TABLE IF NOT EXISTS admins (
    discord_id BIGINT UNSIGNED PRIMARY KEY,
    added_by BIGINT UNSIGNED DEFAULT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin (replace with actual Discord ID)
-- INSERT INTO admins (discord_id, notes) VALUES (123456789012345678, 'Owner');

-- Optional: Create index for full-text search
-- ALTER TABLE transcripts ADD FULLTEXT INDEX ft_search (ticket_number, user_name);
