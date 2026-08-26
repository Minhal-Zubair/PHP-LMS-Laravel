-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 19, 2026 at 01:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user_db`
--
CREATE DATABASE IF NOT EXISTS `user_db` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `user_db`;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `course_link` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `user_id`, `course_name`, `instructor`, `category`, `progress`, `created_at`, `course_link`) VALUES
(2, 1, 'Java Programming', 'Prof. John Smith', 'Programming', 65, '2026-07-17 07:17:08', 'https://www.youtube.com/watch?v=eIrMbAQSU34'),
(3, 1, 'Database Systems', 'Prof. Dan', 'Database', 40, '2026-07-17 07:17:08', 'https://www.youtube.com/watch?v=kDiZkmAzevY'),
(4, 1, 'Artificial Intelligence', 'Dr. Emily Stone', 'AI', 20, '2026-07-17 07:17:08', 'https://www.youtube.com/watch?v=JMUxmLyrhSk'),
(5, 2, 'Java Prog', 'Prof Emily', 'Prog Lang', 0, '2026-07-17 07:32:51', 'https://www.youtube.com/watch?v=kDiZkmAzevY');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `subject` varchar(100) NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `user_id`, `day`, `subject`, `room`, `start_time`, `end_time`, `created_at`) VALUES
(1, 1, 'Monday', 'Data Structures', 'R-201', '08:00:00', '10:00:00', '2026-07-17 06:23:09'),
(2, 1, 'Monday', 'Programming Fundamentals', 'Lab-1', '10:00:00', '12:00:00', '2026-07-17 06:23:09'),
(3, 1, 'Monday', 'Break', '-', '12:00:00', '13:00:00', '2026-07-17 06:23:09'),
(4, 1, 'Monday', 'Database Systems', 'R-105', '13:00:00', '15:00:00', '2026-07-17 06:23:09'),
(5, 1, 'Monday', 'Computer Networks', 'R-302', '15:00:00', '17:00:00', '2026-07-17 06:23:09'),
(6, 1, 'Tuesday', 'Object Oriented Programming', 'Lab-2', '08:00:00', '10:00:00', '2026-07-17 06:23:09'),
(7, 1, 'Tuesday', 'Operating Systems', 'R-205', '10:00:00', '11:00:00', '2026-07-17 06:23:09'),
(8, 1, 'Tuesday', 'Software Engineering', 'R-210', '11:00:00', '12:00:00', '2026-07-17 06:23:09'),
(9, 1, 'Tuesday', 'Break', '-', '12:00:00', '13:00:00', '2026-07-17 06:23:09'),
(10, 1, 'Tuesday', 'Web Development', 'Lab-3', '13:00:00', '15:00:00', '2026-07-17 06:23:09'),
(11, 1, 'Tuesday', 'Digital Logic Design', 'R-108', '15:00:00', '17:00:00', '2026-07-17 06:23:09'),
(12, 1, 'Wednesday', 'English Communication', 'R-110', '08:00:00', '09:00:00', '2026-07-17 06:23:09'),
(13, 1, 'Wednesday', 'Artificial Intelligence', 'R-305', '09:00:00', '11:00:00', '2026-07-17 06:23:09'),
(14, 1, 'Wednesday', 'Computer Graphics', 'Lab-4', '11:00:00', '12:00:00', '2026-07-17 06:23:09'),
(15, 1, 'Wednesday', 'Break', '-', '12:00:00', '13:00:00', '2026-07-17 06:23:09'),
(16, 1, 'Wednesday', 'Java Programming', 'Lab-1', '13:00:00', '15:00:00', '2026-07-17 06:23:09'),
(17, 1, 'Wednesday', 'Data Communication', 'R-215', '15:00:00', '17:00:00', '2026-07-17 06:23:09'),
(18, 1, 'Thursday', 'Database Lab', 'Lab-2', '08:00:00', '10:00:00', '2026-07-17 06:23:09'),
(19, 1, 'Thursday', 'Computer Architecture', 'R-104', '10:00:00', '11:00:00', '2026-07-17 06:23:09'),
(20, 1, 'Thursday', 'Cyber Security', 'R-301', '11:00:00', '12:00:00', '2026-07-17 06:23:09'),
(21, 1, 'Thursday', 'Break', '-', '12:00:00', '13:00:00', '2026-07-17 06:23:09'),
(22, 1, 'Thursday', 'Mobile Application Development', 'Lab-5', '13:00:00', '15:00:00', '2026-07-17 06:23:09'),
(23, 1, 'Thursday', 'Cloud Computing', 'R-208', '15:00:00', '17:00:00', '2026-07-17 06:23:09'),
(24, 1, 'Friday', 'Machine Learning', 'R-402', '08:00:00', '10:00:00', '2026-07-17 06:23:09'),
(25, 1, 'Friday', 'Software Testing', 'R-204', '10:00:00', '11:00:00', '2026-07-17 06:23:09'),
(26, 1, 'Friday', 'Human Computer Interaction', 'R-102', '11:00:00', '12:00:00', '2026-07-17 06:23:09'),
(27, 1, 'Friday', 'Break', '-', '12:00:00', '13:00:00', '2026-07-17 06:23:09'),
(28, 1, 'Friday', 'PHP & Laravel', 'Lab-6', '13:00:00', '15:00:00', '2026-07-17 06:23:09'),
(29, 1, 'Friday', 'Project Work', 'Project Lab', '15:00:00', '17:00:00', '2026-07-17 06:23:09'),
(30, 1, 'Saturday', 'Python Programming', 'Lab-1', '08:00:00', '10:00:00', '2026-07-17 06:23:09'),
(31, 1, 'Saturday', 'DevOps Fundamentals', 'R-303', '10:00:00', '11:00:00', '2026-07-17 06:23:09'),
(32, 1, 'Saturday', 'Computer Ethics', 'R-106', '11:00:00', '12:00:00', '2026-07-17 06:23:09'),
(33, 1, 'Saturday', 'Break', '-', '12:00:00', '13:00:00', '2026-07-17 06:23:09'),
(34, 1, 'Saturday', 'Final Year Project', 'Project Lab', '13:00:00', '15:00:00', '2026-07-17 06:23:09'),
(35, 1, 'Saturday', 'Seminar & Presentation', 'Seminar Hall', '15:00:00', '17:00:00', '2026-07-17 06:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `course_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `estimated_hours` decimal(4,2) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  KEY `due_date` (`due_date`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `task_name`, `status`, `created_at`) VALUES
(1, 1, 'Math Assignment', 'Pending', '2026-07-02 12:03:04'),
(3, 1, 'Science Task', 'Pending', '2026-07-02 12:05:30'),
(4, 1, 'Statistics Task', 'Pending', '2026-07-06 11:37:36'),
(5, 2, 'Math Assignment', 'Completed', '2026-07-17 06:42:48'),
(6, 2, 'Science Task', 'Pending', '2026-07-17 06:45:20'),
(7, 2, 'Statistics Task', 'Pending', '2026-07-17 06:45:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `gender`, `phone`, `address`, `created_at`) VALUES
(1, 'Minhal Zubair', 'minhalzubair@gmail.com', 'minhalzubair', '$2y$12$RLHDFswuB7LciErFkkXx.er8mUrMUU.yWJFX67Z.tXuM3dKs9DUgm', 'Female', '03333333333', 'ISB, PK', '2026-07-02 11:51:27'),
(2, 'Minhal Zubair', 'minhalzubair.work@gmail.com', 'minhal', '$2y$10$UTvd2/3oEZVVf0cd8xtEt.6m6jES9qYmjhz4otih1MMtfFfPXHKQW', 'Female', '033333333', 'LHR\r\n', '2026-07-06 12:21:10'),
(3, 'User Name ', 'username@gmail.com', 'username', '$2y$10$VtTpxfhDDxuZTeI1GdasrODd8Z8uU2gPQXVTS6soDEqB02fTf1bIu', 'Male', '033333', 'La\r\n', '2026-07-07 05:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
-- Added to support secure, server-verified "Remember Me" logins
-- (selector/validator pattern) instead of trusting raw cookie values.
--

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(24) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
-- Supports the forgot-password flow: a short-lived, single-use, hashed
-- token per reset request (never store the raw token itself).
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;