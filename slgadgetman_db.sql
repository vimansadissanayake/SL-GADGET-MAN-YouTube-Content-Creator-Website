-- 1. Create the Database
CREATE DATABASE IF NOT EXISTS `slgadgetman_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `slgadgetman_db`;

-- --------------------------------------------------------

-- 2. Create the 'videos' table
-- Stores YouTube video metadata for the homepage and admin panel
CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `youtube_url` VARCHAR(500) NOT NULL,
    `thumbnail` VARCHAR(500) NOT NULL,
    `views` INT(11) DEFAULT 0,
    `duration` VARCHAR(20) DEFAULT '0:00',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_url` (`youtube_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- 3. Create the 'contact_messages' table
-- Stores messages submitted via the contact form on index.php
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- 4. Clean up any existing duplicates (in case they exist)
-- This will keep only the first occurrence of each video
DELETE v1 FROM videos v1
INNER JOIN videos v2 
WHERE v1.id > v2.id 
AND v1.youtube_url = v2.youtube_url;

-- --------------------------------------------------------

-- Note: Sample videos should be added via setup.php or admin panel
-- to ensure proper duplicate checking