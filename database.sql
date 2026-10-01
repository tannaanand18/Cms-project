-- Online Complaint Management System (CMS) Database Schema
-- Database Name: complaint_db
-- Compatible with MySQL 5.7+ / MariaDB 10.x+ (XAMPP Default)

CREATE DATABASE IF NOT EXISTS `complaint_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `complaint_db`;

-- Drop tables if they exist to prevent foreign key errors on reinstall
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `complaint_logs`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Supports both regular Citizens/Users and Administrators)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Complaint Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Complaints Table
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tracking_code` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
  `status` ENUM('Pending', 'In Progress', 'Resolved', 'Rejected') DEFAULT 'Pending',
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `attachment_name` VARCHAR(255) DEFAULT NULL,
  `admin_remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_complaint_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_complaint_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Complaint Audit / Status Logs
CREATE TABLE `complaint_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `action_by` INT NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `remark` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SEED INITIAL DATA
-- ==========================================================

-- Insert Default Categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Public Infrastructure', 'Issues related to roads, streetlights, bridges, and public works'),
('Sanitation & Waste Management', 'Garbage collection, sewage blockages, and public cleanliness'),
('Water & Electricity Supply', 'Water shortages, pipe leaks, power outages, and voltage issues'),
('Billing & Payments', 'Discrepancies in civic taxes, municipal utility bills, and payment errors'),
('Public Safety & Security', 'Traffic violations, vandalism, and public neighborhood hazards'),
('Other Services', 'General inquiries, feedback, and miscellaneous issues');

-- Insert Default Accounts:
-- Admin Password: password_hash('admin123', PASSWORD_DEFAULT)
-- User Password:  password_hash('user123', PASSWORD_DEFAULT)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `role`) VALUES
('System Administrator', 'admin@cms.com', '$2y$12$pexqbIRgn/3emxmhmFs1V.gHSCkQDSq1MO91yZNzL2A1UqaVF5ek.', '9876543210', 'Headquarters, Admin Block', 'admin'),
('John Citizen', 'user@cms.com', '$2y$12$DkJbdR7Ccto31aWSm.njeu/6V83SqyR7uoDYhllyC3dP2LpTJ2Fl.', '9123456780', '42 Maple Street, Sector 5', 'user');

-- Insert Sample Complaints
INSERT INTO `complaints` (`tracking_code`, `user_id`, `category_id`, `title`, `description`, `priority`, `status`, `admin_remarks`, `created_at`) VALUES
('CMP-2026-7841', 2, 1, 'Deep pothole on Main Avenue near Central Park', 'There is a dangerous deep pothole near the crossing of 4th street and Main Ave. It has caused traffic bottlenecks and nearly tripped two bikers.', 'High', 'In Progress', 'Dispatched municipal road repair team. Inspection completed on morning shift.', '2026-09-28 09:30:00'),
('CMP-2026-9210', 2, 3, 'Continuous low water pressure in Sector 5', 'For the past 4 days, pipeline water pressure has dropped significantly during peak morning hours (6am - 8am).', 'Medium', 'Pending', NULL, '2026-09-29 14:15:00'),
('CMP-2026-6432', 2, 2, 'Irregular garbage pickup in Residential Block B', 'Trash bins are overflowing since Sunday. Stray animals are scattering waste on the pedestrian sidewalk.', 'Urgent', 'Resolved', 'Sanitation truck deployed. Waste cleared and area disinfected. Service schedule restored.', '2026-09-25 11:00:00');

-- Insert Sample Logs for Complaints
INSERT INTO `complaint_logs` (`complaint_id`, `action_by`, `old_status`, `new_status`, `remark`, `created_at`) VALUES
(1, 2, NULL, 'Pending', 'Complaint submitted by user', '2026-09-28 09:30:00'),
(1, 1, 'Pending', 'In Progress', 'Assigned road maintenance contractor team #4.', '2026-09-28 15:40:00'),
(3, 2, NULL, 'Pending', 'Complaint submitted by user', '2026-09-25 11:00:00'),
(3, 1, 'Pending', 'In Progress', 'Dispatched emergency sanitation vehicle.', '2026-09-25 13:20:00'),
(3, 1, 'In Progress', 'Resolved', 'Waste collected, sanitized, and completed ticket.', '2026-09-26 10:00:00');
