-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `role` ENUM('admin', 'reseller', 'user') DEFAULT 'user',
  `parent_id` INT,
  `credits` INT DEFAULT 0,
  `monthly_limit` INT DEFAULT 1000,
  `used_this_month` INT DEFAULT 0,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `api_key` VARCHAR(255) UNIQUE,
  `api_secret` VARCHAR(255),
  `whatsapp_status` VARCHAR(50) DEFAULT 'disconnected',
  `whatsapp_connected_at` DATETIME,
  `whatsapp_enabled` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `parent_id` (`parent_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Templates Table
CREATE TABLE IF NOT EXISTS `templates` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `template_name` VARCHAR(100) NOT NULL,
  `template_content` TEXT NOT NULL,
  `approved` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Campaigns Table
CREATE TABLE IF NOT EXISTS `campaigns` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `campaign_name` VARCHAR(100) NOT NULL,
  `template_id` INT,
  `total_contacts` INT DEFAULT 0,
  `sent_count` INT DEFAULT 0,
  `failed_count` INT DEFAULT 0,
  `message` TEXT,
  `status` ENUM('draft', 'running', 'completed', 'failed') DEFAULT 'draft',
  `scheduled_time` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
);

-- Campaign Contacts Table
CREATE TABLE IF NOT EXISTS `campaign_contacts` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `campaign_id` INT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100),
  `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  `response` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE
);

-- Messages Table
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('sent', 'delivered', 'failed') DEFAULT 'sent',
  `external_id` VARCHAR(100),
  `type` VARCHAR(20) DEFAULT 'api',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Credits Table
CREATE TABLE IF NOT EXISTS `credits` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `amount` INT NOT NULL,
  `type` ENUM('purchase', 'used', 'refund') DEFAULT 'purchase',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `credits` INT NOT NULL,
  `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `payment_id` VARCHAR(100),
  `provider` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Create default admin user (password: admin)
INSERT INTO users (username, email, password, role, credits, status) VALUES
('admin', 'admin@example.com', '$2y$10$YIjlrsTm.J9jLp1P5W0W.e0oF1WkMyE6Y.0l5p1R5y1m1J4nF6QAa', 'admin', 10000, 'active');
