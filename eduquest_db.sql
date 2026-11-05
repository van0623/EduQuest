-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 10:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eduquest_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int(10) UNSIGNED NOT NULL,
  `achievement_name` varchar(150) NOT NULL,
  `achievement_description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `requirement_type` varchar(50) DEFAULT NULL,
  `requirement_value` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`achievement_id`, `achievement_name`, `achievement_description`, `icon`, `requirement_type`, `requirement_value`, `status`, `created_at`) VALUES
(1, 'First Steps', 'Complete your first lesson.', 'footprints', 'lessons_completed', 1, 'active', '2026-10-14 10:44:10'),
(2, 'Mission Starter', 'Complete your first mission.', 'target', 'missions_completed', 1, 'active', '2026-10-14 10:44:10'),
(3, 'Challenge Solver', 'Pass your first challenge.', 'trophy', 'challenges_passed', 1, 'active', '2026-10-14 10:44:10'),
(4, 'Course Master', 'Complete all lessons in a course.', 'graduation-cap', 'courses_completed', 1, 'active', '2026-10-14 10:44:10'),
(5, 'Knowledge Seeker', 'Complete 5 lessons.', 'book-open', 'lessons_completed', 5, 'active', '2026-11-04 11:38:26'),
(6, 'Quick Learner', 'Complete 10 lessons.', 'graduation-cap', 'lessons_completed', 10, 'active', '2026-11-04 11:38:26'),
(7, 'Mission Explorer', 'Complete 3 missions.', 'target', 'missions_completed', 3, 'active', '2026-11-04 11:38:26'),
(8, 'Mission Master', 'Complete 5 missions.', 'flag', 'missions_completed', 5, 'active', '2026-11-04 11:38:26'),
(9, 'Activity Beginner', 'Pass your first activity.', 'clipboard-check', 'activities_passed', 1, 'active', '2026-11-04 11:38:26'),
(10, 'Activity Expert', 'Pass 5 activities.', 'medal', 'activities_passed', 5, 'active', '2026-11-04 11:38:26'),
(11, 'Perfect Score', 'Earn a perfect score on an activity.', 'star', 'perfect_activity_score', 1, 'active', '2026-11-04 11:38:26'),
(12, 'Challenge Champion', 'Pass 5 challenges.', 'trophy', 'challenges_passed', 5, 'active', '2026-11-04 11:38:26'),
(13, 'Final Quest Champion', 'Pass your first Final Quest.', 'crown', 'final_quests_passed', 1, 'active', '2026-11-04 11:38:26'),
(14, 'Ultimate Learner', 'Pass all 3 published Final Quests.', 'award', 'final_quests_passed', 3, 'active', '2026-11-04 11:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `activity_id` int(10) UNSIGNED NOT NULL,
  `mission_id` int(10) UNSIGNED NOT NULL,
  `activity_title` varchar(200) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `activity_type` enum('quiz','short_answer','task','reflection') NOT NULL DEFAULT 'quiz',
  `total_points` smallint(5) UNSIGNED NOT NULL DEFAULT 10,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 70.00,
  `activity_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`activity_id`, `mission_id`, `activity_title`, `activity_description`, `instructions`, `activity_type`, `total_points`, `passing_score`, `activity_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Office Administration Basics', 'Check your understanding of office administration.', 'Answer the questions based on the lesson. Choose the best answer for each question.', 'quiz', 60, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-16 11:00:56'),
(2, 2, 'Organize the Office', 'Review common office procedures and organization.', 'Read each situation and select the most appropriate office practice.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:44:37'),
(3, 3, 'Choose a Filing System', 'Practice selecting a suitable filing method.', 'Match each record situation with the filing method that best fits it.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:44:59'),
(4, 4, 'Records and Professional Conduct', 'Review record handling and professional behavior.', 'Choose the most responsible action for each situation.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:45:14'),
(5, 5, 'Business Communication Check', 'Review the principles of clear communication.', 'Select the answer that best demonstrates effective business communication.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:46:29'),
(6, 6, 'Email and Letter Practice', 'Review professional email and letter structure.', 'Identify the most appropriate format or practice for each message.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:46:40'),
(7, 7, 'Telephone Etiquette Practice', 'Review professional telephone communication.', 'Choose the most professional response to each caller situation.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:47:10'),
(8, 8, 'Clear Correspondence Check', 'Review clear and organized business writing.', 'Choose the message-writing practice that best fits each situation.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:47:26'),
(9, 9, 'Productivity Tools Check', 'Review useful office productivity habits.', 'Select the practice that improves efficiency without reducing quality.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:54:33'),
(10, 10, 'Document Formatting Check', 'Review basic document preparation.', 'Choose the formatting practice that makes a document clear and consistent.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:54:47'),
(11, 11, 'Spreadsheet Basics Check', 'Review spreadsheet structure and accurate data entry.', 'Answer the questions about cells, rows, columns, and basic formulas.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:55:00'),
(12, 12, 'Protect the Records', 'Review records management and data protection.', 'Choose the safest and most appropriate action for each situation.', 'quiz', 100, 70.00, 1, 'published', '2026-10-14 10:44:09', '2026-10-15 02:55:13');

-- --------------------------------------------------------

--
-- Table structure for table `activity_answers`
--

CREATE TABLE `activity_answers` (
  `answer_id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_option_id` int(10) UNSIGNED DEFAULT NULL,
  `answer_text` longtext DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_awarded` decimal(6,2) NOT NULL DEFAULT 0.00,
  `answered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_answers`
--

INSERT INTO `activity_answers` (`answer_id`, `attempt_id`, `question_id`, `selected_option_id`, `answer_text`, `is_correct`, `points_awarded`, `answered_at`) VALUES
(1, 1, 7, 25, NULL, 1, 5.00, '2026-10-14 19:22:06'),
(2, 1, 8, 31, NULL, 0, 0.00, '2026-10-14 19:22:06'),
(3, 2, 7, 25, NULL, 1, 5.00, '2026-10-14 19:32:07'),
(4, 2, 8, 30, NULL, 0, 0.00, '2026-10-14 19:32:07'),
(5, 3, 7, 25, NULL, 1, 5.00, '2026-10-14 20:06:42'),
(6, 3, 8, 29, NULL, 1, 5.00, '2026-10-14 20:06:42'),
(7, 4, 7, 25, NULL, 1, 5.00, '2026-10-15 10:17:55'),
(8, 4, 8, 30, NULL, 0, 0.00, '2026-10-15 10:17:55'),
(9, 5, 7, 25, NULL, 1, 5.00, '2026-10-15 10:58:48'),
(10, 5, 8, 29, NULL, 1, 5.00, '2026-10-15 10:58:48'),
(11, 5, 45, 155, NULL, 1, 10.00, '2026-10-15 10:58:48'),
(12, 5, 46, 159, NULL, 1, 10.00, '2026-10-15 10:58:48'),
(13, 5, 47, 164, NULL, 0, 0.00, '2026-10-15 10:58:48'),
(14, 5, 48, NULL, 'True', 1, 5.00, '2026-10-15 10:58:48'),
(15, 5, 49, NULL, 'True', 0, 0.00, '2026-10-15 10:58:48'),
(16, 5, 50, NULL, 'ending', 0, 0.00, '2026-10-15 10:58:48'),
(17, 5, 51, NULL, '[168,171,170,167,169]', 0, 0.00, '2026-10-15 10:58:48'),
(18, 5, 52, NULL, '{\"172\":\"Reporting a mistake instead of hiding it\",\"173\":\"Arriving on time for work and meetings\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 0, 5.00, '2026-10-15 10:58:48'),
(19, 5, 53, NULL, '[\"information\",\"confidentiality\"]', 0, 0.00, '2026-10-15 10:58:48'),
(20, 5, 54, NULL, '[\"mistakes\",\"decisions\"]', 0, 5.00, '2026-10-15 10:58:48'),
(21, 6, 7, 25, NULL, 1, 5.00, '2026-10-15 11:23:30'),
(22, 6, 8, 29, NULL, 1, 5.00, '2026-10-15 11:23:30'),
(23, 6, 45, 155, NULL, 1, 10.00, '2026-10-15 11:23:30'),
(24, 6, 46, 159, NULL, 1, 10.00, '2026-10-15 11:23:30'),
(25, 6, 47, 163, NULL, 1, 10.00, '2026-10-15 11:23:30'),
(26, 6, 48, NULL, 'True', 1, 5.00, '2026-10-15 11:23:30'),
(27, 6, 49, NULL, 'False', 1, 5.00, '2026-10-15 11:23:30'),
(28, 6, 50, NULL, 'na', 0, 0.00, '2026-10-15 11:23:30'),
(29, 6, 51, NULL, '[171,167,169,168,170]', 0, 2.00, '2026-10-15 11:23:30'),
(30, 6, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Helping a colleague meet a deadline\",\"174\":\"Reporting a mistake instead of hiding it\",\"175\":\"Not sharing private client details\"}', 0, 2.50, '2026-10-15 11:23:30'),
(31, 6, 53, NULL, '[\"gossip\",\"information\"]', 0, 5.00, '2026-10-15 11:23:30'),
(32, 6, 54, NULL, '[\"records\",\"rumors\"]', 0, 5.00, '2026-10-15 11:23:30'),
(33, 7, 7, 25, NULL, 1, 5.00, '2026-10-15 11:37:58'),
(34, 7, 8, 29, NULL, 1, 5.00, '2026-10-15 11:37:58'),
(35, 7, 45, 155, NULL, 1, 10.00, '2026-10-15 11:37:58'),
(36, 7, 46, 159, NULL, 1, 10.00, '2026-10-15 11:37:58'),
(37, 7, 47, 163, NULL, 1, 10.00, '2026-10-15 11:37:58'),
(38, 7, 48, NULL, 'True', 1, 5.00, '2026-10-15 11:37:58'),
(39, 7, 49, NULL, 'False', 1, 5.00, '2026-10-15 11:37:58'),
(40, 7, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 11:37:58'),
(41, 7, 51, NULL, '[168,171,170,169,167]', 0, 0.00, '2026-10-15 11:37:58'),
(42, 7, 52, NULL, '{\"172\":\"Reporting a mistake instead of hiding it\",\"173\":\"Arriving on time for work and meetings\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 0, 5.00, '2026-10-15 11:37:58'),
(43, 7, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-10-15 11:37:58'),
(44, 7, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 11:37:58'),
(45, 8, 7, 25, NULL, 1, 5.00, '2026-10-15 11:56:05'),
(46, 8, 8, 29, NULL, 1, 5.00, '2026-10-15 11:56:05'),
(47, 8, 45, 155, NULL, 1, 10.00, '2026-10-15 11:56:05'),
(48, 8, 46, 159, NULL, 1, 10.00, '2026-10-15 11:56:05'),
(49, 8, 47, 163, NULL, 1, 10.00, '2026-10-15 11:56:05'),
(50, 8, 48, NULL, 'True', 1, 5.00, '2026-10-15 11:56:05'),
(51, 8, 49, NULL, 'False', 1, 5.00, '2026-10-15 11:56:05'),
(52, 8, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 11:56:05'),
(53, 8, 51, NULL, '[167,169,168,170,171]', 0, 6.00, '2026-10-15 11:56:05'),
(54, 8, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-10-15 11:56:05'),
(55, 8, 53, NULL, '[\"information\",\"confidentiality\"]', 0, 0.00, '2026-10-15 11:56:05'),
(56, 8, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 11:56:05'),
(57, 9, 7, 25, NULL, 1, 5.00, '2026-10-15 12:08:22'),
(58, 9, 8, 29, NULL, 1, 5.00, '2026-10-15 12:08:22'),
(59, 9, 45, 155, NULL, 1, 10.00, '2026-10-15 12:08:22'),
(60, 9, 46, 159, NULL, 1, 10.00, '2026-10-15 12:08:22'),
(61, 9, 47, 163, NULL, 1, 10.00, '2026-10-15 12:08:22'),
(62, 9, 48, NULL, 'True', 1, 5.00, '2026-10-15 12:08:22'),
(63, 9, 49, NULL, 'False', 1, 5.00, '2026-10-15 12:08:22'),
(64, 9, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 12:08:22'),
(65, 9, 51, NULL, '[167,170,168,169,171]', 0, 4.00, '2026-10-15 12:08:22'),
(66, 9, 52, NULL, '{\"172\":\"Helping a colleague meet a deadline\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Arriving on time for work and meetings\"}', 0, 5.00, '2026-10-15 12:08:22'),
(67, 9, 53, NULL, '[\"information\",\"confidentiality\"]', 0, 0.00, '2026-10-15 12:08:22'),
(68, 9, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 12:08:22'),
(69, 10, 7, 25, NULL, 1, 5.00, '2026-10-15 13:38:59'),
(70, 10, 8, 29, NULL, 1, 5.00, '2026-10-15 13:38:59'),
(71, 10, 45, 155, NULL, 1, 10.00, '2026-10-15 13:38:59'),
(72, 10, 46, 159, NULL, 1, 10.00, '2026-10-15 13:38:59'),
(73, 10, 47, 163, NULL, 1, 10.00, '2026-10-15 13:38:59'),
(74, 10, 48, NULL, 'True', 1, 5.00, '2026-10-15 13:38:59'),
(75, 10, 49, NULL, 'False', 1, 5.00, '2026-10-15 13:38:59'),
(76, 10, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 13:38:59'),
(77, 10, 51, NULL, '[167,169,168,170,171]', 0, 6.00, '2026-10-15 13:38:59'),
(78, 10, 52, NULL, '{\"172\":\"Helping a colleague meet a deadline\",\"173\":\"Arriving on time for work and meetings\",\"174\":\"Not sharing private client details\",\"175\":\"Reporting a mistake instead of hiding it\"}', 0, 2.50, '2026-10-15 13:38:59'),
(79, 10, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-10-15 13:38:59'),
(80, 10, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 13:38:59'),
(81, 11, 7, 25, NULL, 1, 5.00, '2026-10-15 13:45:27'),
(82, 11, 8, 29, NULL, 1, 5.00, '2026-10-15 13:45:27'),
(83, 11, 45, 155, NULL, 1, 10.00, '2026-10-15 13:45:27'),
(84, 11, 46, 159, NULL, 1, 10.00, '2026-10-15 13:45:27'),
(85, 11, 47, 163, NULL, 1, 10.00, '2026-10-15 13:45:27'),
(86, 11, 48, NULL, 'True', 1, 5.00, '2026-10-15 13:45:27'),
(87, 11, 49, NULL, 'False', 1, 5.00, '2026-10-15 13:45:27'),
(88, 11, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 13:45:27'),
(89, 11, 51, NULL, '[167,168,169,170,171]', 1, 10.00, '2026-10-15 13:45:27'),
(90, 11, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-10-15 13:45:27'),
(91, 11, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-10-15 13:45:27'),
(92, 11, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 13:45:27'),
(93, 12, 7, 25, NULL, 1, 5.00, '2026-10-15 13:52:52'),
(94, 12, 8, 29, NULL, 1, 5.00, '2026-10-15 13:52:52'),
(95, 12, 45, 155, NULL, 1, 10.00, '2026-10-15 13:52:52'),
(96, 12, 46, 159, NULL, 1, 10.00, '2026-10-15 13:52:52'),
(97, 12, 47, 163, NULL, 1, 10.00, '2026-10-15 13:52:52'),
(98, 12, 48, NULL, 'True', 1, 5.00, '2026-10-15 13:52:52'),
(99, 12, 49, NULL, 'False', 1, 5.00, '2026-10-15 13:52:52'),
(100, 12, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 13:52:52'),
(101, 12, 51, NULL, '[167,168,170,169,171]', 0, 6.00, '2026-10-15 13:52:52'),
(102, 12, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-10-15 13:52:52'),
(103, 12, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-10-15 13:52:52'),
(104, 12, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 13:52:52'),
(105, 13, 7, 25, NULL, 1, 5.00, '2026-10-15 14:00:37'),
(106, 13, 8, 29, NULL, 1, 5.00, '2026-10-15 14:00:37'),
(107, 13, 45, 155, NULL, 1, 10.00, '2026-10-15 14:00:37'),
(108, 13, 46, 159, NULL, 1, 10.00, '2026-10-15 14:00:37'),
(109, 13, 47, 163, NULL, 1, 10.00, '2026-10-15 14:00:37'),
(110, 13, 48, NULL, 'True', 1, 5.00, '2026-10-15 14:00:37'),
(111, 13, 49, NULL, 'False', 1, 5.00, '2026-10-15 14:00:37'),
(112, 13, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 14:00:37'),
(113, 13, 51, NULL, '[167,168,169,170,171]', 1, 10.00, '2026-10-15 14:00:37'),
(114, 13, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-10-15 14:00:37'),
(115, 13, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-10-15 14:00:37'),
(116, 13, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 14:00:37'),
(117, 14, 7, 25, NULL, 1, 5.00, '2026-10-15 15:23:39'),
(118, 14, 8, 29, NULL, 1, 5.00, '2026-10-15 15:23:39'),
(119, 14, 45, 155, NULL, 1, 10.00, '2026-10-15 15:23:39'),
(120, 14, 46, 159, NULL, 1, 10.00, '2026-10-15 15:23:39'),
(121, 14, 47, 163, NULL, 1, 10.00, '2026-10-15 15:23:39'),
(122, 14, 48, NULL, 'True', 1, 5.00, '2026-10-15 15:23:39'),
(123, 14, 49, NULL, 'False', 1, 5.00, '2026-10-15 15:23:39'),
(124, 14, 50, NULL, 'shredding', 1, 10.00, '2026-10-15 15:23:39'),
(125, 14, 51, NULL, '[167,168,170,169,171]', 0, 6.00, '2026-10-15 15:23:39'),
(126, 14, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-10-15 15:23:39'),
(127, 14, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-10-15 15:23:39'),
(128, 14, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-10-15 15:23:39'),
(133, 17, 3, 9, NULL, 1, 5.00, '2026-10-15 20:59:31'),
(134, 17, 4, 13, NULL, 1, 5.00, '2026-10-15 20:59:31'),
(135, 17, 25, 97, NULL, 1, 10.00, '2026-10-15 20:59:31'),
(136, 17, 26, 101, NULL, 1, 10.00, '2026-10-15 20:59:31'),
(137, 17, 27, 105, NULL, 1, 10.00, '2026-10-15 20:59:31'),
(138, 17, 28, NULL, 'True', 1, 5.00, '2026-10-15 20:59:31'),
(139, 17, 29, NULL, 'False', 1, 5.00, '2026-10-15 20:59:31'),
(140, 17, 30, NULL, 'Out', 1, 10.00, '2026-10-15 20:59:31'),
(141, 17, 31, NULL, '[109,110,111,112,113]', 1, 10.00, '2026-10-15 20:59:31'),
(142, 17, 32, NULL, '{\"114\":\"Tracking what must be done\",\"115\":\"Storing paper, pens, and folders\",\"116\":\"Posting notices for staff\",\"117\":\"Keeping pens and small items tidy\"}', 0, 0.00, '2026-10-15 20:59:31'),
(143, 17, 33, NULL, '[\"hobby\",\"deadline\"]', 0, 5.00, '2026-10-15 20:59:31'),
(144, 17, 34, NULL, '[\"organized\",\"efficiently\"]', 1, 10.00, '2026-10-15 20:59:31'),
(145, 18, 5, 17, NULL, 1, 5.00, '2026-10-15 21:03:08'),
(146, 18, 6, 21, NULL, 1, 5.00, '2026-10-15 21:03:08'),
(147, 18, 35, 126, NULL, 1, 10.00, '2026-10-15 21:03:08'),
(148, 18, 36, 130, NULL, 1, 10.00, '2026-10-15 21:03:08'),
(149, 18, 37, 134, NULL, 1, 10.00, '2026-10-15 21:03:08'),
(150, 18, 38, NULL, 'True', 1, 5.00, '2026-10-15 21:03:08'),
(151, 18, 39, NULL, 'False', 1, 5.00, '2026-10-15 21:03:08'),
(152, 18, 40, NULL, 'na', 0, 0.00, '2026-10-15 21:03:08'),
(153, 18, 41, NULL, '[139,138,142,141,140]', 0, 2.00, '2026-10-15 21:03:08'),
(154, 18, 42, NULL, '{\"143\":\"By name from A to Z\",\"144\":\"By number order\",\"145\":\"By date\",\"146\":\"By topic\"}', 1, 10.00, '2026-10-15 21:03:08'),
(155, 18, 43, NULL, '[\"alphabetical\",\"numerical\"]', 0, 5.00, '2026-10-15 21:03:08'),
(156, 18, 44, NULL, '[\"label\",\"documents\"]', 1, 10.00, '2026-10-15 21:03:08'),
(157, 19, 9, 33, NULL, 1, 5.00, '2026-10-15 21:05:50'),
(158, 19, 10, 37, NULL, 1, 5.00, '2026-10-15 21:05:50'),
(159, 19, 55, 184, NULL, 1, 10.00, '2026-10-15 21:05:50'),
(160, 19, 56, 188, NULL, 1, 10.00, '2026-10-15 21:05:50'),
(161, 19, 57, 192, NULL, 1, 10.00, '2026-10-15 21:05:50'),
(162, 19, 58, NULL, 'True', 1, 5.00, '2026-10-15 21:05:50'),
(163, 19, 59, NULL, 'False', 1, 5.00, '2026-10-15 21:05:50'),
(164, 19, 60, NULL, 'na', 0, 0.00, '2026-10-15 21:05:50'),
(165, 19, 61, NULL, '[197,196,198,199,200]', 0, 6.00, '2026-10-15 21:05:50'),
(166, 19, 62, NULL, '{\"201\":\"Easy to understand\",\"202\":\"Polite and respectful\",\"203\":\"Short and to the point\",\"204\":\"Accurate facts and good grammar\"}', 0, 5.00, '2026-10-15 21:05:50'),
(167, 19, 63, NULL, '[\"sender\",\"receiver\"]', 1, 10.00, '2026-10-15 21:05:50'),
(168, 19, 64, NULL, '[\"concise\",\"time\"]', 1, 10.00, '2026-10-15 21:05:50'),
(171, 21, 9, 33, NULL, 1, 5.00, '2026-10-16 18:11:14'),
(172, 21, 10, 37, NULL, 1, 5.00, '2026-10-16 18:11:14'),
(173, 21, 55, 184, NULL, 1, 10.00, '2026-10-16 18:11:14'),
(174, 21, 56, 188, NULL, 1, 10.00, '2026-10-16 18:11:14'),
(175, 21, 57, 192, NULL, 1, 10.00, '2026-10-16 18:11:14'),
(176, 21, 58, NULL, 'True', 1, 5.00, '2026-10-16 18:11:14'),
(177, 21, 59, NULL, 'False', 1, 5.00, '2026-10-16 18:11:14'),
(178, 21, 60, NULL, 'feedback', 1, 10.00, '2026-10-16 18:11:14'),
(179, 21, 61, NULL, '[197,196,198,199,200]', 0, 6.00, '2026-10-16 18:11:14'),
(180, 21, 62, NULL, '{\"201\":\"Easy to understand\",\"202\":\"Short and to the point\",\"203\":\"Polite and respectful\",\"204\":\"Accurate facts and good grammar\"}', 1, 10.00, '2026-10-16 18:11:14'),
(181, 21, 63, NULL, '[\"sender\",\"receiver\"]', 1, 10.00, '2026-10-16 18:11:14'),
(182, 21, 64, NULL, '[\"concise\",\"time\"]', 1, 10.00, '2026-10-16 18:11:14'),
(183, 22, 135, 417, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(184, 22, 136, 421, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(185, 22, 137, 425, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(186, 22, 138, 429, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(187, 22, 139, 433, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(188, 22, 140, 437, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(189, 22, 141, 441, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(190, 22, 142, 445, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(191, 22, 143, 449, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(192, 22, 144, 453, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(193, 22, 145, 457, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(194, 22, 146, 461, NULL, 1, 5.00, '2026-10-16 19:02:10'),
(195, 23, 135, 417, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(196, 23, 136, 421, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(197, 23, 137, 425, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(198, 23, 138, 429, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(199, 23, 139, 433, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(200, 23, 140, 437, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(201, 23, 141, 441, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(202, 23, 142, 445, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(203, 23, 143, 449, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(204, 23, 144, 453, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(205, 23, 145, 457, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(206, 23, 146, 461, NULL, 1, 5.00, '2026-10-25 10:22:52'),
(207, 24, 135, 417, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(208, 24, 136, 421, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(209, 24, 137, 425, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(210, 24, 138, 429, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(211, 24, 139, 433, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(212, 24, 140, 437, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(213, 24, 141, 441, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(214, 24, 142, 445, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(215, 24, 143, 449, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(216, 24, 144, 453, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(217, 24, 145, 457, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(218, 24, 146, 461, NULL, 1, 5.00, '2026-10-29 11:46:19'),
(219, 25, 3, 9, NULL, 1, 2.50, '2026-11-03 11:19:29'),
(220, 25, 4, 13, NULL, 1, 5.00, '2026-11-03 11:19:29'),
(221, 25, 25, 97, NULL, 1, 10.00, '2026-11-03 11:19:29'),
(222, 25, 26, 101, NULL, 1, 10.00, '2026-11-03 11:19:29'),
(223, 25, 27, 105, NULL, 1, 10.00, '2026-11-03 11:19:29'),
(224, 25, 28, NULL, 'True', 1, 5.00, '2026-11-03 11:19:29'),
(225, 25, 29, NULL, 'False', 1, 5.00, '2026-11-03 11:19:29'),
(226, 25, 30, NULL, 'Out', 1, 10.00, '2026-11-03 11:19:29'),
(227, 25, 31, NULL, '[109,110,113,111,112]', 0, 4.00, '2026-11-03 11:19:29'),
(228, 25, 32, NULL, '{\"114\":\"Posting notices for staff\",\"115\":\"Keeping pens and small items tidy\",\"116\":\"Tracking what must be done\",\"117\":\"Storing paper, pens, and folders\"}', 1, 5.00, '2026-11-03 11:19:29'),
(229, 25, 33, NULL, '[\"to-do\",\"deadline\"]', 1, 10.00, '2026-11-03 11:19:29'),
(230, 25, 34, NULL, '[\"organized\",\"efficiently\"]', 1, 10.00, '2026-11-03 11:19:29'),
(231, 26, 135, 417, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(232, 26, 136, 421, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(233, 26, 137, 425, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(234, 26, 138, 429, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(235, 26, 139, 433, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(236, 26, 140, 437, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(237, 26, 141, 441, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(238, 26, 142, 445, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(239, 26, 143, 449, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(240, 26, 144, 453, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(241, 26, 145, 457, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(242, 26, 146, 461, NULL, 1, 5.00, '2026-11-03 16:01:00'),
(243, 27, 5, 17, NULL, 1, 5.00, '2026-11-03 21:47:35'),
(244, 27, 6, 21, NULL, 1, 5.00, '2026-11-03 21:47:35'),
(245, 27, 35, 126, NULL, 1, 10.00, '2026-11-03 21:47:35'),
(246, 27, 36, 130, NULL, 1, 10.00, '2026-11-03 21:47:35'),
(247, 27, 37, 134, NULL, 1, 10.00, '2026-11-03 21:47:35'),
(248, 27, 38, NULL, 'True', 1, 5.00, '2026-11-03 21:47:35'),
(249, 27, 39, NULL, 'False', 1, 5.00, '2026-11-03 21:47:35'),
(250, 27, 40, NULL, 'city', 0, 0.00, '2026-11-03 21:47:35'),
(251, 27, 41, NULL, '[138,141,139,142,140]', 0, 2.00, '2026-11-03 21:47:35'),
(252, 27, 42, NULL, '{\"143\":\"By name from A to Z\",\"144\":\"By number order\",\"145\":\"By date\",\"146\":\"By topic\"}', 1, 10.00, '2026-11-03 21:47:35'),
(253, 27, 43, NULL, '[\"chronological\",\"numerical\"]', 1, 5.00, '2026-11-03 21:47:35'),
(254, 27, 44, NULL, '[\"documents\",\"label\"]', 0, 0.00, '2026-11-03 21:47:35'),
(255, 28, 5, 17, NULL, 1, 5.00, '2026-11-03 21:52:56'),
(256, 28, 6, 21, NULL, 1, 5.00, '2026-11-03 21:52:56'),
(257, 28, 35, 126, NULL, 1, 10.00, '2026-11-03 21:52:56'),
(258, 28, 36, 130, NULL, 1, 10.00, '2026-11-03 21:52:56'),
(259, 28, 37, 134, NULL, 1, 10.00, '2026-11-03 21:52:56'),
(260, 28, 38, NULL, 'True', 1, 5.00, '2026-11-03 21:52:56'),
(261, 28, 39, NULL, 'False', 1, 5.00, '2026-11-03 21:52:56'),
(262, 28, 40, NULL, 'geographic', 1, 5.00, '2026-11-03 21:52:56'),
(263, 28, 41, NULL, '[138,139,141,140,142]', 0, 6.00, '2026-11-03 21:52:56'),
(264, 28, 42, NULL, '{\"143\":\"By name from A to Z\",\"144\":\"By number order\",\"145\":\"By date\",\"146\":\"By topic\"}', 1, 10.00, '2026-11-03 21:52:56'),
(265, 28, 43, NULL, '[\"chronological\",\"numerical\"]', 1, 10.00, '2026-11-03 21:52:56'),
(266, 28, 44, NULL, '[\"label\",\"documents\"]', 1, 10.00, '2026-11-03 21:52:56'),
(267, 29, 9, 33, NULL, 1, 5.00, '2026-11-04 10:21:56'),
(268, 29, 10, 37, NULL, 1, 5.00, '2026-11-04 10:21:56'),
(269, 29, 55, 184, NULL, 1, 10.00, '2026-11-04 10:21:56'),
(270, 29, 56, 188, NULL, 1, 10.00, '2026-11-04 10:21:56'),
(271, 29, 57, 192, NULL, 1, 5.00, '2026-11-04 10:21:56'),
(272, 29, 58, NULL, 'True', 1, 5.00, '2026-11-04 10:21:56'),
(273, 29, 59, NULL, 'False', 1, 5.00, '2026-11-04 10:21:56'),
(274, 29, 60, NULL, 'message', 0, 0.00, '2026-11-04 10:21:56'),
(275, 29, 61, NULL, '[196,197,198,199,200]', 1, 5.00, '2026-11-04 10:21:57'),
(276, 29, 62, NULL, '{\"201\":\"Easy to understand\",\"202\":\"Polite and respectful\",\"203\":\"Short and to the point\",\"204\":\"Accurate facts and good grammar\"}', 0, 5.00, '2026-11-04 10:21:57'),
(277, 29, 63, NULL, '[\"sender\",\"receiver\"]', 1, 10.00, '2026-11-04 10:21:57'),
(278, 29, 64, NULL, '[\"concise\",\"time\"]', 1, 10.00, '2026-11-04 10:21:57'),
(279, 30, 7, 25, NULL, 1, 5.00, '2026-11-04 10:36:07'),
(280, 30, 8, 29, NULL, 1, 5.00, '2026-11-04 10:36:07'),
(281, 30, 45, 155, NULL, 1, 10.00, '2026-11-04 10:36:07'),
(282, 30, 46, 159, NULL, 1, 10.00, '2026-11-04 10:36:07'),
(283, 30, 47, 163, NULL, 1, 10.00, '2026-11-04 10:36:07'),
(284, 30, 48, NULL, 'True', 1, 5.00, '2026-11-04 10:36:07'),
(285, 30, 49, NULL, 'False', 1, 5.00, '2026-11-04 10:36:07'),
(286, 30, 50, NULL, 'shredding', 1, 10.00, '2026-11-04 10:36:07'),
(287, 30, 51, NULL, '[167,168,169,170,171]', 1, 10.00, '2026-11-04 10:36:07'),
(288, 30, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-11-04 10:36:07'),
(289, 30, 53, NULL, '[\"information\",\"confidentiality\"]', 0, 0.00, '2026-11-04 10:36:07'),
(290, 30, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-11-04 10:36:07'),
(291, 31, 135, 417, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(292, 31, 136, 421, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(293, 31, 137, 425, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(294, 31, 138, 429, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(295, 31, 139, 433, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(296, 31, 140, 437, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(297, 31, 141, 441, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(298, 31, 142, 445, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(299, 31, 143, 449, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(300, 31, 144, 453, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(301, 31, 145, 457, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(302, 31, 146, 461, NULL, 1, 5.00, '2026-11-04 14:17:06'),
(303, 32, 135, 417, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(304, 32, 136, 421, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(305, 32, 137, 425, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(306, 32, 138, 429, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(307, 32, 139, 433, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(308, 32, 140, 437, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(309, 32, 141, 441, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(310, 32, 142, 445, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(311, 32, 143, 449, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(312, 32, 144, 453, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(313, 32, 145, 457, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(314, 32, 146, 461, NULL, 1, 5.00, '2026-11-04 14:32:56'),
(315, 33, 3, 9, NULL, 1, 5.00, '2026-11-04 14:39:44'),
(316, 33, 4, 13, NULL, 1, 5.00, '2026-11-04 14:39:44'),
(317, 33, 25, 97, NULL, 1, 10.00, '2026-11-04 14:39:44'),
(318, 33, 26, 101, NULL, 1, 10.00, '2026-11-04 14:39:44'),
(319, 33, 27, 105, NULL, 1, 10.00, '2026-11-04 14:39:44'),
(320, 33, 28, NULL, 'True', 1, 5.00, '2026-11-04 14:39:44'),
(321, 33, 29, NULL, 'False', 1, 5.00, '2026-11-04 14:39:44'),
(322, 33, 30, NULL, 'out', 1, 5.00, '2026-11-04 14:39:44'),
(323, 33, 31, NULL, '[109,110,111,112,113]', 1, 5.00, '2026-11-04 14:39:44'),
(324, 33, 32, NULL, '{\"114\":\"Posting notices for staff\",\"115\":\"Storing paper, pens, and folders\",\"116\":\"Tracking what must be done\",\"117\":\"Keeping pens and small items tidy\"}', 0, 2.50, '2026-11-04 14:39:44'),
(325, 33, 33, NULL, '[\"to-do\",\"deadline\"]', 1, 10.00, '2026-11-04 14:39:44'),
(326, 33, 34, NULL, '[\"organized\",\"efficiently\"]', 1, 10.00, '2026-11-04 14:39:44'),
(327, 34, 5, 17, NULL, 1, 5.00, '2026-11-04 14:46:31'),
(328, 34, 6, 21, NULL, 1, 5.00, '2026-11-04 14:46:31'),
(329, 34, 35, 126, NULL, 1, 10.00, '2026-11-04 14:46:31'),
(330, 34, 36, 130, NULL, 1, 10.00, '2026-11-04 14:46:31'),
(331, 34, 37, 134, NULL, 1, 10.00, '2026-11-04 14:46:31'),
(332, 34, 38, NULL, 'True', 1, 5.00, '2026-11-04 14:46:31'),
(333, 34, 39, NULL, 'False', 1, 5.00, '2026-11-04 14:46:31'),
(334, 34, 40, NULL, 'geographic', 1, 10.00, '2026-11-04 14:46:31'),
(335, 34, 41, NULL, '[138,139,140,141,142]', 1, 5.00, '2026-11-04 14:46:31'),
(336, 34, 42, NULL, '{\"143\":\"By name from A to Z\",\"144\":\"By date\",\"145\":\"By number order\",\"146\":\"By topic\"}', 0, 5.00, '2026-11-04 14:46:31'),
(337, 34, 43, NULL, '[\"chronological\",\"numerical\"]', 1, 10.00, '2026-11-04 14:46:31'),
(338, 34, 44, NULL, '[\"label\",\"documents\"]', 1, 5.00, '2026-11-04 14:46:31'),
(339, 35, 3, 9, NULL, 1, 5.00, '2026-11-04 23:10:41'),
(340, 35, 4, 13, NULL, 1, 5.00, '2026-11-04 23:10:41'),
(341, 35, 25, 97, NULL, 1, 10.00, '2026-11-04 23:10:41'),
(342, 35, 26, 101, NULL, 1, 10.00, '2026-11-04 23:10:41'),
(343, 35, 27, 105, NULL, 1, 10.00, '2026-11-04 23:10:41'),
(344, 35, 28, NULL, 'True', 1, 5.00, '2026-11-04 23:10:41'),
(345, 35, 29, NULL, 'False', 1, 5.00, '2026-11-04 23:10:41'),
(346, 35, 30, NULL, 'out', 1, 10.00, '2026-11-04 23:10:41'),
(347, 35, 31, NULL, '[109,110,111,112,113]', 1, 5.00, '2026-11-04 23:10:41'),
(348, 35, 32, NULL, '{\"114\":\"Posting notices for staff\",\"115\":\"Storing paper, pens, and folders\",\"116\":\"Tracking what must be done\",\"117\":\"Keeping pens and small items tidy\"}', 0, 5.00, '2026-11-04 23:10:41'),
(349, 35, 33, NULL, '[\"to-do\",\"deadline\"]', 1, 10.00, '2026-11-04 23:10:41'),
(350, 35, 34, NULL, '[\"organized\",\"efficiently\"]', 1, 10.00, '2026-11-04 23:10:41'),
(351, 36, 5, 17, NULL, 1, 5.00, '2026-11-04 23:14:37'),
(352, 36, 6, 21, NULL, 1, 5.00, '2026-11-04 23:14:37'),
(353, 36, 35, 126, NULL, 1, 10.00, '2026-11-04 23:14:37'),
(354, 36, 36, 130, NULL, 1, 10.00, '2026-11-04 23:14:37'),
(355, 36, 37, 134, NULL, 1, 10.00, '2026-11-04 23:14:37'),
(356, 36, 38, NULL, 'True', 1, 5.00, '2026-11-04 23:14:37'),
(357, 36, 39, NULL, 'False', 1, 5.00, '2026-11-04 23:14:37'),
(358, 36, 40, NULL, 'geographic', 1, 10.00, '2026-11-04 23:14:37'),
(359, 36, 41, NULL, '[138,139,140,141,142]', 1, 5.00, '2026-11-04 23:14:37'),
(360, 36, 42, NULL, '{\"143\":\"By name from A to Z\",\"144\":\"By number order\",\"145\":\"By date\",\"146\":\"By topic\"}', 1, 10.00, '2026-11-04 23:14:37'),
(361, 36, 43, NULL, '[\"chronological\",\"numerical\"]', 1, 10.00, '2026-11-04 23:14:37'),
(362, 36, 44, NULL, '[\"label\",\"documents\"]', 1, 10.00, '2026-11-04 23:14:37'),
(363, 37, 7, 25, NULL, 1, 5.00, '2026-11-04 23:19:04'),
(364, 37, 8, 29, NULL, 1, 5.00, '2026-11-04 23:19:04'),
(365, 37, 45, 155, NULL, 1, 10.00, '2026-11-04 23:19:04'),
(366, 37, 46, 159, NULL, 1, 10.00, '2026-11-04 23:19:04'),
(367, 37, 47, 163, NULL, 1, 10.00, '2026-11-04 23:19:04'),
(368, 37, 48, NULL, 'True', 1, 5.00, '2026-11-04 23:19:04'),
(369, 37, 49, NULL, 'False', 1, 5.00, '2026-11-04 23:19:04'),
(370, 37, 50, NULL, 'sheredding', 0, 0.00, '2026-11-04 23:19:04'),
(371, 37, 51, NULL, '[167,168,169,170,171]', 1, 5.00, '2026-11-04 23:19:04'),
(372, 37, 52, NULL, '{\"172\":\"Arriving on time for work and meetings\",\"173\":\"Reporting a mistake instead of hiding it\",\"174\":\"Not sharing private client details\",\"175\":\"Helping a colleague meet a deadline\"}', 1, 10.00, '2026-11-04 23:19:04'),
(373, 37, 53, NULL, '[\"confidentiality\",\"information\"]', 1, 10.00, '2026-11-04 23:19:04'),
(374, 37, 54, NULL, '[\"records\",\"decisions\"]', 1, 10.00, '2026-11-04 23:19:04');

-- --------------------------------------------------------

--
-- Table structure for table `activity_attempts`
--

CREATE TABLE `activity_attempts` (
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `activity_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `score` decimal(7,2) NOT NULL DEFAULT 0.00,
  `total_points` decimal(7,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','submitted','graded','passed','failed') NOT NULL DEFAULT 'in_progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_attempts`
--

INSERT INTO `activity_attempts` (`attempt_id`, `user_id`, `activity_id`, `attempt_number`, `score`, `total_points`, `percentage`, `status`, `started_at`, `submitted_at`, `graded_at`) VALUES
(1, 5, 4, 1, 5.00, 10.00, 50.00, 'failed', '2026-10-14 19:22:06', '2026-10-14 19:22:06', '2026-10-14 19:22:06'),
(2, 5, 4, 2, 5.00, 10.00, 50.00, 'failed', '2026-10-14 19:32:07', '2026-10-14 19:32:07', '2026-10-14 19:32:07'),
(3, 5, 4, 3, 10.00, 10.00, 100.00, 'passed', '2026-10-14 20:06:42', '2026-10-14 20:06:42', '2026-10-14 20:06:42'),
(4, 5, 4, 4, 5.00, 10.00, 50.00, 'failed', '2026-10-15 10:17:55', '2026-10-15 10:17:55', '2026-10-15 10:17:55'),
(5, 5, 4, 5, 45.00, 100.00, 45.00, 'failed', '2026-10-15 10:58:48', '2026-10-15 10:58:48', '2026-10-15 10:58:48'),
(6, 5, 4, 6, 64.50, 100.00, 64.50, 'failed', '2026-10-15 11:23:30', '2026-10-15 11:23:30', '2026-10-15 11:23:30'),
(7, 5, 4, 7, 85.00, 100.00, 85.00, 'passed', '2026-10-15 11:37:58', '2026-10-15 11:37:58', '2026-10-15 11:37:58'),
(8, 5, 4, 8, 86.00, 100.00, 86.00, 'passed', '2026-10-15 11:56:05', '2026-10-15 11:56:05', '2026-10-15 11:56:05'),
(9, 5, 4, 9, 79.00, 100.00, 79.00, 'passed', '2026-10-15 12:08:22', '2026-10-15 12:08:22', '2026-10-15 12:08:22'),
(10, 5, 4, 10, 88.50, 100.00, 88.50, 'passed', '2026-10-15 13:38:59', '2026-10-15 13:38:59', '2026-10-15 13:38:59'),
(11, 5, 4, 11, 100.00, 100.00, 100.00, 'passed', '2026-10-15 13:45:27', '2026-10-15 13:45:27', '2026-10-15 13:45:27'),
(12, 5, 4, 12, 96.00, 100.00, 96.00, 'passed', '2026-10-15 13:52:52', '2026-10-15 13:52:52', '2026-10-15 13:52:52'),
(13, 5, 4, 13, 100.00, 100.00, 100.00, 'passed', '2026-10-15 14:00:37', '2026-10-15 14:00:37', '2026-10-15 14:00:37'),
(14, 5, 4, 14, 96.00, 100.00, 96.00, 'passed', '2026-10-15 15:23:39', '2026-10-15 15:23:39', '2026-10-15 15:23:39'),
(15, 5, 1, 1, 10.00, 10.00, 100.00, 'passed', '2026-10-15 15:57:47', '2026-10-15 15:57:47', '2026-10-15 15:57:47'),
(16, 5, 1, 2, 10.00, 10.00, 100.00, 'passed', '2026-10-15 15:59:00', '2026-10-15 15:59:00', '2026-10-15 15:59:00'),
(17, 5, 2, 1, 85.00, 100.00, 85.00, 'passed', '2026-10-15 20:59:31', '2026-10-15 20:59:31', '2026-10-15 20:59:31'),
(18, 5, 3, 1, 77.00, 100.00, 77.00, 'passed', '2026-10-15 21:03:08', '2026-10-15 21:03:08', '2026-10-15 21:03:08'),
(19, 5, 5, 1, 81.00, 100.00, 81.00, 'passed', '2026-10-15 21:05:50', '2026-10-15 21:05:50', '2026-10-15 21:05:50'),
(20, 7, 1, 1, 10.00, 10.00, 100.00, 'passed', '2026-10-16 17:13:11', '2026-10-16 17:13:11', '2026-10-16 17:13:11'),
(21, 8, 5, 1, 96.00, 100.00, 96.00, 'passed', '2026-10-16 18:11:14', '2026-10-16 18:11:14', '2026-10-16 18:11:14'),
(22, 8, 1, 1, 60.00, 60.00, 100.00, 'passed', '2026-10-16 19:02:09', '2026-10-16 19:02:09', '2026-10-16 19:02:09'),
(23, 6, 1, 1, 60.00, 60.00, 100.00, 'passed', '2026-10-25 10:22:52', '2026-10-25 10:22:52', '2026-10-25 10:22:52'),
(24, 14, 1, 1, 60.00, 60.00, 100.00, 'passed', '2026-10-29 11:46:19', '2026-10-29 11:46:19', '2026-10-29 11:46:19'),
(25, 6, 2, 1, 86.50, 100.00, 86.50, 'passed', '2026-11-03 11:19:29', '2026-11-03 11:19:29', '2026-11-03 11:19:29'),
(26, 15, 1, 1, 60.00, 60.00, 100.00, 'passed', '2026-11-03 16:01:00', '2026-11-03 16:01:00', '2026-11-03 16:01:00'),
(27, 6, 3, 1, 67.00, 100.00, 67.00, 'failed', '2026-11-03 21:47:35', '2026-11-03 21:47:35', '2026-11-03 21:47:35'),
(28, 6, 3, 2, 91.00, 100.00, 91.00, 'passed', '2026-11-03 21:52:56', '2026-11-03 21:52:56', '2026-11-03 21:52:56'),
(29, 6, 5, 1, 75.00, 100.00, 75.00, 'passed', '2026-11-04 10:21:56', '2026-11-04 10:21:56', '2026-11-04 10:21:56'),
(30, 6, 4, 1, 90.00, 100.00, 90.00, 'passed', '2026-11-04 10:36:07', '2026-11-04 10:36:07', '2026-11-04 10:36:07'),
(31, 16, 1, 1, 60.00, 60.00, 100.00, 'passed', '2026-11-04 14:17:06', '2026-11-04 14:17:06', '2026-11-04 14:17:06'),
(32, 17, 1, 1, 60.00, 60.00, 100.00, 'passed', '2026-11-04 14:32:56', '2026-11-04 14:32:56', '2026-11-04 14:32:56'),
(33, 17, 2, 1, 82.50, 100.00, 82.50, 'passed', '2026-11-04 14:39:44', '2026-11-04 14:39:44', '2026-11-04 14:39:44'),
(34, 17, 3, 1, 85.00, 100.00, 85.00, 'passed', '2026-11-04 14:46:31', '2026-11-04 14:46:31', '2026-11-04 14:46:31'),
(35, 16, 2, 1, 90.00, 100.00, 90.00, 'passed', '2026-11-04 23:10:41', '2026-11-04 23:10:41', '2026-11-04 23:10:41'),
(36, 16, 3, 1, 95.00, 100.00, 95.00, 'passed', '2026-11-04 23:14:37', '2026-11-04 23:14:37', '2026-11-04 23:14:37'),
(37, 16, 4, 1, 85.00, 100.00, 85.00, 'passed', '2026-11-04 23:19:04', '2026-11-04 23:19:04', '2026-11-04 23:19:04');

-- --------------------------------------------------------

--
-- Table structure for table `activity_options`
--

CREATE TABLE `activity_options` (
  `option_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `option_text` text NOT NULL,
  `match_text` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `option_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_options`
--

INSERT INTO `activity_options` (`option_id`, `question_id`, `option_text`, `match_text`, `is_correct`, `option_order`) VALUES
(9, 3, 'Follow a consistent procedure or checklist', NULL, 1, 1),
(10, 3, 'Use a different process every time', NULL, 0, 2),
(11, 3, 'Skip important steps', NULL, 0, 3),
(12, 3, 'Avoid keeping records', NULL, 0, 4),
(13, 4, 'To support safety and organization', NULL, 1, 1),
(14, 4, 'To store more boxes in walkways', NULL, 0, 2),
(15, 4, 'To prevent people from using the area', NULL, 0, 3),
(16, 4, 'To hide office equipment', NULL, 0, 4),
(17, 5, 'Chronological', NULL, 1, 1),
(18, 5, 'Alphabetical', NULL, 0, 2),
(19, 5, 'Random', NULL, 0, 3),
(20, 5, 'Color only', NULL, 0, 4),
(21, 6, 'Consistent file names and folders', NULL, 1, 1),
(22, 6, 'Names such as file1 for every file', NULL, 0, 2),
(23, 6, 'Saving everything to the desktop', NULL, 0, 3),
(24, 6, 'Removing all folder labels', NULL, 0, 4),
(25, 7, 'Report it and follow the correction procedure', NULL, 1, 1),
(26, 7, 'Ignore the error', NULL, 0, 2),
(27, 7, 'Change the record secretly', NULL, 0, 3),
(28, 7, 'Delete all related records', NULL, 0, 4),
(29, 8, 'Handling information responsibly', NULL, 1, 1),
(30, 8, 'Sharing private data casually', NULL, 0, 2),
(31, 8, 'Ignoring workplace procedures', NULL, 0, 3),
(32, 8, 'Blaming coworkers without checking', NULL, 0, 4),
(33, 9, 'Clarity', NULL, 1, 1),
(34, 9, 'Confusion', NULL, 0, 2),
(35, 9, 'Delay', NULL, 0, 3),
(36, 9, 'Decoration', NULL, 0, 4),
(37, 10, 'The information the recipient needs', NULL, 1, 1),
(38, 10, 'Only an unclear greeting', NULL, 0, 2),
(39, 10, 'Unrelated personal stories', NULL, 0, 3),
(40, 10, 'No details or purpose', NULL, 0, 4),
(41, 11, 'A short subject that describes the purpose', NULL, 1, 1),
(42, 11, 'A blank subject line', NULL, 0, 2),
(43, 11, 'A long unrelated sentence', NULL, 0, 3),
(44, 11, 'Only random symbols', NULL, 0, 4),
(45, 12, 'Recipient, attachments, spelling, and tone', NULL, 1, 1),
(46, 12, 'Only the font color', NULL, 0, 2),
(47, 12, 'Nothing; send immediately', NULL, 0, 3),
(48, 12, 'Only the time of day', NULL, 0, 4),
(49, 13, 'Ask the caller politely for permission', NULL, 1, 1),
(50, 13, 'Put the caller on hold without warning', NULL, 0, 2),
(51, 13, 'Hang up immediately', NULL, 0, 3),
(52, 13, 'Ignore the caller', NULL, 0, 4),
(53, 14, 'Caller name, contact details, reason, and time', NULL, 1, 1),
(54, 14, 'Only the caller\'s first name', NULL, 0, 2),
(55, 14, 'An unverified guess', NULL, 0, 3),
(56, 14, 'No details', NULL, 0, 4),
(57, 15, 'Near the beginning', NULL, 1, 1),
(58, 15, 'Hidden at the end without context', NULL, 0, 2),
(59, 15, 'In an unrelated attachment only', NULL, 0, 3),
(60, 15, 'Not included', NULL, 0, 4),
(61, 16, 'Use clear wording and a suitable tone', NULL, 1, 1),
(62, 16, 'Use vague words', NULL, 0, 2),
(63, 16, 'Ignore the audience', NULL, 0, 3),
(64, 16, 'Leave out the main purpose', NULL, 0, 4),
(65, 17, 'Completing work efficiently while maintaining quality', NULL, 1, 1),
(66, 17, 'Working quickly with many errors', NULL, 0, 2),
(67, 17, 'Doing tasks without priorities', NULL, 0, 3),
(68, 17, 'Avoiding all planning', NULL, 0, 4),
(69, 18, 'Set priorities and deadlines', NULL, 1, 1),
(70, 18, 'Do tasks in random order', NULL, 0, 2),
(71, 18, 'Ignore deadlines', NULL, 0, 3),
(72, 18, 'Start everything but finish nothing', NULL, 0, 4),
(73, 19, 'It improves readability and organization', NULL, 1, 1),
(74, 19, 'It makes the document harder to read', NULL, 0, 2),
(75, 19, 'It removes the need for proofreading', NULL, 0, 3),
(76, 19, 'It hides important information', NULL, 0, 4),
(77, 20, 'Proofread content and check layout', NULL, 1, 1),
(78, 20, 'Submit without reviewing', NULL, 0, 2),
(79, 20, 'Delete headings at random', NULL, 0, 3),
(80, 20, 'Ignore page breaks', NULL, 0, 4),
(81, 21, 'The intersection of a row and a column', NULL, 1, 1),
(82, 21, 'A whole worksheet file', NULL, 0, 2),
(83, 21, 'A printer setting', NULL, 0, 3),
(84, 21, 'A folder name', NULL, 0, 4),
(85, 22, '=', NULL, 1, 1),
(86, 22, '#', NULL, 0, 2),
(87, 22, '&', NULL, 0, 3),
(88, 22, '@', NULL, 0, 4),
(89, 23, 'Allow access only to authorized people', NULL, 1, 1),
(90, 23, 'Share records with everyone', NULL, 0, 2),
(91, 23, 'Post records publicly', NULL, 0, 3),
(92, 23, 'Use one shared password', NULL, 0, 4),
(93, 24, 'Report it promptly using the approved process', NULL, 1, 1),
(94, 24, 'Hide the incident', NULL, 0, 2),
(95, 24, 'Forward the data to friends', NULL, 0, 3),
(96, 24, 'Delete evidence without reporting', NULL, 0, 4),
(97, 25, 'You can find what you need quickly and work more efficiently', NULL, 1, 1),
(98, 25, 'The desk looks empty for visitors only', NULL, 0, 2),
(99, 25, 'You never need to file documents', NULL, 0, 3),
(100, 25, 'It lets you avoid finishing tasks', NULL, 0, 4),
(101, 26, 'Do it first, before less urgent tasks', NULL, 1, 1),
(102, 26, 'Leave it until the end of the week', NULL, 0, 2),
(103, 26, 'Wait until someone reminds you', NULL, 0, 3),
(104, 26, 'Move it to the bottom of the pile', NULL, 0, 4),
(105, 27, 'Report it or reorder it right away following the supply procedure', NULL, 1, 1),
(106, 27, 'Wait until the paper completely runs out', NULL, 0, 2),
(107, 27, 'Take paper from another department without telling anyone', NULL, 0, 3),
(108, 27, 'Cancel the printing job', NULL, 0, 4),
(109, 31, 'Check the current stock levels', NULL, 0, 1),
(110, 31, 'List the items that are running low', NULL, 0, 2),
(111, 31, 'Request approval or place the order', NULL, 0, 3),
(112, 31, 'Receive and check the delivery', NULL, 0, 4),
(113, 31, 'Store the supplies in the supply cabinet', NULL, 0, 5),
(114, 32, 'Bulletin board', 'Posting notices for staff', 0, 1),
(115, 32, 'Desk organizer', 'Keeping pens and small items tidy', 0, 2),
(116, 32, 'Task list', 'Tracking what must be done', 0, 3),
(117, 32, 'Supply cabinet', 'Storing paper, pens, and folders', 0, 4),
(118, 33, 'to-do', NULL, 0, 1),
(119, 33, 'deadline', NULL, 0, 2),
(120, 33, 'hobby', NULL, 0, 3),
(121, 33, 'rumor', NULL, 0, 4),
(122, 34, 'organized', NULL, 0, 1),
(123, 34, 'efficiently', NULL, 0, 2),
(124, 34, 'cluttered', NULL, 0, 3),
(125, 34, 'slowly', NULL, 0, 4),
(126, 35, 'Alphabetical', NULL, 1, 1),
(127, 35, 'Numerical', NULL, 0, 2),
(128, 35, 'Chronological', NULL, 0, 3),
(129, 35, 'Geographic', NULL, 0, 4),
(130, 36, 'Chronological', NULL, 1, 1),
(131, 36, 'Alphabetical', NULL, 0, 2),
(132, 36, 'Subject', NULL, 0, 3),
(133, 36, 'Geographic', NULL, 0, 4),
(134, 37, 'Numerical filing', NULL, 1, 1),
(135, 37, 'Alphabetical filing by customer name', NULL, 0, 2),
(136, 37, 'Geographic filing by city', NULL, 0, 3),
(137, 37, 'No system, just a pile of folders', NULL, 0, 4),
(138, 41, 'Inspect: check the document is ready to be filed', NULL, 0, 1),
(139, 41, 'Index: decide the name or subject to file it under', NULL, 0, 2),
(140, 41, 'Code: mark the document with the file name or number', NULL, 0, 3),
(141, 41, 'Sort: arrange documents in filing order', NULL, 0, 4),
(142, 41, 'Store: place it in the correct folder', NULL, 0, 5),
(143, 42, 'Alphabetical', 'By name from A to Z', 0, 1),
(144, 42, 'Numerical', 'By number order', 0, 2),
(145, 42, 'Chronological', 'By date', 0, 3),
(146, 42, 'Subject', 'By topic', 0, 4),
(147, 43, 'chronological', NULL, 0, 1),
(148, 43, 'numerical', NULL, 0, 2),
(149, 43, 'alphabetical', NULL, 0, 3),
(150, 43, 'random', NULL, 0, 4),
(151, 44, 'label', NULL, 0, 1),
(152, 44, 'documents', NULL, 0, 2),
(153, 44, 'decoration', NULL, 0, 3),
(154, 44, 'password', NULL, 0, 4),
(155, 45, 'Arriving on time and following workplace policies', NULL, 1, 1),
(156, 45, 'Gossiping about coworkers', NULL, 0, 2),
(157, 45, 'Using office equipment for personal business', NULL, 0, 3),
(158, 45, 'Ignoring deadlines', NULL, 0, 4),
(159, 46, 'Decisions and reports depend on reliable information', NULL, 1, 1),
(160, 46, 'It makes the files look thicker', NULL, 0, 2),
(161, 46, 'Records are never used after they are created', NULL, 0, 3),
(162, 46, 'Accuracy only matters for handwritten records', NULL, 0, 4),
(163, 47, 'Politely decline and explain that access is limited to authorized people', NULL, 1, 1),
(164, 47, 'Give it to them because they work in the same company', NULL, 0, 2),
(165, 47, 'Email it to them so they can read it later', NULL, 0, 3),
(166, 47, 'Leave it on their desk when they are away', NULL, 0, 4),
(167, 51, 'Receive the document and note that it is marked confidential', NULL, 0, 1),
(168, 51, 'Store it in a locked drawer or secure file', NULL, 0, 2),
(169, 51, 'Share it only with authorized people', NULL, 0, 3),
(170, 51, 'Return it to secure storage after use', NULL, 0, 4),
(171, 51, 'Shred it when it is no longer needed', NULL, 0, 5),
(172, 52, 'Punctuality', 'Arriving on time for work and meetings', 0, 1),
(173, 52, 'Honesty', 'Reporting a mistake instead of hiding it', 0, 2),
(174, 52, 'Confidentiality', 'Not sharing private client details', 0, 3),
(175, 52, 'Teamwork', 'Helping a colleague meet a deadline', 0, 4),
(176, 53, 'confidentiality', NULL, 0, 1),
(177, 53, 'information', NULL, 0, 2),
(178, 53, 'gossip', NULL, 0, 3),
(179, 53, 'jokes', NULL, 0, 4),
(180, 54, 'records', NULL, 0, 1),
(181, 54, 'decisions', NULL, 0, 2),
(182, 54, 'rumors', NULL, 0, 3),
(183, 54, 'mistakes', NULL, 0, 4),
(184, 55, 'Being clear and concise', NULL, 1, 1),
(185, 55, 'Using as much jargon as possible', NULL, 0, 2),
(186, 55, 'Making every message as long as possible', NULL, 0, 3),
(187, 55, 'Avoiding any feedback from the reader', NULL, 0, 4),
(188, 56, 'Receiver', NULL, 1, 1),
(189, 56, 'Sender', NULL, 0, 2),
(190, 56, 'Channel', NULL, 0, 3),
(191, 56, 'Editor', NULL, 0, 4),
(192, 57, 'The report deadline has moved from Friday to Wednesday at 5 p.m.', NULL, 1, 1),
(193, 57, 'Things changed with that report, so be ready soon.', NULL, 0, 2),
(194, 57, 'Maybe hurry up sometime this week.', NULL, 0, 3),
(195, 57, 'You know the thing we talked about is different now.', NULL, 0, 4),
(196, 61, 'The sender decides what to say', NULL, 0, 1),
(197, 61, 'The sender puts the idea into words', NULL, 0, 2),
(198, 61, 'The message is sent through a channel', NULL, 0, 3),
(199, 61, 'The receiver interprets the message', NULL, 0, 4),
(200, 61, 'The receiver gives feedback', NULL, 0, 5),
(201, 62, 'Clear', 'Easy to understand', 0, 1),
(202, 62, 'Concise', 'Short and to the point', 0, 2),
(203, 62, 'Courteous', 'Polite and respectful', 0, 3),
(204, 62, 'Correct', 'Accurate facts and good grammar', 0, 4),
(205, 63, 'sender', NULL, 0, 1),
(206, 63, 'receiver', NULL, 0, 2),
(207, 63, 'printer', NULL, 0, 3),
(208, 63, 'window', NULL, 0, 4),
(209, 64, 'concise', NULL, 0, 1),
(210, 64, 'time', NULL, 0, 2),
(211, 64, 'wordy', NULL, 0, 3),
(212, 64, 'confusion', NULL, 0, 4),
(213, 65, 'Subject line', NULL, 1, 1),
(214, 65, 'Signature', NULL, 0, 2),
(215, 65, 'Attachment', NULL, 0, 3),
(216, 65, 'CC field', NULL, 0, 4),
(217, 66, 'Sincerely,', NULL, 1, 1),
(218, 66, 'Later!', NULL, 0, 2),
(219, 66, 'See ya,', NULL, 0, 3),
(220, 66, 'Bye bye,', NULL, 0, 4),
(221, 67, 'Dear Ms. Santos, thank you for your patience regarding your order.', NULL, 1, 1),
(222, 67, 'Hey, so your order is late lol.', NULL, 0, 2),
(223, 67, 'Hi, it will come when it comes.', NULL, 0, 3),
(224, 67, 'To whom it may concern, this is not my department.', NULL, 0, 4),
(225, 71, 'Sender address or letterhead', NULL, 0, 1),
(226, 71, 'Date', NULL, 0, 2),
(227, 71, 'Inside address of the receiver', NULL, 0, 3),
(228, 71, 'Salutation', NULL, 0, 4),
(229, 71, 'Body of the letter', NULL, 0, 5),
(230, 71, 'Closing and signature', NULL, 0, 6),
(231, 72, 'Subject line', 'States the topic briefly', 0, 1),
(232, 72, 'Salutation', 'Greets the reader by name', 0, 2),
(233, 72, 'Body', 'Contains the main message', 0, 3),
(234, 72, 'Signature block', 'Gives your name and contact details', 0, 4),
(235, 73, 'subject', NULL, 0, 1),
(236, 73, 'closing', NULL, 0, 2),
(237, 73, 'emoji', NULL, 0, 3),
(238, 73, 'slang', NULL, 0, 4),
(239, 74, 'proofread', NULL, 0, 1),
(240, 74, 'send', NULL, 0, 2),
(241, 74, 'ignore', NULL, 0, 3),
(242, 74, 'hide', NULL, 0, 4),
(243, 75, 'Greet the caller and identify yourself and your office', NULL, 1, 1),
(244, 75, 'Ask the caller to call back later', NULL, 0, 2),
(245, 75, 'Say nothing until the caller speaks', NULL, 0, 3),
(246, 75, 'Hang up if you are busy', NULL, 0, 4),
(247, 76, 'Ask permission and wait for the caller to agree', NULL, 1, 1),
(248, 76, 'Press hold without saying anything', NULL, 0, 2),
(249, 76, 'Leave them on hold as long as you like', NULL, 0, 3),
(250, 76, 'Hang up and call back later', NULL, 0, 4),
(251, 77, 'Explain the manager is unavailable and offer to take a message or arrange a call back', NULL, 1, 1),
(252, 77, 'Say you do not know and hang up', NULL, 0, 2),
(253, 77, 'Tell the caller the manager does not want to talk to them', NULL, 0, 3),
(254, 77, 'Transfer the call to a random extension', NULL, 0, 4),
(255, 81, 'Answer the call politely', NULL, 0, 1),
(256, 81, 'Ask for the caller name and number', NULL, 0, 2),
(257, 81, 'Write down the message with the date and time', NULL, 0, 3),
(258, 81, 'Repeat the details to confirm them', NULL, 0, 4),
(259, 81, 'Pass the message on promptly', NULL, 0, 5),
(260, 82, 'May I ask who is calling?', 'Identify the caller', 0, 1),
(261, 82, 'Please hold for a moment.', 'Ask the caller to wait', 0, 2),
(262, 82, 'Let me repeat that to confirm.', 'Check that details are correct', 0, 3),
(263, 82, 'Thank you for calling.', 'Close the call politely', 0, 4),
(264, 83, 'identify', NULL, 0, 1),
(265, 83, 'office', NULL, 0, 2),
(266, 83, 'avoid', NULL, 0, 3),
(267, 83, 'mood', NULL, 0, 4),
(268, 84, 'date', NULL, 0, 1),
(269, 84, 'time', NULL, 0, 2),
(270, 84, 'weather', NULL, 0, 3),
(271, 84, 'color', NULL, 0, 4),
(272, 85, 'State the purpose first, give the details, then state the next step', NULL, 1, 1),
(273, 85, 'Hide the purpose at the very end', NULL, 0, 2),
(274, 85, 'Start with a joke and add the purpose later', NULL, 0, 3),
(275, 85, 'Write one long paragraph without breaks', NULL, 0, 4),
(276, 86, 'Please let me know if you have any questions.', NULL, 1, 1),
(277, 86, 'Hit me up if you have questions.', NULL, 0, 2),
(278, 86, 'Lemme know, k?', NULL, 0, 3),
(279, 86, 'Whatever, just ask if you want.', NULL, 0, 4),
(280, 87, 'Thank them, state the decision clearly, and give a brief, courteous reason', NULL, 1, 1),
(281, 87, 'Ignore the request and hope they forget', NULL, 0, 2),
(282, 87, 'Reply with a blunt one-word No', NULL, 0, 3),
(283, 87, 'Criticize the supplier for asking', NULL, 0, 4),
(284, 91, 'Decide the purpose of the message', NULL, 0, 1),
(285, 91, 'Plan and organize the main points', NULL, 0, 2),
(286, 91, 'Write the first draft', NULL, 0, 3),
(287, 91, 'Revise and proofread', NULL, 0, 4),
(288, 91, 'Send the message', NULL, 0, 5),
(289, 92, 'Memo', 'Internal message inside the company', 0, 1),
(290, 92, 'Business letter', 'Formal message to outside parties', 0, 2),
(291, 92, 'Email', 'Fast electronic message', 0, 3),
(292, 92, 'Report', 'Detailed findings and information', 0, 4),
(293, 93, 'purpose', NULL, 0, 1),
(294, 93, 'short', NULL, 0, 2),
(295, 93, 'secret', NULL, 0, 3),
(296, 93, 'long', NULL, 0, 4),
(297, 94, 'polite', NULL, 0, 1),
(298, 94, 'tone', NULL, 0, 2),
(299, 94, 'rude', NULL, 0, 3),
(300, 94, 'volume', NULL, 0, 4),
(301, 95, 'Planning your day and prioritizing tasks', NULL, 1, 1),
(302, 95, 'Starting tasks without any plan', NULL, 0, 2),
(303, 95, 'Checking social media between every task', NULL, 0, 3),
(304, 95, 'Leaving the hardest task undone forever', NULL, 0, 4),
(305, 96, 'A calendar application', NULL, 1, 1),
(306, 96, 'A calculator', NULL, 0, 2),
(307, 96, 'A photo editor', NULL, 0, 3),
(308, 96, 'A music player', NULL, 0, 4),
(309, 97, 'List the tasks and prioritize them by urgency and importance', NULL, 1, 1),
(310, 97, 'Start the easiest one and ignore the others', NULL, 0, 2),
(311, 97, 'Work on all five at the same time', NULL, 0, 3),
(312, 97, 'Wait for someone else to finish them', NULL, 0, 4),
(313, 101, 'Review your calendar and messages', NULL, 0, 1),
(314, 101, 'List everything you need to do', NULL, 0, 2),
(315, 101, 'Prioritize tasks by urgency and importance', NULL, 0, 3),
(316, 101, 'Schedule time to work on each task', NULL, 0, 4),
(317, 101, 'Review your progress at the end of the day', NULL, 0, 5),
(318, 102, 'Word processor', 'Writing letters and reports', 0, 1),
(319, 102, 'Spreadsheet', 'Calculating and organizing numbers', 0, 2),
(320, 102, 'Calendar', 'Scheduling meetings', 0, 3),
(321, 102, 'Email', 'Sending messages', 0, 4),
(322, 103, 'prioritizing', NULL, 0, 1),
(323, 103, 'efficiently', NULL, 0, 2),
(324, 103, 'ignoring', NULL, 0, 3),
(325, 103, 'slowly', NULL, 0, 4),
(326, 104, 'word', NULL, 0, 1),
(327, 104, 'spreadsheet', NULL, 0, 2),
(328, 104, 'camera', NULL, 0, 3),
(329, 104, 'speaker', NULL, 0, 4),
(330, 105, 'Consistent fonts, headings, and spacing', NULL, 1, 1),
(331, 105, 'A different font for every paragraph', NULL, 0, 2),
(332, 105, 'Very tiny text to save paper', NULL, 0, 3),
(333, 105, 'Bright colors on every line', NULL, 0, 4),
(334, 106, 'A clean, standard font at a readable size', NULL, 1, 1),
(335, 106, 'A decorative script font in many colors', NULL, 0, 2),
(336, 106, 'A very small font', NULL, 0, 3),
(337, 106, 'A different font in each paragraph', NULL, 0, 4),
(338, 107, 'Apply the same heading style to all headings', NULL, 1, 1),
(339, 107, 'Leave them as they are', NULL, 0, 2),
(340, 107, 'Change only the headings on the first page', NULL, 0, 3),
(341, 107, 'Remove all the headings', NULL, 0, 4),
(342, 111, 'Plan the purpose and content', NULL, 0, 1),
(343, 111, 'Type the text', NULL, 0, 2),
(344, 111, 'Apply formatting such as fonts and headings', NULL, 0, 3),
(345, 111, 'Proofread and check for errors', NULL, 0, 4),
(346, 111, 'Save and share or print the document', NULL, 0, 5),
(347, 112, 'Heading', 'Shows the title of a section', 0, 1),
(348, 112, 'Bullet list', 'Presents short items clearly', 0, 2),
(349, 112, 'Line spacing', 'Controls the space between lines', 0, 3),
(350, 112, 'Page number', 'Helps readers find their place', 0, 4),
(351, 113, 'fonts', NULL, 0, 1),
(352, 113, 'spacing', NULL, 0, 2),
(353, 113, 'glitter', NULL, 0, 3),
(354, 113, 'noise', NULL, 0, 4),
(355, 114, 'proofread', NULL, 0, 1),
(356, 114, 'save', NULL, 0, 2),
(357, 114, 'hide', NULL, 0, 3),
(358, 114, 'delete', NULL, 0, 4),
(359, 115, 'Cell', NULL, 1, 1),
(360, 115, 'Page', NULL, 0, 2),
(361, 115, 'Folder', NULL, 0, 3),
(362, 115, 'Slide', NULL, 0, 4),
(363, 116, '=SUM(A1:A5)', NULL, 1, 1),
(364, 116, 'SUM A1 to A5', NULL, 0, 2),
(365, 116, '=A1:A5', NULL, 0, 3),
(366, 116, 'ADD(A1, A5) without an equals sign', NULL, 0, 4),
(367, 117, 'Verify entries against the source document as you go', NULL, 1, 1),
(368, 117, 'Type faster so you finish sooner', NULL, 0, 2),
(369, 117, 'Ignore the mistakes because they are small', NULL, 0, 3),
(370, 117, 'Delete all the phone numbers', NULL, 0, 4),
(371, 121, 'Enter the numbers into the cells', NULL, 0, 1),
(372, 121, 'Select the cell where the total should appear', NULL, 0, 2),
(373, 121, 'Type the formula, such as =SUM(A1:A5)', NULL, 0, 3),
(374, 121, 'Press Enter to confirm', NULL, 0, 4),
(375, 121, 'Check that the result looks correct', NULL, 0, 5),
(376, 122, 'Row', 'A horizontal line of cells', 0, 1),
(377, 122, 'Column', 'A vertical line of cells', 0, 2),
(378, 122, 'Cell', 'One box where a row and column meet', 0, 3),
(379, 122, 'Formula', 'A calculation that starts with =', 0, 4),
(380, 123, 'rows', NULL, 0, 1),
(381, 123, 'columns', NULL, 0, 2),
(382, 123, 'pages', NULL, 0, 3),
(383, 123, 'folders', NULL, 0, 4),
(384, 124, 'check', NULL, 0, 1),
(385, 124, 'accuracy', NULL, 0, 2),
(386, 124, 'skip', NULL, 0, 3),
(387, 124, 'speed', NULL, 0, 4),
(388, 125, 'Creation, use, storage, and disposal', NULL, 1, 1),
(389, 125, 'Printing, folding, mailing, and forgetting', NULL, 0, 2),
(390, 125, 'Buying, selling, renting, and returning', NULL, 0, 3),
(391, 125, 'Typing, deleting, retyping, and hiding', NULL, 0, 4),
(392, 126, 'Use strong passwords and make regular backups', NULL, 1, 1),
(393, 126, 'Share your password with coworkers', NULL, 0, 2),
(394, 126, 'Leave your computer unlocked all day', NULL, 0, 3),
(395, 126, 'Never save your work', NULL, 0, 4),
(396, 127, 'Secure the folder and return it to the responsible person or report it', NULL, 1, 1),
(397, 127, 'Leave it where it is', NULL, 0, 2),
(398, 127, 'Read the records out of curiosity', NULL, 0, 3),
(399, 127, 'Throw it in the regular trash', NULL, 0, 4),
(400, 131, 'Create or receive the record', NULL, 0, 1),
(401, 131, 'Use and maintain the record', NULL, 0, 2),
(402, 131, 'Store the record securely', NULL, 0, 3),
(403, 131, 'Keep it for the required period', NULL, 0, 4),
(404, 131, 'Dispose of it safely', NULL, 0, 5),
(405, 132, 'Password', 'Unauthorized access to files', 0, 1),
(406, 132, 'Backup', 'Loss of data', 0, 2),
(407, 132, 'Locked cabinet', 'Theft or viewing of paper records', 0, 3),
(408, 132, 'Shredder', 'Exposure of discarded confidential papers', 0, 4),
(409, 133, 'lifecycle', NULL, 0, 1),
(410, 133, 'disposal', NULL, 0, 2),
(411, 133, 'vacation', NULL, 0, 3),
(412, 133, 'weather', NULL, 0, 4),
(413, 134, 'passwords', NULL, 0, 1),
(414, 134, 'backups', NULL, 0, 2),
(415, 134, 'rumors', NULL, 0, 3),
(416, 134, 'shortcuts', NULL, 0, 4),
(417, 135, 'To organize office activities and support daily operations', NULL, 1, 1),
(418, 135, 'To make office tasks more confusing', NULL, 0, 2),
(419, 135, 'To avoid communicating with coworkers', NULL, 0, 3),
(420, 135, 'To remove all office records', NULL, 0, 4),
(421, 136, 'To find information easily and maintain accurate records', NULL, 1, 1),
(422, 136, 'To make documents harder to find', NULL, 0, 2),
(423, 136, 'To avoid recording important information', NULL, 0, 3),
(424, 136, 'To mix all documents together', NULL, 0, 4),
(425, 137, 'Managing records and organizing office tasks', NULL, 1, 1),
(426, 137, 'Ignoring office schedules', NULL, 0, 2),
(427, 137, 'Removing all workplace procedures', NULL, 0, 3),
(428, 137, 'Avoiding communication', NULL, 0, 4),
(429, 138, 'To make files easier to identify and retrieve', NULL, 1, 1),
(430, 138, 'To make files difficult to find', NULL, 0, 2),
(431, 138, 'To remove the need for records', NULL, 0, 3),
(432, 138, 'To mix unrelated documents', NULL, 0, 4),
(433, 139, 'Record the details and forward it to the appropriate person', NULL, 1, 1),
(434, 139, 'Delete the message immediately', NULL, 0, 2),
(435, 139, 'Share it with everyone without checking', NULL, 0, 3),
(436, 139, 'Ignore the message', NULL, 0, 4),
(437, 140, 'They help employees manage time and complete tasks on time', NULL, 1, 1),
(438, 140, 'They prevent employees from planning', NULL, 0, 2),
(439, 140, 'They make deadlines unnecessary', NULL, 0, 3),
(440, 140, 'They replace all communication', NULL, 0, 4),
(441, 141, 'Being respectful, reliable, and responsible', NULL, 1, 1),
(442, 141, 'Ignoring coworkers', NULL, 0, 2),
(443, 141, 'Sharing confidential files carelessly', NULL, 0, 3),
(444, 141, 'Frequently ignoring assigned tasks', NULL, 0, 4),
(445, 142, 'Share it only with authorized people', NULL, 1, 1),
(446, 142, 'Post it publicly', NULL, 0, 2),
(447, 142, 'Leave it where anyone can read it', NULL, 0, 3),
(448, 142, 'Send it to unknown recipients', NULL, 0, 4),
(449, 143, 'To keep it working safely and reduce damage', NULL, 1, 1),
(450, 143, 'To make equipment wear out faster', NULL, 0, 2),
(451, 143, 'To avoid following instructions', NULL, 0, 3),
(452, 143, 'To increase unnecessary expenses', NULL, 0, 4),
(453, 144, 'Follow the correction procedure and inform the responsible person', NULL, 1, 1),
(454, 144, 'Hide the mistake', NULL, 0, 2),
(455, 144, 'Delete every related record', NULL, 0, 3),
(456, 144, 'Ignore the mistake', NULL, 0, 4),
(457, 145, 'It helps people understand instructions and avoid mistakes', NULL, 1, 1),
(458, 145, 'It makes instructions less clear', NULL, 0, 2),
(459, 145, 'It prevents teamwork', NULL, 0, 3),
(460, 145, 'It removes the need to record information', NULL, 0, 4),
(461, 146, 'Secure documents and organize the workspace', NULL, 1, 1),
(462, 146, 'Leave confidential records exposed', NULL, 0, 2),
(463, 146, 'Leave equipment running unnecessarily', NULL, 0, 3),
(464, 146, 'Ignore workplace safety procedures', NULL, 0, 4);

-- --------------------------------------------------------

--
-- Table structure for table `activity_questions`
--

CREATE TABLE `activity_questions` (
  `question_id` int(10) UNSIGNED NOT NULL,
  `activity_id` int(10) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(30) NOT NULL,
  `points` decimal(6,2) NOT NULL DEFAULT 5.00,
  `question_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `correct_answer` text DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_questions`
--

INSERT INTO `activity_questions` (`question_id`, `activity_id`, `question_text`, `question_type`, `points`, `question_order`, `correct_answer`, `explanation`, `status`, `created_at`, `updated_at`) VALUES
(3, 2, 'What is a good way to handle a recurring office task?', 'multiple_choice', 5.00, 1, 'Follow a consistent procedure or checklist', 'Consistent procedures help reduce mistakes and confusion.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(4, 2, 'Why should walkways and shared areas be kept clear?', 'multiple_choice', 5.00, 2, 'To support safety and organization', 'Clear work areas help people move safely and work efficiently.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(5, 3, 'Which filing method organizes records by date?', 'multiple_choice', 5.00, 1, 'Chronological', 'Chronological filing organizes records by date or time.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(6, 3, 'What helps people find digital documents more easily?', 'multiple_choice', 5.00, 2, 'Consistent file names and folders', 'Consistent naming and folder rules make records easier to retrieve.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(7, 4, 'What should you do if you notice an error in an important record?', 'multiple_choice', 5.00, 1, 'Report it and follow the correction procedure', 'Records should be corrected through the approved process.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(8, 4, 'Which behavior shows professionalism?', 'multiple_choice', 5.00, 2, 'Handling information responsibly', 'Professional conduct includes accountability and confidentiality.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(9, 5, 'Which principle means using words that are easy to understand?', 'multiple_choice', 5.00, 1, 'Clarity', 'Clear messages are easy for the audience to understand.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(10, 5, 'What should a complete workplace message include?', 'multiple_choice', 5.00, 2, 'The information the recipient needs', 'Complete communication includes the details required to understand or act.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(11, 6, 'What is a useful email subject line?', 'multiple_choice', 5.00, 1, 'A short subject that describes the purpose', 'A specific subject helps the recipient understand the message quickly.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(12, 6, 'What should you check before sending an email?', 'multiple_choice', 5.00, 2, 'Recipient, attachments, spelling, and tone', 'A final review helps prevent common email mistakes.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(13, 7, 'What should you do before placing a caller on hold?', 'multiple_choice', 5.00, 1, 'Ask the caller politely for permission', 'Asking first is courteous and professional.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(14, 7, 'Which details are useful when taking a phone message?', 'multiple_choice', 5.00, 2, 'Caller name, contact details, reason, and time', 'Accurate message details help the right person follow up.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(15, 8, 'Where should the main purpose of a business message usually appear?', 'multiple_choice', 5.00, 1, 'Near the beginning', 'Putting the main point early helps readers understand the message.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(16, 8, 'Which practice improves business correspondence?', 'multiple_choice', 5.00, 2, 'Use clear wording and a suitable tone', 'Professional correspondence should be clear, organized, and respectful.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(17, 9, 'What is the best meaning of office productivity?', 'multiple_choice', 5.00, 1, 'Completing work efficiently while maintaining quality', 'Productivity balances efficiency with accuracy and quality.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(18, 9, 'What is a useful way to manage several tasks?', 'multiple_choice', 5.00, 2, 'Set priorities and deadlines', 'Prioritizing helps people use time effectively.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(19, 10, 'Why should document formatting be consistent?', 'multiple_choice', 5.00, 1, 'It improves readability and organization', 'Consistent formatting makes documents easier to read.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(20, 10, 'What should you do before submitting a document?', 'multiple_choice', 5.00, 2, 'Proofread content and check layout', 'Reviewing catches errors and formatting problems.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(21, 11, 'In a spreadsheet, what is a cell?', 'multiple_choice', 5.00, 1, 'The intersection of a row and a column', 'A cell is where a row and column meet.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(22, 11, 'Which character usually begins a spreadsheet formula?', 'multiple_choice', 5.00, 2, '=', 'Spreadsheet formulas commonly begin with an equals sign.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(23, 12, 'What is the safest approach to confidential records?', 'multiple_choice', 5.00, 1, 'Allow access only to authorized people', 'Access should be limited to people who need the information for legitimate work.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(24, 12, 'What should you do if you suspect information was exposed?', 'multiple_choice', 5.00, 2, 'Report it promptly using the approved process', 'Prompt reporting helps the organization respond to possible data exposure.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(25, 2, 'What is the main benefit of keeping your workspace organized?', 'multiple_choice', 10.00, 3, NULL, 'An organized workspace saves time because you can find what you need right away.', 'published', '2026-10-15 02:44:36', '2026-10-15 02:44:36'),
(26, 2, 'How should you handle a task that is both urgent and important?', 'multiple_choice', 10.00, 4, NULL, 'Urgent and important tasks come first because delay causes real problems.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(27, 2, 'You notice the office supply cabinet is almost out of printer paper, and a large printing job is due tomorrow.\n\nWhat should you do?', 'multiple_choice', 10.00, 5, NULL, 'Following the supply procedure and acting early prevents delays in the work.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(28, 2, 'True or False: A clutter-free desk helps reduce lost documents and wasted time.', 'true_false', 5.00, 6, 'True', 'Less clutter means fewer misplaced items and faster work.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(29, 2, 'True or False: Routine office procedures only need to be followed when a manager is present.', 'true_false', 5.00, 7, 'False', 'Procedures should be followed every time so work stays consistent and reliable.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(30, 2, 'In supply management, FIFO means First In, First ____. Type the missing word.', 'short_answer', 10.00, 8, 'out', 'FIFO means older supplies are used first so nothing expires or goes to waste.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(31, 2, 'Put the steps for restocking office supplies in the correct order.', 'ordering', 10.00, 9, NULL, 'Check stock, list what is low, order it, check the delivery, and then store it.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(32, 2, 'Match each workplace item to its purpose.', 'matching', 10.00, 10, NULL, 'Each item keeps the office organized in a different way.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(33, 2, 'A [blank] list helps you track tasks, and a [blank] is the date by which a task must be finished.', 'fill_blank', 10.00, 11, 'to-do|deadline', 'A to-do list tracks the work, and a deadline tells you when it is due.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(34, 2, 'Keeping the workspace [blank] and tidy helps you work [blank].', 'fill_blank', 10.00, 12, 'organized|efficiently', 'An organized space lets you work faster and make fewer mistakes.', 'published', '2026-10-15 02:44:37', '2026-10-15 02:44:37'),
(35, 3, 'Which filing system arranges files in A-to-Z order by name?', 'multiple_choice', 10.00, 3, NULL, 'Alphabetical filing sorts files by name, usually last name first for people.', 'published', '2026-10-15 02:44:58', '2026-10-15 02:44:58'),
(36, 3, 'Which filing system arranges documents by date, with the newest on top?', 'multiple_choice', 10.00, 4, NULL, 'Chronological filing is based on dates, which suits invoices, letters, and reports.', 'published', '2026-10-15 02:44:58', '2026-10-15 02:44:58'),
(37, 3, 'Your office keeps customer files identified by account numbers. Staff must find a file quickly by number, and customer names should stay less visible.\n\nWhich filing system fits best?', 'multiple_choice', 10.00, 5, NULL, 'Numerical filing uses numbers, which makes lookup fast and keeps names off the folders.', 'published', '2026-10-15 02:44:58', '2026-10-15 02:44:58'),
(38, 3, 'True or False: Labeling folders clearly makes documents easier to find.', 'true_false', 5.00, 6, 'True', 'Clear labels let anyone find or return a folder without guessing.', 'published', '2026-10-15 02:44:58', '2026-10-15 02:44:58'),
(39, 3, 'True or False: In alphabetical filing by last name, the file \"Reyes, Ana\" comes before \"Cruz, Mark\".', 'true_false', 5.00, 7, 'False', 'C comes before R, so Cruz is filed before Reyes.', 'published', '2026-10-15 02:44:58', '2026-10-15 02:44:58'),
(40, 3, 'What is the one-word name for the filing system that arranges documents by place, such as city or region?', 'short_answer', 10.00, 8, 'geographic', 'Geographic filing groups documents by location.', 'published', '2026-10-15 02:44:59', '2026-10-15 02:44:59'),
(41, 3, 'Put the steps of filing a document in the correct order.', 'ordering', 10.00, 9, NULL, 'The standard routine is inspect, index, code, sort, and store.', 'published', '2026-10-15 02:44:59', '2026-10-15 02:44:59'),
(42, 3, 'Match each filing system to how it arranges documents.', 'matching', 10.00, 10, NULL, 'Each system uses a different rule, so pick the one that fits how people search.', 'published', '2026-10-15 02:44:59', '2026-10-15 02:44:59'),
(43, 3, 'In [blank] filing, documents are arranged by date, while in [blank] filing they are arranged by number.', 'fill_blank', 10.00, 11, 'chronological|numerical', 'Chronological means by date and numerical means by number.', 'published', '2026-10-15 02:44:59', '2026-10-15 02:44:59'),
(44, 3, 'Each folder should have a clear [blank] so that anyone can find the [blank] inside quickly.', 'fill_blank', 10.00, 12, 'label|documents', 'A clear label tells people what is in the folder without opening it.', 'published', '2026-10-15 02:44:59', '2026-10-15 02:44:59'),
(45, 4, 'Which behavior shows professional conduct at work?', 'multiple_choice', 10.00, 3, NULL, 'Punctuality and following policies show reliability and respect for the workplace.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(46, 4, 'Why should office records be accurate and up to date?', 'multiple_choice', 10.00, 4, NULL, 'Decisions, reports, and services all depend on correct information.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(47, 4, 'A coworker from another department asks you for a copy of a personnel file for another employee. They say they are just curious.\n\nWhat should you do?', 'multiple_choice', 10.00, 5, NULL, 'Personnel files are confidential, and curiosity is not a valid reason for access.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(48, 4, 'True or False: Records should be kept only as long as required, then disposed of following company policy.', 'true_false', 5.00, 6, 'True', 'Retention rules keep needed records available and remove those no longer required.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(49, 4, 'True or False: It is acceptable to share your computer password with a coworker to save time.', 'true_false', 5.00, 7, 'False', 'Passwords are private, and sharing them puts records and accountability at risk.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(50, 4, 'What is the one-word name for destroying confidential paper records by cutting them into tiny pieces? (It ends in -ing.)', 'short_answer', 10.00, 8, 'shredding', 'Shredding makes confidential paper unreadable before disposal.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(51, 4, 'Put the steps for handling a confidential document in the correct order.', 'ordering', 10.00, 9, NULL, 'Secure it, share it only with authorized people, return it to storage, and shred it when it is no longer needed.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(52, 4, 'Match each professional practice to an example of it.', 'matching', 10.00, 10, NULL, 'Each practice shows respect for the workplace, in a different way.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(53, 4, 'Professional employees respect [blank] by never sharing private [blank] with unauthorized people.', 'fill_blank', 10.00, 11, 'confidentiality|information', 'Confidentiality means protecting private information from those not allowed to see it.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(54, 4, 'Accurate [blank] help an office make good [blank].', 'fill_blank', 10.00, 12, 'records|decisions', 'Good decisions need reliable records behind them.', 'published', '2026-10-15 02:45:14', '2026-10-15 02:45:14'),
(55, 5, 'Which of the following is a principle of effective business communication?', 'multiple_choice', 10.00, 3, NULL, 'Clear, concise messages are easy to understand and respect the reader time.', 'published', '2026-10-15 02:46:28', '2026-10-15 02:46:28'),
(56, 5, 'In the communication process, what is the person who receives the message called?', 'multiple_choice', 10.00, 4, NULL, 'The sender creates the message and the receiver interprets it.', 'published', '2026-10-15 02:46:28', '2026-10-15 02:46:28'),
(57, 5, 'You need to tell a coworker that a report deadline moved from Friday to Wednesday.\n\nWhich message is the clearest?', 'multiple_choice', 10.00, 5, NULL, 'A clear message gives the specific item, the old date, the new date, and the time.', 'published', '2026-10-15 02:46:28', '2026-10-15 02:46:28'),
(58, 5, 'True or False: Choosing the right channel, such as email or a phone call, depends on the message and the audience.', 'true_false', 5.00, 6, 'True', 'A quick question suits a call, while a record of a decision suits email.', 'published', '2026-10-15 02:46:28', '2026-10-15 02:46:28'),
(59, 5, 'True or False: Slang and abbreviations are always appropriate in formal business messages.', 'true_false', 5.00, 7, 'False', 'Formal messages need professional language that every reader can understand.', 'published', '2026-10-15 02:46:28', '2026-10-15 02:46:28'),
(60, 5, 'What is the one-word term for the response a receiver gives back to the sender?', 'short_answer', 10.00, 8, 'feedback', 'Feedback shows the sender whether the message was understood.', 'published', '2026-10-15 02:46:29', '2026-10-15 02:46:29'),
(61, 5, 'Put the steps of the communication process in the correct order.', 'ordering', 10.00, 9, NULL, 'The sender forms the message, it travels through a channel, and the receiver interprets it and responds.', 'published', '2026-10-15 02:46:29', '2026-10-15 02:46:29'),
(62, 5, 'Match each principle of good communication to its meaning.', 'matching', 10.00, 10, NULL, 'Good business messages combine all four qualities.', 'published', '2026-10-15 02:46:29', '2026-10-15 02:46:29'),
(63, 5, 'The [blank] sends the message, and the [blank] receives it.', 'fill_blank', 10.00, 11, 'sender|receiver', 'Communication needs both a sender and a receiver.', 'published', '2026-10-15 02:46:29', '2026-10-15 02:46:29'),
(64, 5, 'A [blank] message gets to the point quickly and saves [blank].', 'fill_blank', 10.00, 12, 'concise|time', 'Concise messages avoid extra words and respect the reader time.', 'published', '2026-10-15 02:46:29', '2026-10-15 02:46:29'),
(65, 6, 'Which part of an email briefly tells the reader what the message is about?', 'multiple_choice', 10.00, 3, NULL, 'A clear subject line helps the reader understand and prioritize the email.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(66, 6, 'Which closing is appropriate for a formal business letter?', 'multiple_choice', 10.00, 4, NULL, 'Formal letters end with a polite closing such as Sincerely or Respectfully.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(67, 6, 'You are emailing a customer about a delayed order.\n\nWhich opening is the most professional?', 'multiple_choice', 10.00, 5, NULL, 'A polite greeting by name and a thank-you set a respectful tone.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(68, 6, 'True or False: You should proofread an email before sending it.', 'true_false', 5.00, 6, 'True', 'Proofreading catches errors that could look unprofessional.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(69, 6, 'True or False: Writing a business email entirely in capital letters is considered polite.', 'true_false', 5.00, 7, 'False', 'All capital letters look like shouting and are hard to read.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(70, 6, 'What is the one-word name for the greeting in a letter, such as \"Dear Mr. Cruz,\"?', 'short_answer', 10.00, 8, 'salutation', 'The salutation greets the reader at the start of a letter or email.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(71, 6, 'Put the parts of a business letter in the correct order.', 'ordering', 10.00, 9, NULL, 'A letter flows from the sender details down to the closing and signature.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(72, 6, 'Match each part of an email to its purpose.', 'matching', 10.00, 10, NULL, 'Each part has a specific job in a professional email.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(73, 6, 'A professional email should have a clear [blank] line and end with a polite [blank].', 'fill_blank', 10.00, 11, 'subject|closing', 'A clear subject and a polite closing make an email look professional.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(74, 6, 'Always [blank] your message for spelling and grammar errors before you [blank] it.', 'fill_blank', 10.00, 12, 'proofread|send', 'Check first, then send, because an email cannot be taken back.', 'published', '2026-10-15 02:46:40', '2026-10-15 02:46:40'),
(75, 7, 'What should you do first when you answer a business call?', 'multiple_choice', 10.00, 3, NULL, 'A friendly greeting with your name and office tells the caller they reached the right place.', 'published', '2026-10-15 02:47:08', '2026-10-15 02:47:08'),
(76, 7, 'What should you do before placing a caller on hold?', 'multiple_choice', 10.00, 4, NULL, 'Asking permission is polite and lets the caller choose to wait or leave a message.', 'published', '2026-10-15 02:47:08', '2026-10-15 02:47:08'),
(77, 7, 'A caller asks to speak with your manager, who is in a meeting.\n\nWhat is the best response?', 'multiple_choice', 10.00, 5, NULL, 'Explaining politely and offering a message or call back keeps the caller informed and helped.', 'published', '2026-10-15 02:47:08', '2026-10-15 02:47:08'),
(78, 7, 'True or False: Your tone of voice on the phone matters as much as the words you say.', 'true_false', 5.00, 6, 'True', 'Callers cannot see you, so tone shows friendliness and respect.', 'published', '2026-10-15 02:47:09', '2026-10-15 02:47:09'),
(79, 7, 'True or False: When taking a message, you do not need to write down the caller name or number.', 'true_false', 5.00, 7, 'False', 'Without the name and number, the person you are helping cannot return the call.', 'published', '2026-10-15 02:47:09', '2026-10-15 02:47:09'),
(80, 7, 'Good telephone ____ means using polite, professional manners when speaking on the phone. Type the missing word.', 'short_answer', 10.00, 8, 'etiquette', 'Etiquette is the set of polite behaviors expected in a situation.', 'published', '2026-10-15 02:47:09', '2026-10-15 02:47:09'),
(81, 7, 'Put the steps for taking a phone message in the correct order.', 'ordering', 10.00, 9, NULL, 'Answer politely, collect the details, confirm them, and pass the message on quickly.', 'published', '2026-10-15 02:47:09', '2026-10-15 02:47:09'),
(82, 7, 'Match each telephone phrase to its purpose.', 'matching', 10.00, 10, NULL, 'Using the right phrase makes each call smooth and professional.', 'published', '2026-10-15 02:47:09', '2026-10-15 02:47:09'),
(83, 7, 'Answer the phone politely and [blank] yourself and your [blank].', 'fill_blank', 10.00, 11, 'identify|office', 'Callers should know right away who they have reached.', 'published', '2026-10-15 02:47:09', '2026-10-15 02:47:09'),
(84, 7, 'When taking a message, write down the name of the caller, their number, and the [blank] and [blank] of the call.', 'fill_blank', 10.00, 12, 'date|time', 'The date and time help the person judge how urgent the message is.', 'published', '2026-10-15 02:47:10', '2026-10-15 02:47:10'),
(85, 8, 'Which is the best way to organize a business message?', 'multiple_choice', 10.00, 3, NULL, 'Putting the purpose first helps the reader understand what the message is for right away.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(86, 8, 'Which wording is the most suitable for a formal business message?', 'multiple_choice', 10.00, 4, NULL, 'Formal messages use polite, standard language and avoid slang.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(87, 8, 'You must politely decline a supplier request.\n\nWhich approach is best?', 'multiple_choice', 10.00, 5, NULL, 'A thank-you, a clear decision, and a brief courteous reason protect the business relationship.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(88, 8, 'True or False: Short paragraphs and clear headings make a message easier to read.', 'true_false', 5.00, 6, 'True', 'Breaking text into small parts helps readers find information quickly.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(89, 8, 'True or False: Bad news in business writing should be delivered in a rude tone so it is taken seriously.', 'true_false', 5.00, 7, 'False', 'Even bad news should be delivered respectfully and clearly.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(90, 8, 'What is the one-word term for the attitude a message gives the reader, such as polite or friendly?', 'short_answer', 10.00, 8, 'tone', 'Tone affects how the reader feels about the message and the writer.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(91, 8, 'Put the steps of writing a business message in the correct order.', 'ordering', 10.00, 9, NULL, 'Good writing starts with a purpose and ends with a proofread message.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(92, 8, 'Match each type of business correspondence to its purpose.', 'matching', 10.00, 10, NULL, 'Choosing the right format helps the message reach the right people the right way.', 'published', '2026-10-15 02:47:25', '2026-10-15 02:47:25'),
(93, 8, 'Begin a business message by stating its [blank], and keep sentences [blank].', 'fill_blank', 10.00, 11, 'purpose|short', 'A clear purpose and short sentences help readers understand quickly.', 'published', '2026-10-15 02:47:26', '2026-10-15 02:47:26'),
(94, 8, 'Use a [blank] and respectful [blank] so the reader feels valued.', 'fill_blank', 10.00, 12, 'polite|tone', 'A courteous tone builds good working relationships.', 'published', '2026-10-15 02:47:26', '2026-10-15 02:47:26'),
(95, 9, 'Which habit helps improve productivity at work?', 'multiple_choice', 10.00, 3, NULL, 'Planning your day and prioritizing tasks keeps you focused on what matters most.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(96, 9, 'Which tool is best for scheduling meetings and tracking appointments?', 'multiple_choice', 10.00, 4, NULL, 'A calendar application shows dates and times and can send reminders.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(97, 9, 'You have been given five tasks today and you realize you cannot finish all of them.\n\nWhat is the best first step?', 'multiple_choice', 10.00, 5, NULL, 'Listing and prioritizing tasks lets you finish the most urgent and important ones first.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(98, 9, 'True or False: Taking short breaks can help you stay focused during the workday.', 'true_false', 5.00, 6, 'True', 'Short breaks help you rest your mind and keep your attention on the work.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(99, 9, 'True or False: Doing many tasks at the same time always improves the quality of your work.', 'true_false', 5.00, 7, 'False', 'Switching between many tasks usually leads to more mistakes and slower progress.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(100, 9, 'What is the one-word term for completing tasks quickly with little wasted time or effort?', 'short_answer', 10.00, 8, 'efficiency', 'Efficiency means getting good results without wasting time or resources.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(101, 9, 'Put the steps for planning your workday in the correct order.', 'ordering', 10.00, 9, NULL, 'Review what is coming, list tasks, set priorities, schedule time, and check progress at the end.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(102, 9, 'Match each office tool to its main use.', 'matching', 10.00, 10, NULL, 'Each tool is designed for a different kind of office work.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(103, 9, 'Planning your day and [blank] tasks helps you work more [blank].', 'fill_blank', 10.00, 11, 'prioritizing|efficiently', 'Setting priorities helps you use your time well.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(104, 9, 'A [blank] processor is used to type letters, and a [blank] is used to organize numbers.', 'fill_blank', 10.00, 12, 'word|spreadsheet', 'Word processors handle text and spreadsheets handle numbers.', 'published', '2026-10-15 02:54:33', '2026-10-15 02:54:33'),
(105, 10, 'Which formatting choice makes a document easier to read?', 'multiple_choice', 10.00, 3, NULL, 'Consistent fonts, headings, and spacing help readers follow the document.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(106, 10, 'Which font choice is most appropriate for a formal business document?', 'multiple_choice', 10.00, 4, NULL, 'A clean, standard font at a readable size looks professional.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(107, 10, 'You are preparing a five-page report, and the headings look different on each page.\n\nWhat should you do?', 'multiple_choice', 10.00, 5, NULL, 'Applying one heading style everywhere keeps the document consistent.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(108, 10, 'True or False: Consistent formatting makes a document look more professional.', 'true_false', 5.00, 6, 'True', 'A uniform style shows care and makes the document easier to read.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(109, 10, 'True or False: Using five different fonts in one document improves readability.', 'true_false', 5.00, 7, 'False', 'Too many fonts make a document look messy and distract the reader.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(110, 10, 'What is the one-word term (singular) for the blank space around the edges of a page?', 'short_answer', 10.00, 8, 'margin', 'Margins keep text away from the edge of the paper and give the page breathing room.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(111, 10, 'Put the steps of preparing a document in the correct order.', 'ordering', 10.00, 9, NULL, 'Plan first, write, format, proofread, and then save or share.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(112, 10, 'Match each formatting feature to its use.', 'matching', 10.00, 10, NULL, 'Each feature helps organize or present information clearly.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(113, 10, 'Use consistent [blank] and [blank] so the whole document looks neat.', 'fill_blank', 10.00, 11, 'fonts|spacing', 'Consistent fonts and spacing give the document a clean look.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(114, 10, 'Always [blank] a document and [blank] it before sharing.', 'fill_blank', 10.00, 12, 'proofread|save', 'Check for mistakes first and save your work before sharing.', 'published', '2026-10-15 02:54:46', '2026-10-15 02:54:46'),
(115, 11, 'In a spreadsheet, what is the box where a row and a column meet called?', 'multiple_choice', 10.00, 3, NULL, 'A cell is the basic unit of a spreadsheet and holds one piece of data.', 'published', '2026-10-15 02:54:59', '2026-10-15 02:54:59'),
(116, 11, 'Which formula adds the values in cells A1 to A5?', 'multiple_choice', 10.00, 4, NULL, 'The SUM function with a range such as A1:A5 adds all the cells in that range.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(117, 11, 'You are entering 200 customer records, and you notice some phone numbers have missing digits.\n\nWhat is the best way to prevent this problem?', 'multiple_choice', 10.00, 5, NULL, 'Verifying entries against the source document catches mistakes before they spread.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(118, 11, 'True or False: A formula in a spreadsheet usually starts with an equals sign (=).', 'true_false', 5.00, 6, 'True', 'The equals sign tells the spreadsheet that what follows is a calculation.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(119, 11, 'True or False: Accuracy in data entry does not matter as long as you type quickly.', 'true_false', 5.00, 7, 'False', 'Errors in data can lead to wrong calculations and bad decisions.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(120, 11, 'What is the one-word name for a vertical line of cells in a spreadsheet?', 'short_answer', 10.00, 8, 'column', 'Columns run up and down and are usually labeled with letters.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(121, 11, 'Put the steps for adding up a column of numbers in the correct order.', 'ordering', 10.00, 9, NULL, 'Enter the data, choose the result cell, type the formula, confirm it, and check the answer.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(122, 11, 'Match each spreadsheet term to its meaning.', 'matching', 10.00, 10, NULL, 'Knowing these terms makes it easier to read and build spreadsheets.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(123, 11, 'A spreadsheet is made up of [blank] and [blank].', 'fill_blank', 10.00, 11, 'rows|columns', 'Rows and columns together form the grid of cells.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(124, 11, 'Always [blank] your data entries for [blank].', 'fill_blank', 10.00, 12, 'check|accuracy', 'Checking your entries keeps the data accurate and reliable.', 'published', '2026-10-15 02:55:00', '2026-10-15 02:55:00'),
(125, 12, 'Which choice describes the basic lifecycle of a record?', 'multiple_choice', 10.00, 3, NULL, 'Records are created, used, stored, and finally disposed of when no longer needed.', 'published', '2026-10-15 02:55:12', '2026-10-15 02:55:12'),
(126, 12, 'Which is a good way to protect electronic records?', 'multiple_choice', 10.00, 4, NULL, 'Strong passwords limit access, and backups protect against loss.', 'published', '2026-10-15 02:55:12', '2026-10-15 02:55:12'),
(127, 12, 'You find a folder of customer records left on a shared printer.\n\nWhat should you do?', 'multiple_choice', 10.00, 5, NULL, 'Securing the folder and returning it or reporting it protects the customers private information.', 'published', '2026-10-15 02:55:12', '2026-10-15 02:55:12'),
(128, 12, 'True or False: Backing up electronic files helps protect against data loss.', 'true_false', 5.00, 6, 'True', 'A backup lets you restore files if the originals are lost or damaged.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(129, 12, 'True or False: It is safe to write your password on a sticky note on your monitor.', 'true_false', 5.00, 7, 'False', 'Anyone who passes by could see it and access your accounts and records.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(130, 12, 'What is the one-word term for a copy of files kept so they can be restored if the originals are lost?', 'short_answer', 10.00, 8, 'backup', 'Regular backups are a key part of protecting electronic records.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(131, 12, 'Put the stages of the records lifecycle in the correct order.', 'ordering', 10.00, 9, NULL, 'A record is created, used, stored, kept for the required time, and then disposed of safely.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(132, 12, 'Match each protection measure to what it protects against.', 'matching', 10.00, 10, NULL, 'Different records need different kinds of protection.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(133, 12, 'Records go through a [blank] from creation to [blank].', 'fill_blank', 10.00, 11, 'lifecycle|disposal', 'The lifecycle runs from creating a record to disposing of it.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(134, 12, 'Use strong [blank] and regular [blank] to protect electronic data.', 'fill_blank', 10.00, 12, 'passwords|backups', 'Passwords block unauthorized access and backups protect against loss.', 'published', '2026-10-15 02:55:13', '2026-10-15 02:55:13'),
(135, 1, 'What is the main purpose of office administration?', 'multiple_choice', 5.00, 1, 'To organize office activities and support daily operations', 'Office administration helps organize tasks, records, communication, and daily office operations.', 'published', '2026-10-16 10:58:55', '2026-10-16 10:58:55'),
(136, 1, 'Why is it important to keep office documents organized?', 'multiple_choice', 5.00, 2, 'To find information easily and maintain accurate records', 'Organized documents help employees locate information quickly and keep reliable office records.', 'published', '2026-10-16 10:58:55', '2026-10-16 10:58:55'),
(137, 1, 'Which task is part of office administration?', 'multiple_choice', 5.00, 3, 'Managing records and organizing office tasks', 'Office administration includes managing records and coordinating daily tasks.', 'published', '2026-10-16 11:00:45', '2026-10-16 11:00:45'),
(138, 1, 'Why should office files be labeled clearly?', 'multiple_choice', 5.00, 4, 'To make files easier to identify and retrieve', 'Clear labels help employees locate the correct files quickly.', 'published', '2026-10-16 11:00:45', '2026-10-16 11:00:45'),
(139, 1, 'What should you do when an important office message is received?', 'multiple_choice', 5.00, 5, 'Record the details and forward it to the appropriate person', 'Important messages should be recorded and sent to the correct person.', 'published', '2026-10-16 11:00:45', '2026-10-16 11:00:45'),
(140, 1, 'Why are office schedules useful?', 'multiple_choice', 5.00, 6, 'They help employees manage time and complete tasks on time', 'Schedules help organize work and meet deadlines.', 'published', '2026-10-16 11:00:45', '2026-10-16 11:00:45'),
(141, 1, 'Which behavior shows professionalism in an office?', 'multiple_choice', 5.00, 7, 'Being respectful, reliable, and responsible', 'Professional behavior includes respect, reliability, and responsibility.', 'published', '2026-10-16 11:00:46', '2026-10-16 11:00:46'),
(142, 1, 'What is the best way to protect confidential office information?', 'multiple_choice', 5.00, 8, 'Share it only with authorized people', 'Confidential information must be handled carefully and shared only when authorized.', 'published', '2026-10-16 11:00:46', '2026-10-16 11:00:46'),
(143, 1, 'Why should office equipment be handled properly?', 'multiple_choice', 5.00, 9, 'To keep it working safely and reduce damage', 'Proper handling helps protect equipment and supports workplace safety.', 'published', '2026-10-16 11:00:46', '2026-10-16 11:00:46'),
(144, 1, 'What should you do if you notice a mistake in an office record?', 'multiple_choice', 5.00, 10, 'Follow the correction procedure and inform the responsible person', 'Office records should be corrected properly to maintain accuracy.', 'published', '2026-10-16 11:00:46', '2026-10-16 11:00:46'),
(145, 1, 'Why is clear communication important in office administration?', 'multiple_choice', 5.00, 11, 'It helps people understand instructions and avoid mistakes', 'Clear communication supports teamwork and reduces misunderstandings.', 'published', '2026-10-16 11:00:46', '2026-10-16 11:00:46'),
(146, 1, 'What should be done at the end of the workday?', 'multiple_choice', 5.00, 12, 'Secure documents and organize the workspace', 'Securing documents and organizing the workspace helps maintain safety and readiness for the next workday.', 'published', '2026-10-16 11:00:46', '2026-10-16 11:00:46');

-- --------------------------------------------------------

--
-- Table structure for table `admin_preferences`
--

CREATE TABLE `admin_preferences` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `dark_mode` tinyint(1) NOT NULL DEFAULT 0,
  `default_date_range` varchar(20) NOT NULL DEFAULT 'all',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_preferences`
--

INSERT INTO `admin_preferences` (`id`, `user_id`, `dark_mode`, `default_date_range`, `updated_at`) VALUES
(1, 6, 0, '30', '2026-10-30 07:37:11');

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

CREATE TABLE `challenges` (
  `challenge_id` int(10) UNSIGNED NOT NULL,
  `mission_id` int(10) UNSIGNED NOT NULL,
  `challenge_title` varchar(200) NOT NULL,
  `challenge_description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `total_points` smallint(5) UNSIGNED NOT NULL DEFAULT 20,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 70.00,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `challenges`
--

INSERT INTO `challenges` (`challenge_id`, `mission_id`, `challenge_title`, `challenge_description`, `instructions`, `total_points`, `passing_score`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Office Support Scenario', 'Apply basic office administration skills to a workplace scenario.', 'Read the scenario and submit the requested response.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(2, 2, 'Workplace Organization Scenario', 'Apply an organized process to a routine office task.', 'Describe or select the steps you would take to complete the task.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(3, 3, 'Document Retrieval Scenario', 'Choose a filing approach for a set of office records.', 'Explain how you would file and retrieve the records.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(4, 4, 'Responsible Records Scenario', 'Apply professional practices to handling office records.', 'Choose the correct action and explain why it is appropriate.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(5, 5, 'Communication Scenario', 'Respond to a common workplace communication situation.', 'Prepare a clear and respectful response to the situation.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(6, 6, 'Professional Message Scenario', 'Prepare an appropriate professional message.', 'Use a suitable subject, greeting, message body, and closing.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(7, 7, 'Telephone Call Scenario', 'Handle a workplace phone call professionally.', 'Write the key information you would record and the next action.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(8, 8, 'Correspondence Revision Scenario', 'Improve a message so it is clear and professional.', 'Revise the message using clear wording and an appropriate tone.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(9, 9, 'Productivity Planning Scenario', 'Plan an efficient approach to a set of office tasks.', 'Arrange the tasks in a sensible order and explain your priorities.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(10, 10, 'Document Preparation Scenario', 'Prepare a consistent office document.', 'Describe the formatting checks you would complete before submission.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(11, 11, 'Spreadsheet Accuracy Scenario', 'Check a small set of spreadsheet data.', 'Identify possible entry errors and explain how you would verify the values.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(12, 12, 'Data Protection Scenario', 'Respond to a possible information-security issue.', 'Choose the safest action and explain how the records should be protected.', 20, 70.00, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `challenge_attempts`
--

CREATE TABLE `challenge_attempts` (
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `challenge_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `answer_text` longtext DEFAULT NULL,
  `score` decimal(7,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  `result` enum('passed','failed') DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `challenge_attempts`
--

INSERT INTO `challenge_attempts` (`attempt_id`, `user_id`, `challenge_id`, `attempt_number`, `answer_text`, `score`, `status`, `result`, `feedback`, `started_at`, `submitted_at`, `graded_at`) VALUES
(1, 5, 4, 1, 'Honesty is the correct action and why it is appropriate because it basic human law', 0.00, 'graded', NULL, 'Not passed yet. Your answer met 0 out of 5 required criteria. Score: 0%. Review the challenge instructions and try again.', '2026-10-15 11:40:37', '2026-10-15 11:40:37', '2026-10-15 11:40:37'),
(2, 5, 4, 2, 'Protecting confidential information -  because it is important documents and we need to protect these papers.', 5.00, 'graded', NULL, 'Your answer contains 8 meaningful words. Write at least 30 meaningful words and include relevant information about the challenge. Your keyword score before the minimum-length requirement was 25/100. Result: FAILED.', '2026-10-15 14:13:37', '2026-10-15 14:13:37', '2026-10-15 14:13:37'),
(3, 5, 4, 3, 'Protecting confidential information - we can protect it because it\'s important documents and love care mean the world to me to the point that i cant live without you so please dont i want karioka right know can you buy me some 3 karioka not right now we have prioritize to do maybe when we are done on this website fuckshit', 5.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 1 of 5 criteria matched. Score: 25/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 14:19:07', '2026-10-15 14:19:07', '2026-10-15 14:19:07'),
(4, 5, 4, 4, 'We need to protect confidential information because it contains private and important details that should not be shared with unauthorized people. It helps protect people\'s privacy, avoid problems like identity theft, and maintain trust in the workplace. For example, as an office administration student or employee, we should keep customer records, personal information, and company documents safe.', 5.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 1 of 5 criteria matched. Score: 25/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 14:20:38', '2026-10-15 14:20:38', '2026-10-15 14:20:38'),
(5, 5, 4, 5, 'We need to protect confidential information because it contains private and important details that should not be shared with unauthorized people. It helps protect people\'s privacy, avoid problems like identity theft, and maintain trust in the workplace. For example, as an office administration student or employee, we should keep customer records, personal information, and company documents safe.', 5.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 1 of 5 criteria matched. Score: 25/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 14:21:10', '2026-10-15 14:21:10', '2026-10-15 14:21:10'),
(6, 5, 4, 6, 'We need to protect confidential information because it contains private and important details that should not be shared with unauthorized people. It helps protect people\'s privacy, avoid problems like identity theft, and maintain trust in the workplace. For example, as an office administration student or employee, we should keep customer records, personal information, and company documents safe.', 5.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 1 of 5 criteria matched. Score: 25/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 14:23:58', '2026-10-15 14:23:58', '2026-10-15 14:23:58'),
(7, 5, 4, 7, '\"the\", \"and\", \"for\", \"are\", \"but\", \"not\", \"you\", \"your\",\r\n                \"with\", \"this\", \"that\", \"these\", \"those\", \"was\", \"were\",\r\n                \"is\", \"am\", \"be\", \"been\", \"being\", \"to\", \"of\", \"in\",\r\n                \"on\", \"at\", \"by\", \"as\", \"an\", \"a\", \"or\", \"if\", \"it\",\r\n                \"its\", \"we\", \"our\", \"they\", \"their\", \"he\", \"she\", \"his\",\r\n                \"her\", \"them\", \"from\", \"then\", \"than\", \"there\", \"here\",\r\n                \"can\", \"could\", \"would\", \"should\", \"will\", \"may\", \"might\",\r\n                \"must\", \"have\", \"has\", \"had\", \"do\", \"does\", \"did\", \"i\",\r\n                \"me\", \"my\", \"mine\", \"so\", \"such\", \"also\", \"very\", \"more\",\r\n                \"most\", \"some\", \"any\", \"each\", \"all\", \"both\", \"what\",\r\n                \"when\", \"where\", \"which\", \"who\", \"why\", \"how\", \"because\",\r\n                \"while\", \"after\", \"before\", \"during\", \"about\", \"into\",\r\n                \"through\", \"up\", \"down\", \"out\", \"off\", \"over\", \"under\",\r\n                \"again\", \"once\", \"only\", \"own\", \"same\", \"just\", \"other\",\r\n                \"another\", \"much\", \"many\", \"few\", \"less\"', 0.00, 'graded', NULL, 'Your answer contains 0 meaningful words. Write at least 20 meaningful words and include relevant information about the challenge. Your keyword score before the minimum-length requirement was 0/100. Result: FAILED.', '2026-10-15 14:26:06', '2026-10-15 14:26:06', '2026-10-15 14:26:06'),
(8, 5, 4, 8, 'I will return equipment after using it. We need to protect confidential information because it contains private and important details that should not be shared with unauthorized people. It helps protect people\'s privacy, avoid problems like identity theft, and maintain trust in the workplace. For example, as an office administration student or employee, we should keep customer records, personal information, and company documents safe.', 5.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 1 of 5 criteria matched. Score: 25/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 14:28:45', '2026-10-15 14:28:45', '2026-10-15 14:28:45'),
(9, 5, 4, 9, 'I will organize my tasks and prioritize the most urgent and important activities first. I will make a checklist and follow a schedule to meet deadlines. I will communicate with my supervisor and coworkers to provide updates and ask questions when needed. I will keep documents and records organized, check office supplies, and review my work to make sure the information is accurate and complete.', 8.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 2 of 5 criteria matched. Score: 40/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 14:29:21', '2026-10-15 14:29:21', '2026-10-15 14:29:21'),
(10, 5, 4, 10, 'I will keep office records accurate, complete, and organized. I will protect confidential information and allow only authorized people to access the records. I will follow the proper filing system, verify information before saving it, and follow the records retention policy. If I find missing or incorrect information, I will report it to my supervisor. These actions help maintain privacy, accuracy, security, and accountability in the workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-10-15 14:29:49', '2026-10-15 14:29:49', '2026-10-15 14:29:49'),
(11, 5, 1, 1, 'I will keep office records accurate, complete, and organized. I will protect confidential information and allow only authorized people to access the records. I will follow the proper filing system, verify information before saving it, and follow the records retention policy. If I find missing or incorrect information, I will report it to my supervisor. These actions help maintain privacy, accuracy, security, and accountability in the workplace.', 10.00, 'graded', 'failed', 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 50/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 20:41:33', '2026-10-15 20:41:33', '2026-10-15 20:41:33'),
(12, 5, 1, 2, 'I will keep office records accurate, complete, and organized. I will protect confidential information and allow only authorized people to access the records. I will follow the proper filing system, verify information before saving it, and follow the records retention policy. If I find missing or incorrect information, I will report it to my supervisor. These actions help maintain privacy, accuracy, security, and accountability in the workplace.', 10.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 50/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 20:48:38', '2026-10-15 20:48:38', '2026-10-15 20:48:38'),
(13, 5, 1, 3, 'I will keep office records accurate, complete, and organized. I will check the information before saving it, protect confidential records, and allow only authorized people to access them. I will follow the proper filing system and records retention policy. If I find missing or incorrect information, I will report it to my supervisor to maintain accuracy, privacy, security, and accountability.', 10.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 50/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 20:49:15', '2026-10-15 20:49:15', '2026-10-15 20:49:15'),
(14, 5, 1, 4, 'I will maintain accurate, complete, and organized office records by verifying all information before saving it. I will protect confidential information and ensure that only authorized people can access the records. I will follow the proper filing system and records retention policy. I will report missing, incorrect, or unauthorized changes to my supervisor. These practices ensure accuracy, confidentiality, data security, privacy, and accountability in managing office records.', 10.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 50/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 20:49:35', '2026-10-15 20:49:35', '2026-10-15 20:49:35'),
(15, 5, 1, 5, 'I will maintain accurate and complete records by checking and verifying all information before saving it. I will protect confidentiality and privacy by keeping private information secure. I will allow only authorized people to access records and require proper permission for restricted access. I will follow the records retention policy and ensure secure disposal of records when necessary. If I find errors or missing records, I will report them to my supervisor. I will take responsibility for my work and maintain accountability to ensure accurate, complete, and secure office records.', 10.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 50/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-15 20:51:33', '2026-10-15 20:51:33', '2026-10-15 20:51:33'),
(16, 5, 1, 6, 'I will apply basic office administration skills by organizing my tasks, prioritizing urgent and important work, and following deadlines. I will use a checklist and schedule to plan my daily activities and make sure all assigned tasks are completed on time. I will organize office documents and records using the proper filing system so they are easy to find. I will verify information to maintain accuracy and completeness. I will communicate with my coworkers and inform my supervisor about updates, problems, or missing information. I will also protect confidential records and allow only authorized people to access them. These actions will help me maintain an organized, accurate, secure, and productive workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-10-15 20:52:22', '2026-10-15 20:52:22', '2026-10-15 20:52:22'),
(17, 5, 2, 1, 'I will apply an organized process to a routine office task by planning my work, prioritizing important tasks, and following deadlines. I will use a checklist and schedule to organize my activities and complete each step properly. I will arrange and label documents using the proper filing system so they are easy to find and retrieve. I will verify information to ensure accuracy and completeness before saving records. I will communicate with my coworkers and inform my supervisor about any problems or missing information. I will also protect confidential records and allow only authorized people to access them. Finally, I will review my work to correct errors and make sure the task is completed accurately, safely, and on time.', 14.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 3 of 5 criteria matched. Score: 70/100. Result: PASSED.', '2026-10-15 21:00:03', '2026-10-15 21:00:03', '2026-10-15 21:00:03'),
(18, 5, 3, 1, 'I will choose the appropriate filing system based on the type of office records. I will use alphabetical filing for records arranged by names, numerical filing for records with assigned numbers, and chronological filing for documents arranged by date. I will categorize and label each file clearly using a consistent naming convention so documents are easy to locate and retrieve. I will keep confidential records secure and allow only authorized people to access them. I will also check the files regularly to maintain an organized, accurate, and complete filing system.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-10-15 21:03:26', '2026-10-15 21:03:26', '2026-10-15 21:03:26'),
(19, 5, 5, 1, 'I will respond to the workplace communication situation in a clear, polite, and professional manner. First, I will listen carefully to understand the person\'s concern and ask questions to clarify any missing details. I will communicate the main point using simple and respectful language and provide accurate and complete information. I will confirm that the other person understands the message and explain the next steps, including any deadlines or required actions. If the concern needs further assistance, I will inform my supervisor or the responsible person and follow up when necessary. This approach helps prevent misunderstandings and maintain good communication in the workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-10-15 21:06:11', '2026-10-15 21:06:11', '2026-10-15 21:06:11'),
(20, 8, 5, 1, 'I will communicate clearly and respectfully, listen carefully to others, ask questions when needed, and provide appropriate feedback to avoid misunderstandings and maintain good workplace relationships.', 4.00, 'graded', NULL, 'Your answer contains 18 meaningful words. Write at least 20 meaningful words and include relevant information. Your keyword score was 20/100. Result: FAILED.', '2026-10-16 18:13:23', '2026-10-16 18:13:23', '2026-10-16 18:13:23'),
(21, 8, 5, 2, 'I will use clear, respectful, and professional communication by listening actively, asking questions, clarifying instructions, and giving constructive feedback. I will remain calm, show empathy, and work with my colleagues to resolve misunderstandings, solve problems, and maintain positive workplace relationships.', 9.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 2 of 5 criteria matched. Score: 45/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-16 18:13:43', '2026-10-16 18:13:43', '2026-10-16 18:13:43'),
(22, 8, 5, 3, 'I will practice effective workplace communication by listening actively, speaking clearly, using polite and professional language, and showing respect for different opinions. If I receive unclear instructions or encounter a misunderstanding, I will ask questions, clarify important details, and confirm that I understand the message correctly. I will also provide constructive feedback, use appropriate communication channels, maintain a positive attitude, and cooperate with my colleagues to solve problems. By being honest, respectful, empathetic, and open to feedback, I can prevent conflicts, build trust, and maintain good working relationships.', 9.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 2 of 5 criteria matched. Score: 45/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-16 18:13:59', '2026-10-16 18:13:59', '2026-10-16 18:13:59'),
(23, 8, 5, 4, 'In a workplace situation, I will communicate professionally by identifying the problem, listening carefully to the other person\'s concerns, and responding calmly and respectfully. I will use clear and concise language, ask questions to clarify information, and avoid making assumptions. I will show empathy, maintain a positive tone, and provide constructive feedback. If there is a misunderstanding, I will discuss the issue privately, focus on facts, and work together to find a solution. I will also confirm important details to ensure accurate understanding and use the appropriate communication channel. Effective communication helps prevent conflicts, improve teamwork, build trust, and achieve workplace goals.', 7.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 2 of 5 criteria matched. Score: 35/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-16 18:14:20', '2026-10-16 18:14:20', '2026-10-16 18:14:20'),
(24, 8, 5, 5, 'When responding to a workplace communication situation, I will use active listening, clear verbal communication, and professional language to understand the concern. I will remain calm, respectful, and open-minded when communicating with coworkers. I will ask clarifying questions, provide accurate information, and give constructive feedback to prevent misunderstandings. I will also consider nonverbal communication, such as eye contact, facial expressions, and appropriate body language. If a conflict occurs, I will address it professionally, focus on finding a solution, and cooperate with others. By practicing empathy, respect, teamwork, and proper communication, I can build trust, improve productivity, and maintain a positive work environment.', 12.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 60/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-10-16 18:14:43', '2026-10-16 18:14:43', '2026-10-16 18:14:43'),
(25, 8, 5, 6, 'In the workplace, effective communication is important to avoid misunderstandings and maintain good professional relationships. I will listen actively, identify the main concern, and respond with clear, concise, accurate, and respectful language. I will consider the sender\'s message, purpose, and audience before responding. I will ask clarifying questions, confirm important information, and choose the appropriate communication channel, such as email for formal messages or face-to-face conversation for sensitive concerns. I will also pay attention to tone, facial expressions, and body language. If a conflict arises, I will remain calm, show empathy, provide constructive feedback, and focus on problem-solving. By practicing professional communication, teamwork, and respect, I can build trust, prevent conflict, and help achieve organizational goals.', 14.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 3 of 5 criteria matched. Score: 70/100. Result: PASSED.', '2026-10-16 18:15:02', '2026-10-16 18:15:02', '2026-10-16 18:15:02'),
(26, 6, 1, 1, 'In an office, I would organize documents properly, manage schedules, respond to emails professionally, and keep records updated. I would prioritize important tasks, communicate clearly with coworkers, and make sure deadlines are followed. If a problem occurs, I would stay calm, find a practical solution, and inform the supervisor when necessary. These practices help maintain an organized, productive, and professional workplace.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-10-25 10:23:35', '2026-10-25 10:23:35', '2026-10-25 10:23:35'),
(27, 14, 1, 1, 'In this situation, I would apply basic office administration skills by organizing tasks, managing documents, and communicating properly with coworkers. I would prioritize important tasks, keep records accurate and updated, maintain organized files, and handle office information responsibly. I would also use time management and problem-solving skills to complete the work efficiently. If I encounter a problem, I would communicate with my supervisor and ask for guidance when needed. This would help maintain an organized, productive, and professional workplace.', 15.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 75/100. Result: PASSED.', '2026-10-29 12:11:36', '2026-10-29 12:11:36', '2026-10-29 12:11:36'),
(28, 6, 2, 1, 'I would start by identifying the task and preparing all the necessary documents and materials. Then, I would organize the files, arrange them properly, and follow the correct office procedures. I would complete the task carefully, check the information for errors, and make sure everything is accurate and properly recorded. Lastly, I would keep the workplace clean and organized to make the work easier and more efficient.', 8.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 2 of 5 criteria matched. Score: 40/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-11-03 12:02:08', '2026-11-03 12:02:08', '2026-11-03 12:02:08'),
(29, 6, 2, 2, 'First, I would identify the task and understand the instructions before starting. I would prepare the necessary documents, supplies, and office equipment to avoid delays. Next, I would arrange the documents according to their categories, label and file them properly, and keep important records in their designated folders. I would follow the correct office procedures, prioritize urgent tasks, and manage my time properly. I would also maintain a clean and organized workspace, avoid unnecessary clutter, and check my work to make sure all information is complete and accurate. Lastly, I would update the records and return all office materials to their proper places after completing the task.', 13.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 3 of 5 criteria matched. Score: 65/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-11-03 12:02:22', '2026-11-03 12:02:22', '2026-11-03 12:02:22'),
(30, 6, 2, 3, 'To complete a routine office task, I would first identify the purpose of the task and review the instructions to make sure I understand what needs to be done. I would prepare the necessary documents, office supplies, and equipment before starting. Next, I would follow a systematic process by sorting documents into categories, checking important information, labeling folders, and filing records in their proper locations. I would prioritize urgent tasks, follow the office rules and standard operating procedures, and manage my time to avoid delays. I would also use a checklist to monitor the tasks and make sure nothing is missed. After completing the work, I would review the documents for accuracy, update the filing system, record completed tasks, and return all supplies to their designated places. Finally, I would keep my desk clean, remove unnecessary papers, and maintain an organized workspace to promote productivity, efficiency, and smooth office operations.', 7.00, 'graded', NULL, 'Your answer meets the minimum length, but it needs more relevant details. 2 of 5 criteria matched. Score: 35/100. Add more accurate information about the scenario and try again. Result: FAILED.', '2026-11-03 12:02:37', '2026-11-03 12:02:37', '2026-11-03 12:02:37'),
(31, 6, 2, 4, 'If I were assigned to complete a routine office task, such as organizing and filing important documents, I would first understand the instructions and identify what needs to be accomplished. Before starting, I would prepare the necessary office supplies, folders, labels, computer, and other materials to make sure everything is ready. I would begin by collecting all documents and checking each one to make sure the information is complete and correct. After that, I would sort the documents according to their types, dates, departments, and importance. I would separate urgent documents from regular files so I could prioritize tasks and avoid missing important deadlines. Next, I would label each folder clearly, arrange the documents in alphabetical or chronological order, and place them in their designated filing cabinets or storage areas for easy access and retrieval. I would also update the filing records or office database to keep track of new documents and make sure the information is properly recorded. While doing the task, I would follow the company\'s standard operating procedures, maintain confidentiality when handling sensitive information, and use office equipment properly. I would manage my time, avoid unnecessary delays, and use a checklist to monitor my progress. Before considering the task finished, I would review the documents and check if everything is complete, accurate, properly labeled, and filed in the correct location. Lastly, I would return all office supplies and equipment to their proper places, remove unnecessary papers from my desk, and keep my workspace clean and organized. By following these steps, I can complete the task efficiently, prevent misplaced documents, reduce errors, save time, and help maintain an orderly and productive office environment.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-11-03 12:03:07', '2026-11-03 12:03:07', '2026-11-03 12:03:07'),
(32, 6, 3, 1, 'For this task, I would choose the alphabetical filing system because it is simple, organized, and makes office records easier to locate and retrieve. First, I would gather all the documents and check their names, dates, reference numbers, and other important information. I would make sure that each document is complete and belongs to the correct category. Next, I would arrange the records alphabetically according to the client\'s last name, company name, or document title. I would separate different types of records into folders, label each folder clearly, and place them in the proper filing cabinets or designated storage areas. I would also create a file index or record log containing the document names, folder labels, and storage locations so that I can easily track and find specific records when needed. When someone requests a document, I would identify the correct file, check the index, locate the folder, and verify that I have retrieved the correct record before releasing it. I would also follow the office filing procedures, maintain confidentiality, and make sure that borrowed documents are properly recorded. After using the records, I would return them to their original locations to avoid misplaced or missing files. Lastly, I would regularly check and update the filing system to keep all office records accurate, accessible, secure, and organized.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-11-03 22:01:54', '2026-11-03 22:01:54', '2026-11-03 22:01:54'),
(33, 6, 4, 1, 'Protecting confidential information -  I will keep office records accurate, complete, and organized. I will protect confidential information and allow only authorized people to access the records. I will follow the proper filing system, verify information before saving it, and follow the records retention policy. If I find missing or incorrect information, I will report it to my supervisor.\r\nThese actions help maintain privacy, accuracy, security, and accountability in the workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-11-04 10:37:57', '2026-11-04 10:37:57', '2026-11-04 10:37:57'),
(34, 16, 1, 1, 'If I were assigned to handle basic office administration tasks, I would first identify the responsibilities and understand the instructions given by my supervisor. I would prepare the necessary documents, office supplies, and equipment to make sure everything is ready before starting. I would organize incoming and outgoing documents, sort files according to their categories, record important information, and make sure all records are properly labeled and stored for easy access. I would also answer phone calls and emails professionally, assist visitors, schedule appointments, and communicate politely with employees and clients. When handling office records, I would check the accuracy of information, maintain confidentiality, and follow the company\'s standard operating procedures. I would prioritize important tasks, manage my time properly, and use a checklist to monitor my progress and avoid missing deadlines. Before finishing my work, I would review completed tasks, update necessary records, return office materials to their proper places, and keep my workspace clean and organized. By applying these basic office administration skills, I can help daily office operations run smoothly, prevent errors, provide good support to employees and clients, and maintain a professional and productive workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-11-04 14:24:20', '2026-11-04 14:24:20', '2026-11-04 14:24:20'),
(35, 17, 1, 1, 'If I were assigned to handle basic office administration tasks, I would first identify the responsibilities and understand the instructions given by my supervisor. I would prepare the necessary documents, office supplies, and equipment to make sure everything is ready before starting. I would organize incoming and outgoing documents, sort files according to their categories, record important information, and make sure all records are properly labeled and stored for easy access. I would also answer phone calls and emails professionally, assist visitors, schedule appointments, and communicate politely with employees and clients. When handling office records, I would check the accuracy of information, maintain confidentiality, and follow the company\'s standard operating procedures. I would prioritize important tasks, manage my time properly, and use a checklist to monitor my progress and avoid missing deadlines. Before finishing my work, I would review completed tasks, update necessary records, return office materials to their proper places, and keep my workspace clean and organized. By applying these basic office administration skills, I can help daily office operations run smoothly, prevent errors, provide good support to employees and clients, and maintain a professional and productive workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-11-04 14:33:11', '2026-11-04 14:33:11', '2026-11-04 14:33:11'),
(36, 17, 2, 1, 'If I were assigned to complete a routine office task, such as organizing and filing important documents, I would first understand the instructions and identify what needs to be accomplished. Before starting, I would prepare the necessary office supplies, folders, labels, computer, and other materials to make sure everything is ready. I would begin by collecting all documents and checking each one to make sure the information is complete and correct. After that, I would sort the documents according to their types, dates, departments, and importance. I would separate urgent documents from regular files so I could prioritize tasks and avoid missing important deadlines. Next, I would label each folder clearly, arrange the documents in alphabetical or chronological order, and place them in their designated filing cabinets or storage areas for easy access and retrieval. I would also update the filing records or office database to keep track of new documents and make sure the information is properly recorded. While doing the task, I would follow the company\'s standard operating procedures, maintain confidentiality when handling sensitive information, and use office equipment properly. I would manage my time, avoid unnecessary delays, and use a checklist to monitor my progress. Before considering the task finished, I would review the documents and check if everything is complete, accurate, properly labeled, and filed in the correct location. Lastly, I would return all office supplies and equipment to their proper places, remove unnecessary papers from my desk, and keep my workspace clean and organized. By following these steps, I can complete the task efficiently, prevent misplaced documents, reduce errors, save time, and help maintain an orderly and productive office environment.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-11-04 14:40:59', '2026-11-04 14:40:59', '2026-11-04 14:40:59'),
(37, 17, 3, 1, 'For this task, I would choose the alphabetical filing system because it is simple, organized, and makes office records easier to locate and retrieve. First, I would gather all the documents and check their names, dates, reference numbers, and other important information. I would make sure that each document is complete and belongs to the correct category. Next, I would arrange the records alphabetically according to the client\'s last name, company name, or document title. I would separate different types of records into folders, label each folder clearly, and place them in the proper filing cabinets or designated storage areas. I would also create a file index or record log containing the document names, folder labels, and storage locations so that I can easily track and find specific records when needed. When someone requests a document, I would identify the correct file, check the index, locate the folder, and verify that I have retrieved the correct record before releasing it. I would also follow the office filing procedures, maintain confidentiality, and make sure that borrowed documents are properly recorded. After using the records, I would return them to their original locations to avoid misplaced or missing files. Lastly, I would regularly check and update the filing system to keep all office records accurate, accessible, secure, and organized.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-11-04 14:47:37', '2026-11-04 14:47:37', '2026-11-04 14:47:37'),
(38, 16, 2, 1, 'If I were assigned to complete a routine office task, such as organizing and filing important documents, I would first understand the instructions and identify what needs to be accomplished. Before starting, I would prepare the necessary office supplies, folders, labels, computer, and other materials to make sure everything is ready. I would begin by collecting all documents and checking each one to make sure the information is complete and correct. After that, I would sort the documents according to their types, dates, departments, and importance. I would separate urgent documents from regular files so I could prioritize tasks and avoid missing important deadlines. Next, I would label each folder clearly, arrange the documents in alphabetical or chronological order, and place them in their designated filing cabinets or storage areas for easy access and retrieval. I would also update the filing records or office database to keep track of new documents and make sure the information is properly recorded. While doing the task, I would follow the company\'s standard operating procedures, maintain confidentiality when handling sensitive information, and use office equipment properly. I would manage my time, avoid unnecessary delays, and use a checklist to monitor my progress. Before considering the task finished, I would review the documents and check if everything is complete, accurate, properly labeled, and filed in the correct location. Lastly, I would return all office supplies and equipment to their proper places, remove unnecessary papers from my desk, and keep my workspace clean and organized. By following these steps, I can complete the task efficiently, prevent misplaced documents, reduce errors, save time, and help maintain an orderly and productive office environment.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-11-04 23:12:00', '2026-11-04 23:12:00', '2026-11-04 23:12:00'),
(39, 16, 3, 1, 'For this task, I would choose the alphabetical filing system because it is simple, organized, and makes office records easier to locate and retrieve. First, I would gather all the documents and check their names, dates, reference numbers, and other important information. I would make sure that each document is complete and belongs to the correct category. Next, I would arrange the records alphabetically according to the client\'s last name, company name, or document title. I would separate different types of records into folders, label each folder clearly, and place them in the proper filing cabinets or designated storage areas. I would also create a file index or record log containing the document names, folder labels, and storage locations so that I can easily track and find specific records when needed. When someone requests a document, I would identify the correct file, check the index, locate the folder, and verify that I have retrieved the correct record before releasing it. I would also follow the office filing procedures, maintain confidentiality, and make sure that borrowed documents are properly recorded. After using the records, I would return them to their original locations to avoid misplaced or missing files. Lastly, I would regularly check and update the filing system to keep all office records accurate, accessible, secure, and organized.', 17.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 4 of 5 criteria matched. Score: 85/100. Result: PASSED.', '2026-11-04 23:15:05', '2026-11-04 23:15:05', '2026-11-04 23:15:05'),
(40, 16, 4, 1, 'If I were responsible for handling office records, I would make sure to follow proper procedures and handle every document carefully, professionally, and responsibly. First, I would check and identify the records to understand their purpose, importance, and level of confidentiality. I would organize documents according to their categories, dates, names, or reference numbers so they can be easily located when needed. I would use a proper filing system, label folders clearly, and store records in their designated cabinets or secure digital folders. I would also make sure that confidential information is protected and only shared with authorized employees who have permission to access it. I would never leave important documents unattended, share private information with unauthorized people, or remove records without proper approval. Before updating or releasing any document, I would verify the information to prevent mistakes, missing records, or unauthorized changes. I would also maintain accurate records by updating the filing system, recording borrowed and returned documents, and following the company\'s records management policies. When a document is no longer needed, I would follow the approved retention and disposal procedures instead of throwing it away without permission. I believe these actions are appropriate because office records contain important information that must be accurate, secure, organized, and accessible only to the right people. By practicing confidentiality, accuracy, accountability, and proper record management, I can help protect company information and maintain a professional and trustworthy workplace.', 20.00, 'graded', NULL, 'Your answer meets the minimum length and passed the keyword criteria. 5 of 5 criteria matched. Score: 100/100. Result: PASSED.', '2026-11-04 23:20:02', '2026-11-04 23:20:02', '2026-11-04 23:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(10) UNSIGNED NOT NULL,
  `course_title` varchar(200) NOT NULL,
  `course_description` text DEFAULT NULL,
  `course_number` tinyint(3) UNSIGNED NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_title`, `course_description`, `course_number`, `thumbnail`, `is_locked`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Office Administration Fundamentals', 'Learn the basic roles, procedures, and professional practices used in an office environment.', 1, NULL, 0, 'published', '2026-10-14 10:44:08', '2026-10-14 10:44:08'),
(2, 'Business Communication and Correspondence', 'Develop clear and professional communication skills for emails, letters, telephone calls, and workplace interactions.', 2, NULL, 1, 'published', '2026-10-14 10:44:08', '2026-10-14 10:44:08'),
(3, 'Office Productivity and Records Management', 'Practice document preparation, spreadsheet basics, organized records, and responsible data protection.', 3, NULL, 1, 'published', '2026-10-14 10:44:08', '2026-10-14 10:44:08'),
(4, 'sample', 'sample desc', 4, NULL, 0, 'archived', '2026-10-19 03:49:15', '2026-10-19 03:57:53'),
(5, 'sample 5', '', 5, NULL, 0, 'archived', '2026-10-21 12:03:05', '2026-10-21 12:05:45'),
(7, 'sample 6', '', 6, NULL, 0, 'archived', '2026-11-04 07:33:59', '2026-11-04 07:36:42');

-- --------------------------------------------------------

--
-- Table structure for table `final_assessments`
--

CREATE TABLE `final_assessments` (
  `final_assessment_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `assessment_title` varchar(200) NOT NULL,
  `assessment_description` text DEFAULT NULL,
  `total_points` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `passing_score` decimal(5,2) NOT NULL DEFAULT 70.00,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `final_assessments`
--

INSERT INTO `final_assessments` (`final_assessment_id`, `course_id`, `assessment_title`, `assessment_description`, `total_points`, `passing_score`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Course 1 Final Quest', 'Review the key concepts from Office Administration Fundamentals.', 100, 70.00, 'published', '2026-10-14 10:44:10', '2026-10-14 10:44:10'),
(2, 2, 'Course 2 Final Quest', 'Review the key concepts from Business Communication and Correspondence.', 100, 70.00, 'published', '2026-10-14 10:44:10', '2026-10-14 10:44:10'),
(3, 3, 'Course 3 Final Quest', 'Review the key concepts from Office Productivity and Records Management.', 100, 70.00, 'published', '2026-10-14 10:44:10', '2026-10-14 10:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `final_assessment_attempts`
--

CREATE TABLE `final_assessment_attempts` (
  `final_attempt_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `final_assessment_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `score` decimal(7,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','submitted','graded','passed','failed') NOT NULL DEFAULT 'in_progress',
  `answers_json` longtext DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `final_assessment_attempts`
--

INSERT INTO `final_assessment_attempts` (`final_attempt_id`, `user_id`, `final_assessment_id`, `attempt_number`, `score`, `status`, `answers_json`, `feedback`, `started_at`, `submitted_at`, `graded_at`) VALUES
(1, 5, 1, 1, 90.00, 'passed', '{\"1\":{\"selected_option_id\":2,\"selected_answer\":\"Verify the information, update the authorized record, and notify both departments\",\"is_correct\":true},\"2\":{\"selected_option_id\":6,\"selected_answer\":\"Send only part of it\",\"is_correct\":false},\"3\":{\"selected_option_id\":11,\"selected_answer\":\"Assess urgency, address time-critical needs, and communicate priorities\",\"is_correct\":true},\"4\":{\"selected_option_id\":14,\"selected_answer\":\"Contact the employee or an appropriate delegate and offer a suitable next step\",\"is_correct\":true},\"5\":{\"selected_option_id\":19,\"selected_answer\":\"Check the purchasing policy and obtain required authorization\",\"is_correct\":true},\"6\":{\"selected_option_id\":22,\"selected_answer\":\"Check priorities, contact organizers, and arrange an approved alternative\",\"is_correct\":true},\"7\":{\"selected_option_id\":27,\"selected_answer\":\"Decline and direct them to an authorized access method\",\"is_correct\":true},\"8\":{\"selected_option_id\":30,\"selected_answer\":\"Search approved storage, notify the responsible person, and follow recovery procedures\",\"is_correct\":true},\"9\":{\"selected_option_id\":34,\"selected_answer\":\"Record the discrepancy and notify the supplier or purchasing contact\",\"is_correct\":true},\"10\":{\"selected_option_id\":38,\"selected_answer\":\"Explain the conflict, clarify priorities, and agree on a plan\",\"is_correct\":true},\"11\":{\"selected_option_id\":43,\"selected_answer\":\"Follow emergency procedures and guide people to safety\",\"is_correct\":true},\"12\":{\"selected_option_id\":47,\"selected_answer\":\"Secure it and return it to an authorized person\",\"is_correct\":true},\"13\":{\"selected_option_id\":51,\"selected_answer\":\"Follow the organization\'s gift and conflict-of-interest policy\",\"is_correct\":true},\"14\":{\"selected_option_id\":54,\"selected_answer\":\"Clarify the dependency and deadline, then escalate appropriately if needed\",\"is_correct\":true},\"15\":{\"selected_option_id\":59,\"selected_answer\":\"Use the approved contact procedure without disclosing private information\",\"is_correct\":true},\"16\":{\"selected_option_id\":62,\"selected_answer\":\"Decisions, task owners, deadlines, and follow-up actions\",\"is_correct\":true},\"17\":{\"selected_option_id\":66,\"selected_answer\":\"Investigate the discrepancy and update records according to procedure\",\"is_correct\":true},\"18\":{\"selected_option_id\":71,\"selected_answer\":\"Promise compensation without authority\",\"is_correct\":false},\"19\":{\"selected_option_id\":74,\"selected_answer\":\"Confirm essential details and approval before booking\",\"is_correct\":true},\"20\":{\"selected_option_id\":79,\"selected_answer\":\"Identify affected records and follow the proper approval process\",\"is_correct\":true}}', 'Result: PASSED. You passed the Final Quest.', '2026-10-25 10:30:24', '2026-10-25 10:30:24', '2026-10-25 10:30:24'),
(2, 6, 1, 1, 25.00, 'failed', '{\"1\":{\"selected_option_id\":3,\"selected_answer\":\"Keep both versions active\",\"is_correct\":false},\"2\":{\"selected_option_id\":8,\"selected_answer\":\"Forward it to all managers\",\"is_correct\":false},\"3\":{\"selected_option_id\":9,\"selected_answer\":\"Do the easiest task first\",\"is_correct\":false},\"4\":{\"selected_option_id\":15,\"selected_answer\":\"Tell the visitor to return without checking\",\"is_correct\":false},\"5\":{\"selected_option_id\":18,\"selected_answer\":\"Split it into smaller requests to avoid approval\",\"is_correct\":false},\"6\":{\"selected_option_id\":23,\"selected_answer\":\"Let both groups share without asking\",\"is_correct\":false},\"7\":{\"selected_option_id\":28,\"selected_answer\":\"Change the password to the colleague\'s name\",\"is_correct\":false},\"8\":{\"selected_option_id\":32,\"selected_answer\":\"Ignore the missing document\",\"is_correct\":false},\"9\":{\"selected_option_id\":33,\"selected_answer\":\"Sign that everything arrived\",\"is_correct\":false},\"10\":{\"selected_option_id\":38,\"selected_answer\":\"Explain the conflict, clarify priorities, and agree on a plan\",\"is_correct\":true},\"11\":{\"selected_option_id\":43,\"selected_answer\":\"Follow emergency procedures and guide people to safety\",\"is_correct\":true},\"12\":{\"selected_option_id\":48,\"selected_answer\":\"Photograph it for reference\",\"is_correct\":false},\"13\":{\"selected_option_id\":49,\"selected_answer\":\"Accept it privately\",\"is_correct\":false},\"14\":{\"selected_option_id\":54,\"selected_answer\":\"Clarify the dependency and deadline, then escalate appropriately if needed\",\"is_correct\":true},\"15\":{\"selected_option_id\":58,\"selected_answer\":\"Share it if the visitor sounds important\",\"is_correct\":false},\"16\":{\"selected_option_id\":64,\"selected_answer\":\"Only the meeting start time\",\"is_correct\":false},\"17\":{\"selected_option_id\":67,\"selected_answer\":\"Order supplies immediately\",\"is_correct\":false},\"18\":{\"selected_option_id\":70,\"selected_answer\":\"Acknowledge the issue, check the facts, and explain corrective action\",\"is_correct\":true},\"19\":{\"selected_option_id\":76,\"selected_answer\":\"Book duplicate tickets\",\"is_correct\":false},\"20\":{\"selected_option_id\":79,\"selected_answer\":\"Identify affected records and follow the proper approval process\",\"is_correct\":true}}', 'Result: FAILED. Review the lessons and try again.', '2026-11-04 10:39:04', '2026-11-04 10:39:04', '2026-11-04 10:39:04'),
(3, 6, 1, 2, 100.00, 'passed', '{\"1\":{\"selected_option_id\":2,\"selected_answer\":\"Verify the information, update the authorized record, and notify both departments\",\"is_correct\":true},\"2\":{\"selected_option_id\":7,\"selected_answer\":\"Verify the sender\'s identity and authorization\",\"is_correct\":true},\"3\":{\"selected_option_id\":11,\"selected_answer\":\"Assess urgency, address time-critical needs, and communicate priorities\",\"is_correct\":true},\"4\":{\"selected_option_id\":14,\"selected_answer\":\"Contact the employee or an appropriate delegate and offer a suitable next step\",\"is_correct\":true},\"5\":{\"selected_option_id\":19,\"selected_answer\":\"Check the purchasing policy and obtain required authorization\",\"is_correct\":true},\"6\":{\"selected_option_id\":22,\"selected_answer\":\"Check priorities, contact organizers, and arrange an approved alternative\",\"is_correct\":true},\"7\":{\"selected_option_id\":27,\"selected_answer\":\"Decline and direct them to an authorized access method\",\"is_correct\":true},\"8\":{\"selected_option_id\":30,\"selected_answer\":\"Search approved storage, notify the responsible person, and follow recovery procedures\",\"is_correct\":true},\"9\":{\"selected_option_id\":34,\"selected_answer\":\"Record the discrepancy and notify the supplier or purchasing contact\",\"is_correct\":true},\"10\":{\"selected_option_id\":38,\"selected_answer\":\"Explain the conflict, clarify priorities, and agree on a plan\",\"is_correct\":true},\"11\":{\"selected_option_id\":43,\"selected_answer\":\"Follow emergency procedures and guide people to safety\",\"is_correct\":true},\"12\":{\"selected_option_id\":47,\"selected_answer\":\"Secure it and return it to an authorized person\",\"is_correct\":true},\"13\":{\"selected_option_id\":51,\"selected_answer\":\"Follow the organization\'s gift and conflict-of-interest policy\",\"is_correct\":true},\"14\":{\"selected_option_id\":54,\"selected_answer\":\"Clarify the dependency and deadline, then escalate appropriately if needed\",\"is_correct\":true},\"15\":{\"selected_option_id\":59,\"selected_answer\":\"Use the approved contact procedure without disclosing private information\",\"is_correct\":true},\"16\":{\"selected_option_id\":62,\"selected_answer\":\"Decisions, task owners, deadlines, and follow-up actions\",\"is_correct\":true},\"17\":{\"selected_option_id\":66,\"selected_answer\":\"Investigate the discrepancy and update records according to procedure\",\"is_correct\":true},\"18\":{\"selected_option_id\":70,\"selected_answer\":\"Acknowledge the issue, check the facts, and explain corrective action\",\"is_correct\":true},\"19\":{\"selected_option_id\":74,\"selected_answer\":\"Confirm essential details and approval before booking\",\"is_correct\":true},\"20\":{\"selected_option_id\":79,\"selected_answer\":\"Identify affected records and follow the proper approval process\",\"is_correct\":true}}', 'Result: PASSED. You passed the Final Quest.', '2026-11-04 10:40:33', '2026-11-04 10:40:33', '2026-11-04 10:40:33'),
(4, 16, 1, 1, 100.00, 'passed', '{\"1\":{\"selected_option_id\":2,\"selected_answer\":\"Verify the information, update the authorized record, and notify both departments\",\"is_correct\":true},\"2\":{\"selected_option_id\":7,\"selected_answer\":\"Verify the sender\'s identity and authorization\",\"is_correct\":true},\"3\":{\"selected_option_id\":11,\"selected_answer\":\"Assess urgency, address time-critical needs, and communicate priorities\",\"is_correct\":true},\"4\":{\"selected_option_id\":14,\"selected_answer\":\"Contact the employee or an appropriate delegate and offer a suitable next step\",\"is_correct\":true},\"5\":{\"selected_option_id\":19,\"selected_answer\":\"Check the purchasing policy and obtain required authorization\",\"is_correct\":true},\"6\":{\"selected_option_id\":22,\"selected_answer\":\"Check priorities, contact organizers, and arrange an approved alternative\",\"is_correct\":true},\"7\":{\"selected_option_id\":27,\"selected_answer\":\"Decline and direct them to an authorized access method\",\"is_correct\":true},\"8\":{\"selected_option_id\":30,\"selected_answer\":\"Search approved storage, notify the responsible person, and follow recovery procedures\",\"is_correct\":true},\"9\":{\"selected_option_id\":34,\"selected_answer\":\"Record the discrepancy and notify the supplier or purchasing contact\",\"is_correct\":true},\"10\":{\"selected_option_id\":38,\"selected_answer\":\"Explain the conflict, clarify priorities, and agree on a plan\",\"is_correct\":true},\"11\":{\"selected_option_id\":43,\"selected_answer\":\"Follow emergency procedures and guide people to safety\",\"is_correct\":true},\"12\":{\"selected_option_id\":47,\"selected_answer\":\"Secure it and return it to an authorized person\",\"is_correct\":true},\"13\":{\"selected_option_id\":51,\"selected_answer\":\"Follow the organization\'s gift and conflict-of-interest policy\",\"is_correct\":true},\"14\":{\"selected_option_id\":54,\"selected_answer\":\"Clarify the dependency and deadline, then escalate appropriately if needed\",\"is_correct\":true},\"15\":{\"selected_option_id\":59,\"selected_answer\":\"Use the approved contact procedure without disclosing private information\",\"is_correct\":true},\"16\":{\"selected_option_id\":62,\"selected_answer\":\"Decisions, task owners, deadlines, and follow-up actions\",\"is_correct\":true},\"17\":{\"selected_option_id\":66,\"selected_answer\":\"Investigate the discrepancy and update records according to procedure\",\"is_correct\":true},\"18\":{\"selected_option_id\":70,\"selected_answer\":\"Acknowledge the issue, check the facts, and explain corrective action\",\"is_correct\":true},\"19\":{\"selected_option_id\":74,\"selected_answer\":\"Confirm essential details and approval before booking\",\"is_correct\":true},\"20\":{\"selected_option_id\":79,\"selected_answer\":\"Identify affected records and follow the proper approval process\",\"is_correct\":true}}', 'Result: PASSED. You passed the Final Quest.', '2026-11-04 23:21:15', '2026-11-04 23:21:15', '2026-11-04 23:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `final_assessment_options`
--

CREATE TABLE `final_assessment_options` (
  `option_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `option_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_assessment_options`
--

INSERT INTO `final_assessment_options` (`option_id`, `question_id`, `option_text`, `option_order`) VALUES
(1, 1, 'Use whichever file looks newest', 1),
(2, 1, 'Verify the information, update the authorized record, and notify both departments', 2),
(3, 1, 'Keep both versions active', 3),
(4, 1, 'Delete both files', 4),
(5, 2, 'Send it because the request is urgent', 1),
(6, 2, 'Send only part of it', 2),
(7, 2, 'Verify the sender\'s identity and authorization', 3),
(8, 2, 'Forward it to all managers', 4),
(9, 3, 'Do the easiest task first', 1),
(10, 3, 'Ignore the meeting', 2),
(11, 3, 'Assess urgency, address time-critical needs, and communicate priorities', 3),
(12, 3, 'Complete tasks in the order received', 4),
(13, 4, 'Let the visitor enter alone', 1),
(14, 4, 'Contact the employee or an appropriate delegate and offer a suitable next step', 2),
(15, 4, 'Tell the visitor to return without checking', 3),
(16, 4, 'Share the employee\'s private schedule', 4),
(17, 5, 'Approve it to avoid delays', 1),
(18, 5, 'Split it into smaller requests to avoid approval', 2),
(19, 5, 'Check the purchasing policy and obtain required authorization', 3),
(20, 5, 'Pay for it using petty cash', 4),
(21, 6, 'Cancel both meetings', 1),
(22, 6, 'Check priorities, contact organizers, and arrange an approved alternative', 2),
(23, 6, 'Let both groups share without asking', 3),
(24, 6, 'Give the room to whoever arrives first', 4),
(25, 7, 'Share it temporarily', 1),
(26, 7, 'Write it on a note', 2),
(27, 7, 'Decline and direct them to an authorized access method', 3),
(28, 7, 'Change the password to the colleague\'s name', 4),
(29, 8, 'Create a replacement and claim it is original', 1),
(30, 8, 'Search approved storage, notify the responsible person, and follow recovery procedures', 2),
(31, 8, 'Blame the last employee who handled it', 3),
(32, 8, 'Ignore the missing document', 4),
(33, 9, 'Sign that everything arrived', 1),
(34, 9, 'Record the discrepancy and notify the supplier or purchasing contact', 2),
(35, 9, 'Pay the invoice without checking', 3),
(36, 9, 'Discard the receipt', 4),
(37, 10, 'Choose one silently', 1),
(38, 10, 'Explain the conflict, clarify priorities, and agree on a plan', 2),
(39, 10, 'Claim both are complete', 3),
(40, 10, 'Give both to an unauthorized employee', 4),
(41, 11, 'Finish checking visitors in', 1),
(42, 11, 'Collect office files', 2),
(43, 11, 'Follow emergency procedures and guide people to safety', 3),
(44, 11, 'Wait for a manager\'s email', 4),
(45, 12, 'Leave it for the owner', 1),
(46, 12, 'Read it to identify the owner', 2),
(47, 12, 'Secure it and return it to an authorized person', 3),
(48, 12, 'Photograph it for reference', 4),
(49, 13, 'Accept it privately', 1),
(50, 13, 'Accept it if the supplier is friendly', 2),
(51, 13, 'Follow the organization\'s gift and conflict-of-interest policy', 3),
(52, 13, 'Ask for a larger gift', 4),
(53, 14, 'Publicly criticize the coworker', 1),
(54, 14, 'Clarify the dependency and deadline, then escalate appropriately if needed', 2),
(55, 14, 'Change the deadline without informing anyone', 3),
(56, 14, 'Stop doing your own tasks', 4),
(57, 15, 'Provide it from the staff directory', 1),
(58, 15, 'Share it if the visitor sounds important', 2),
(59, 15, 'Use the approved contact procedure without disclosing private information', 3),
(60, 15, 'Ask another visitor for it', 4),
(61, 16, 'Only attendee names', 1),
(62, 16, 'Decisions, task owners, deadlines, and follow-up actions', 2),
(63, 16, 'Personal opinions about attendees', 3),
(64, 16, 'Only the meeting start time', 4),
(65, 17, 'Change the record without checking', 1),
(66, 17, 'Investigate the discrepancy and update records according to procedure', 2),
(67, 17, 'Order supplies immediately', 3),
(68, 17, 'Delete the inventory history', 4),
(69, 18, 'Argue that it was not your fault', 1),
(70, 18, 'Acknowledge the issue, check the facts, and explain corrective action', 2),
(71, 18, 'Promise compensation without authority', 3),
(72, 18, 'Ignore the complaint', 4),
(73, 19, 'Book non-refundable travel immediately', 1),
(74, 19, 'Confirm essential details and approval before booking', 2),
(75, 19, 'Guess the location', 3),
(76, 19, 'Book duplicate tickets', 4),
(77, 20, 'Sign on behalf of the approvers', 1),
(78, 20, 'Hide the records', 2),
(79, 20, 'Identify affected records and follow the proper approval process', 3),
(80, 20, 'Delete the records', 4),
(81, 21, 'Promise delivery tomorrow without confirmation', 1),
(82, 21, 'Acknowledge the issue, explain the investigation, and provide a realistic update time', 2),
(83, 21, 'Tell the customer to contact the courier', 3),
(84, 21, 'Blame the warehouse', 4),
(85, 22, 'Assign it without asking', 1),
(86, 22, 'Leave it unresolved', 2),
(87, 22, 'Confirm responsibility and circulate the owner and deadline', 3),
(88, 22, 'List every department as equally responsible', 4),
(89, 23, 'Check only the subject line', 1),
(90, 23, 'Verify the recipient, authorization, attachment, and approved sharing method', 2),
(91, 23, 'Copy all staff', 3),
(92, 23, 'Send it to a personal email account', 4),
(93, 24, 'Guess what is needed', 1),
(94, 24, 'Ignore the message', 2),
(95, 24, 'Clarify the expected outcome, priority, and deadline', 3),
(96, 24, 'Send an unrelated report', 4),
(97, 25, 'Support the louder coworker', 1),
(98, 25, 'Restore respectful discussion, focus on facts, and agree on actions', 2),
(99, 25, 'End the meeting without notes', 3),
(100, 25, 'Share the argument publicly', 4),
(101, 26, 'Send it because most of it is correct', 1),
(102, 26, 'Correct and verify the document before sending', 2),
(103, 26, 'Ask the recipient to ignore the number', 3),
(104, 26, 'Delete the reference number from all records', 4),
(105, 27, 'Reply angrily', 1),
(106, 27, 'Forward it to everyone', 2),
(107, 27, 'Ask for clarification calmly and address the issue professionally', 3),
(108, 27, 'Post the message publicly', 4),
(109, 28, 'Send it unchanged', 1),
(110, 28, 'Provide the purpose, agenda, time, location, and preparation requirements', 2),
(111, 28, 'Invite more people without explanation', 3),
(112, 28, 'Cancel all future meetings', 4),
(113, 29, 'Approve it anyway', 1),
(114, 29, 'Reject it without checking policy', 2),
(115, 29, 'Explain the next step and refer it to an authorized person', 3),
(116, 29, 'Ignore the customer', 4),
(117, 30, 'Tell one employee and assume everyone will hear', 1),
(118, 30, 'Use an approved channel accessible to all affected employees', 2),
(119, 30, 'Post it only in a private chat', 3),
(120, 30, 'Wait until someone asks', 4),
(121, 31, 'Hope no one notices', 1),
(122, 31, 'Send a corrected version and notify recipients about the correction', 2),
(123, 31, 'Delete the sent email and do nothing', 3),
(124, 31, 'Blame the spreadsheet', 4),
(125, 32, 'Agree immediately', 1),
(126, 32, 'Make a vague promise', 2),
(127, 32, 'Check feasibility with the responsible team before confirming', 3),
(128, 32, 'Tell the client they are unreasonable', 4),
(129, 33, 'Add more technical terms', 1),
(130, 33, 'Use plain language and explain necessary terms clearly', 2),
(131, 33, 'Make the message longer without changing it', 3),
(132, 33, 'Remove the main request', 4),
(133, 34, 'Let the confusion continue', 1),
(134, 34, 'Manage speaking turns and summarize decisions and action items', 2),
(135, 34, 'End the call immediately', 3),
(136, 34, 'Disable everyone\'s audio permanently', 4),
(137, 35, 'Insist your memory is correct', 1),
(138, 35, 'Check the documented email trail and respond with verified facts', 2),
(139, 35, 'Change the date without explanation', 3),
(140, 35, 'Delete the correspondence', 4),
(141, 36, 'Say the idea is bad', 1),
(142, 36, 'Say no without explanation', 2),
(143, 36, 'Explain the concern respectfully and suggest an alternative where possible', 3),
(144, 36, 'Ignore the proposal indefinitely', 4),
(145, 37, 'Hide the request in a long paragraph', 1),
(146, 37, 'State the action owner, requested task, and deadline clearly', 2),
(147, 37, 'Use only emojis', 3),
(148, 37, 'Copy every employee', 4),
(149, 38, 'Record that a decision was made', 1),
(150, 38, 'Record the unresolved issue and the next action or review date', 2),
(151, 38, 'Remove the topic completely', 3),
(152, 38, 'Include a personal guess', 4),
(153, 39, 'Forward it quickly', 1),
(154, 39, 'Add your own speculation', 2),
(155, 39, 'Avoid spreading it and refer them to official communications', 3),
(156, 39, 'Post it anonymously', 4),
(157, 40, 'Assume silence means understanding', 1),
(158, 40, 'Ask recipients to confirm requirements or summarize next steps', 2),
(159, 40, 'Repeat the same message without changes', 3),
(160, 40, 'Remove the deadline', 4),
(161, 41, 'Use the newest timestamp', 1),
(162, 41, 'Establish standard naming, controlled storage, and approval history', 2),
(163, 41, 'Let employees choose their own versions', 3),
(164, 41, 'Print all copies without labels', 4),
(165, 42, 'Calculate totals first', 1),
(166, 42, 'Validate the data and investigate duplicates and missing values before calculating', 2),
(167, 42, 'Delete every row with a blank cell', 3),
(168, 42, 'Change formulas to match last month', 4),
(169, 43, 'Ignore it if there is no reply', 1),
(170, 43, 'Report the incident promptly according to security procedures', 2),
(171, 43, 'Delete the sent message and assume the data is gone', 3),
(172, 43, 'Send the file to more people to explain', 4),
(173, 44, 'Check whether the formula range includes the new data', 1),
(174, 44, 'Change the worksheet color', 2),
(175, 44, 'Shorten the file name', 3),
(176, 44, 'Rename the worksheet tab', 4),
(177, 45, 'Create a replacement and call it original', 1),
(178, 45, 'Use the approved index or search process and document any issue', 2),
(179, 45, 'Remove the record from the inventory', 3),
(180, 45, 'Ask the auditor to ignore it', 4),
(181, 46, 'Require more USB drives', 1),
(182, 46, 'Use an approved central repository with access controls and backups', 2),
(183, 46, 'Allow files to be stored anywhere', 3),
(184, 46, 'Email reports to all employees', 4),
(185, 47, 'Submit it because the spreadsheet calculated it', 1),
(186, 47, 'Trace the source data, formulas, and assumptions, then verify the correction', 2),
(187, 47, 'Adjust the number to look reasonable', 3),
(188, 47, 'Hide the total cell', 4),
(189, 48, 'Delete all records immediately', 1),
(190, 48, 'Follow the retention schedule and check for applicable legal holds', 2),
(191, 48, 'Keep every record forever', 3),
(192, 48, 'Move them to a personal account', 4),
(193, 49, 'Make random copies', 1),
(194, 49, 'Use controlled permissions and version history', 2),
(195, 49, 'Remove all file names', 3),
(196, 49, 'Let only one person know its location', 4),
(197, 50, 'Open it immediately', 1),
(198, 50, 'Forward it to coworkers', 2),
(199, 50, 'Verify the sender independently and follow security policy', 3),
(200, 50, 'Enable macros to open it', 4),
(201, 51, 'It changes the worksheet color', 1),
(202, 51, 'Mixed data types can interfere with sorting and calculations', 2),
(203, 51, 'It makes the computer physically slower', 3),
(204, 51, 'It prevents saving', 4),
(205, 52, 'Send uncontrolled copies to each department', 1),
(206, 52, 'Use a controlled workflow with version identification and recorded approvals', 2),
(207, 52, 'Allow approvals by rumor', 3),
(208, 52, 'Remove the version date', 4),
(209, 53, 'Grant full access for convenience', 1),
(210, 53, 'Provide access appropriate to the employee\'s role and task', 2),
(211, 53, 'Send the export to a personal email', 3),
(212, 53, 'Share the administrator account', 4),
(213, 54, 'Skip validation', 1),
(214, 54, 'Automate repeatable steps while retaining validation checks', 2),
(215, 54, 'Remove source references', 3),
(216, 54, 'Reuse old figures without checking', 4),
(217, 55, 'Assume metadata is always correct', 1),
(218, 55, 'Investigate the discrepancy and document verified information', 2),
(219, 55, 'Change both dates randomly', 3),
(220, 55, 'Delete the record', 4),
(221, 56, 'Add more optional formats', 1),
(222, 56, 'Introduce a standard template with required fields and validation', 2),
(223, 56, 'Use free-form notes only', 3),
(224, 56, 'Create unrelated spreadsheets for every employee', 4),
(225, 57, 'The folder name might look unattractive', 1),
(226, 57, 'The backup may be unusable when recovery is needed', 2),
(227, 57, 'Files will automatically become public', 3),
(228, 57, 'The backup will always restore faster', 4),
(229, 58, 'The screen becomes less colorful', 1),
(230, 58, 'It reduces traceability and makes changes difficult to verify', 2),
(231, 58, 'It always increases file size', 3),
(232, 58, 'It prevents shutdown', 4),
(233, 59, 'Leave everything undocumented', 1),
(234, 59, 'Identify calculated fields, restrict inappropriate edits, and validate totals', 2),
(235, 59, 'Delete every formula', 3),
(236, 59, 'Hide all data rows', 4),
(237, 60, 'Destroy all records according to the usual schedule', 1),
(238, 60, 'Pause disposal of affected records and follow the hold procedure', 2),
(239, 60, 'Move them to a home computer', 3),
(240, 60, 'Delete the index but keep the paper', 4);

-- --------------------------------------------------------

--
-- Table structure for table `final_assessment_questions`
--

CREATE TABLE `final_assessment_questions` (
  `question_id` int(10) UNSIGNED NOT NULL,
  `final_assessment_id` int(10) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `points` smallint(5) UNSIGNED NOT NULL DEFAULT 5,
  `question_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `correct_answer` varchar(500) NOT NULL,
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_assessment_questions`
--

INSERT INTO `final_assessment_questions` (`question_id`, `final_assessment_id`, `question_text`, `points`, `question_order`, `correct_answer`, `explanation`) VALUES
(1, 1, 'Two departments have different versions of a client record. What should you do?', 5, 1, 'Verify the information, update the authorized record, and notify both departments', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(2, 1, 'A manager requests a confidential employee file from an unfamiliar email address.', 5, 2, 'Verify the sender\'s identity and authorization', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(3, 1, 'A meeting starts in ten minutes, an urgent report has an error, and routine filing is due tomorrow.', 5, 3, 'Assess urgency, address time-critical needs, and communicate priorities', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(4, 1, 'A visitor arrives, but the employee they want to see is unavailable.', 5, 4, 'Contact the employee or an appropriate delegate and offer a suitable next step', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(5, 1, 'A purchase request exceeds the department\'s approved budget.', 5, 5, 'Check the purchasing policy and obtain required authorization', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(6, 1, 'Two meetings are booked in the same room at the same time.', 5, 6, 'Check priorities, contact organizers, and arrange an approved alternative', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(7, 1, 'A colleague asks for your password to finish a task.', 5, 7, 'Decline and direct them to an authorized access method', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(8, 1, 'An important document is missing before a deadline.', 5, 8, 'Search approved storage, notify the responsible person, and follow recovery procedures', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(9, 1, 'A delivery contains fewer items than the purchase order specifies.', 5, 9, 'Record the discrepancy and notify the supplier or purchasing contact', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(10, 1, 'Two urgent tasks cannot both be completed on time.', 5, 10, 'Explain the conflict, clarify priorities, and agree on a plan', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(11, 1, 'A fire alarm sounds while visitors are waiting in reception.', 5, 11, 'Follow emergency procedures and guide people to safety', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(12, 1, 'Confidential paperwork is left on a shared printer.', 5, 12, 'Secure it and return it to an authorized person', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(13, 1, 'A supplier offers you an expensive gift while a contract decision is pending.', 5, 13, 'Follow the organization\'s gift and conflict-of-interest policy', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(14, 1, 'A coworker repeatedly misses deadlines that affect your work.', 5, 14, 'Clarify the dependency and deadline, then escalate appropriately if needed', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(15, 1, 'A visitor asks for an employee\'s personal phone number.', 5, 15, 'Use the approved contact procedure without disclosing private information', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(16, 1, 'What should meeting notes record after tasks are assigned?', 5, 16, 'Decisions, task owners, deadlines, and follow-up actions', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(17, 1, 'The office supply count is lower than the inventory record.', 5, 17, 'Investigate the discrepancy and update records according to procedure', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(18, 1, 'A client complains that an important document arrived late.', 5, 18, 'Acknowledge the issue, check the facts, and explain corrective action', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(19, 1, 'Travel must be booked, but the meeting location is unconfirmed.', 5, 19, 'Confirm essential details and approval before booking', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(20, 1, 'An audit finds records without required approval signatures.', 5, 20, 'Identify affected records and follow the proper approval process', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(21, 2, 'A customer is angry about a second delivery delay, but the cause is unknown.', 5, 1, 'Acknowledge the issue, explain the investigation, and provide a realistic update time', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(22, 2, 'Meeting notes do not identify which department owns an important report.', 5, 2, 'Confirm responsibility and circulate the owner and deadline', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(23, 2, 'You need to email a confidential report to an external partner.', 5, 3, 'Verify the recipient, authorization, attachment, and approved sharing method', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(24, 2, 'A manager says, \'Handle the client issue ASAP,\' without further details.', 5, 4, 'Clarify the expected outcome, priority, and deadline', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(25, 2, 'Two coworkers argue during a meeting and interrupt the discussion.', 5, 5, 'Restore respectful discussion, focus on facts, and agree on actions', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(26, 2, 'A formal letter contains the wrong reference number just before sending.', 5, 6, 'Correct and verify the document before sending', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(27, 2, 'A colleague sends a message that sounds insulting, but the meaning is unclear.', 5, 7, 'Ask for clarification calmly and address the issue professionally', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(28, 2, 'A meeting invitation lacks an agenda and preparation details.', 5, 8, 'Provide the purpose, agenda, time, location, and preparation requirements', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(29, 2, 'A customer requests a refund outside your authority.', 5, 9, 'Explain the next step and refer it to an authorized person', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(30, 2, 'An announcement must reach employees across different shifts.', 5, 10, 'Use an approved channel accessible to all affected employees', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(31, 2, 'A report you sent contains an incorrect figure.', 5, 11, 'Send a corrected version and notify recipients about the correction', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(32, 2, 'A client requests a commitment your team has not approved.', 5, 12, 'Check feasibility with the responsible team before confirming', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(33, 2, 'A customer does not understand technical terms in your message.', 5, 13, 'Use plain language and explain necessary terms clearly', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(34, 2, 'Several participants talk at once during a virtual meeting.', 5, 14, 'Manage speaking turns and summarize decisions and action items', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(35, 2, 'A supplier disputes the date in your purchase correspondence.', 5, 15, 'Check the documented email trail and respond with verified facts', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(36, 2, 'You must reject a coworker\'s proposal professionally.', 5, 16, 'Explain the concern respectfully and suggest an alternative where possible', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(37, 2, 'Only one person needs to act on a message sent to several recipients.', 5, 17, 'State the action owner, requested task, and deadline clearly', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(38, 2, 'A decision was postponed during a meeting. What should the minutes say?', 5, 18, 'Record the unresolved issue and the next action or review date', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(39, 2, 'An employee asks you to forward an unverified rumor about layoffs.', 5, 19, 'Avoid spreading it and refer them to official communications', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(40, 2, 'You sent complex instructions and want to reduce mistakes.', 5, 20, 'Ask recipients to confirm requirements or summarize next steps', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(41, 3, 'Different report versions exist in different folders, and an audit cannot identify the approved copy.', 5, 1, 'Establish standard naming, controlled storage, and approval history', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(42, 3, 'A spreadsheet has duplicates, inconsistent dates, and missing values.', 5, 2, 'Validate the data and investigate duplicates and missing values before calculating', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(43, 3, 'A spreadsheet containing personal information is sent to an unauthorized recipient.', 5, 3, 'Report the incident promptly according to security procedures', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(44, 3, 'A spreadsheet formula gives the wrong total after new rows are added.', 5, 4, 'Check whether the formula range includes the new data', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(45, 3, 'An auditor requests a record that has been moved from its usual folder.', 5, 5, 'Use the approved index or search process and document any issue', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(46, 3, 'Staff store final reports on personal USB drives.', 5, 6, 'Use an approved central repository with access controls and backups', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(47, 3, 'A report total differs greatly from its source data.', 5, 7, 'Trace the source data, formulas, and assumptions, then verify the correction', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(48, 3, 'Records have reached their retention period, but some may relate to legal proceedings.', 5, 8, 'Follow the retention schedule and check for applicable legal holds', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(49, 3, 'A shared spreadsheet is frequently overwritten.', 5, 9, 'Use controlled permissions and version history', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(50, 3, 'An urgent invoice arrives as a suspicious email attachment.', 5, 10, 'Verify the sender independently and follow security policy', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(51, 3, 'A quantity column contains values such as \'ten\', \'12\', and \'8\'.', 5, 11, 'Mixed data types can interfere with sorting and calculations', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(52, 3, 'Three departments must approve a document. How can outdated drafts be avoided?', 5, 12, 'Use a controlled workflow with version identification and recorded approvals', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(53, 3, 'An employee requests a complete customer database but only needs a few records.', 5, 13, 'Provide access appropriate to the employee\'s role and task', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(54, 3, 'A monthly report must be prepared faster without reducing accuracy.', 5, 14, 'Automate repeatable steps while retaining validation checks', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(55, 3, 'A record\'s metadata date differs from the date written in the document.', 5, 15, 'Investigate the discrepancy and document verified information', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(56, 3, 'The same form arrives in several formats, causing inconsistent data entry.', 5, 16, 'Introduce a standard template with required fields and validation', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(57, 3, 'A backup reports success, but restoration has never been tested.', 5, 17, 'The backup may be unusable when recovery is needed', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(58, 3, 'An employee changes a record without documenting the change.', 5, 18, 'It reduces traceability and makes changes difficult to verify', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(59, 3, 'A spreadsheet mixes formulas with manually typed totals.', 5, 19, 'Identify calculated fields, restrict inappropriate edits, and validate totals', 'Review the workplace situation and choose the action that follows proper office procedures.'),
(60, 3, 'Old records may relate to an ongoing investigation.', 5, 20, 'Pause disposal of affected records and follow the hold procedure', 'Review the workplace situation and choose the action that follows proper office procedures.');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `lesson_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `lesson_title` varchar(200) NOT NULL,
  `lesson_content` longtext NOT NULL,
  `estimated_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT 10,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`lesson_id`, `module_id`, `lesson_number`, `lesson_title`, `lesson_content`, `estimated_minutes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Introduction to Office Administration', '<h2>What is Office Administration?</h2><p>Office administration involves coordinating information, people, documents, and routine tasks so an organization can operate smoothly.</p><h3>Common responsibilities</h3><ul><li>Organizing files and records</li><li>Handling calls, messages, and appointments</li><li>Preparing documents and reports</li><li>Supporting staff and visitors</li><li>Protecting confidential information</li></ul><p>Good office administration depends on organization, communication, accuracy, reliability, and professionalism.</p><h3>Remember</h3><p>Office staff help the workplace stay organized and make sure information reaches the right people at the right time.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(2, 2, 1, 'Fundamentals of Business Communication', '<h2>Business Communication</h2><p>Business communication is the exchange of information in a workplace or professional setting. It can happen through speaking, writing, phone calls, meetings, and digital tools.</p><h3>Principles of effective communication</h3><ul><li><strong>Clarity:</strong> use words that are easy to understand.</li><li><strong>Completeness:</strong> include the details the reader needs.</li><li><strong>Accuracy:</strong> check facts, names, dates, and figures.</li><li><strong>Courtesy:</strong> use respectful language.</li><li><strong>Conciseness:</strong> avoid unnecessary information.</li></ul><p>Choose a communication method that fits the message, urgency, and audience.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(3, 3, 1, 'Introduction to Office Productivity', '<h2>Office Productivity</h2><p>Office productivity means completing useful work accurately and efficiently. Productivity improves when tasks are planned, tools are used properly, and distractions are managed.</p><h3>Helpful practices</h3><ul><li>Set priorities and deadlines.</li><li>Break large tasks into smaller steps.</li><li>Use templates for repeated documents.</li><li>Keep digital and physical workspaces organized.</li><li>Review work before submitting it.</li></ul><p>Productivity is not only about working faster. It also means maintaining quality and avoiding preventable errors.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(4, 4, 1, 'Office Procedures and Workplace Organization', '<h2>Office Procedures</h2><p>Office procedures are the agreed steps employees follow to complete routine work consistently. Examples include receiving visitors, routing documents, scheduling meetings, and handling incoming mail.</p><h3>Keeping the workplace organized</h3><ul><li>Return supplies and equipment to their assigned places.</li><li>Keep walkways and shared areas clear.</li><li>Use checklists for recurring tasks.</li><li>Follow the organization\'s document and safety rules.</li></ul><p>Consistent procedures reduce confusion, improve service, and help coworkers coordinate their work.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(5, 5, 1, 'Professional Emails and Business Letters', '<h2>Professional Messages</h2><p>Professional emails and business letters communicate information in a respectful and organized way.</p><h3>Basic email structure</h3><ol><li>Use a clear subject line.</li><li>Start with an appropriate greeting.</li><li>State the purpose early.</li><li>Provide relevant details or action items.</li><li>Close politely and include your name.</li></ol><p>Before sending, check the recipient, attachments, spelling, tone, and confidential information. Use a business letter when a more formal record or format is required.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(6, 6, 1, 'Document Preparation and Formatting', '<h2>Preparing Office Documents</h2><p>A well-prepared document is readable, consistent, accurate, and suitable for its audience.</p><h3>Formatting guidelines</h3><ul><li>Use clear headings to organize sections.</li><li>Choose readable fonts and sizes.</li><li>Keep spacing and alignment consistent.</li><li>Use lists for steps or related items.</li><li>Check page breaks, margins, and numbering.</li></ul><p>Proofread the content and confirm that the final file uses the required name and format before sharing it.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(7, 7, 1, 'Filing Systems and Document Organization', '<h2>Filing Systems</h2><p>A filing system helps staff store, locate, use, and maintain documents. A good system uses consistent labels and clear rules.</p><h3>Common methods</h3><ul><li><strong>Alphabetical:</strong> organized by names or titles.</li><li><strong>Numerical:</strong> organized by assigned numbers.</li><li><strong>Chronological:</strong> organized by date.</li><li><strong>Subject-based:</strong> organized by topic or category.</li></ul><p>Use the method that fits the records and the way users will search for them. Apply naming conventions consistently and restrict access to sensitive files.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(8, 8, 1, 'Telephone and Workplace Communication', '<h2>Telephone Etiquette</h2><p>Professional telephone communication creates a positive impression and helps information reach the correct person.</p><h3>Good practices</h3><ul><li>Answer promptly and identify yourself or the organization.</li><li>Speak clearly and maintain a polite tone.</li><li>Listen carefully and take accurate notes.</li><li>Ask permission before placing a caller on hold.</li><li>Confirm names, numbers, and next steps.</li></ul><p>For messages, record the caller\'s name, contact details, reason for calling, date and time, and any requested action.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(9, 9, 1, 'Spreadsheet and Data Entry Fundamentals', '<h2>Spreadsheets and Data Entry</h2><p>A spreadsheet organizes information into rows and columns. A cell is the intersection of a row and a column.</p><h3>Basic concepts</h3><ul><li>Rows usually contain individual records.</li><li>Columns contain fields such as date, name, or amount.</li><li>Labels explain what each column represents.</li><li>Formulas calculate values from cell data.</li></ul><p>Accurate data entry requires checking source information, using consistent formats, avoiding duplicate records, and reviewing totals. A simple spreadsheet formula may begin with an equals sign, such as <code>=SUM(B2:B6)</code>.</p>', 12, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(10, 10, 1, 'Office Records and Professional Practice', '<h2>Office Records</h2><p>Office records provide evidence of activities, decisions, transactions, and responsibilities. Records must be handled according to workplace rules.</p><h3>Professional practice</h3><ul><li>Record information accurately.</li><li>Share information only with authorized people.</li><li>Follow retention and disposal procedures.</li><li>Keep documents complete and easy to retrieve.</li><li>Report errors or missing records promptly.</li></ul><p>Professional conduct includes reliability, respect, confidentiality, accountability, and following established policies.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(11, 11, 1, 'Writing Clear Business Correspondence', '<h2>Clear Business Writing</h2><p>Business correspondence includes emails, letters, memos, notices, and other workplace messages. Good correspondence helps readers understand what happened and what they need to do.</p><h3>Make a message effective</h3><ul><li>Know the audience and purpose.</li><li>Put the main point near the beginning.</li><li>Use short paragraphs and familiar words.</li><li>Separate facts, requests, and deadlines.</li><li>End with a clear next step when needed.</li></ul><p>Review the message for tone, accuracy, grammar, and completeness before sending it.</p>', 10, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(12, 12, 1, 'Records Management and Data Protection', '<h2>Records Management and Data Protection</h2><p>Records management covers the creation, organization, storage, retrieval, retention, and authorized disposal of records.</p><h3>Protecting information</h3><ul><li>Use strong passwords and do not share them.</li><li>Grant access only to people who need it.</li><li>Lock devices when leaving a workstation.</li><li>Use approved storage and backup locations.</li><li>Dispose of records securely when authorized.</li><li>Report suspected data exposure promptly.</li></ul><p>Personal and confidential information should be handled carefully and only for legitimate work purposes.</p>', 12, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `missions`
--

CREATE TABLE `missions` (
  `mission_id` int(10) UNSIGNED NOT NULL,
  `module_id` int(10) UNSIGNED NOT NULL,
  `mission_number` tinyint(3) UNSIGNED NOT NULL,
  `mission_title` varchar(200) NOT NULL,
  `mission_description` text DEFAULT NULL,
  `mission_type` varchar(50) NOT NULL DEFAULT 'lesson',
  `mission_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `missions`
--

INSERT INTO `missions` (`mission_id`, `module_id`, `mission_number`, `mission_title`, `mission_description`, `mission_type`, `mission_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Intro to Office Administration', 'Identify the purpose of office administration and the responsibilities of office staff.', 'lesson', 1, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(2, 4, 2, 'Office Procedures and Workplace Organization', 'Apply basic office procedures and workplace organization practices.', 'lesson', 2, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(3, 7, 3, 'Filing Systems and Document Organization', 'Choose an appropriate way to organize and retrieve office documents.', 'lesson', 3, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(4, 10, 4, 'Office Records and Professional Practice', 'Recognize responsible record handling and professional behavior.', 'lesson', 4, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(5, 2, 1, 'Fundamentals of Business Communication', 'Identify the key parts of effective business communication.', 'lesson', 1, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(6, 5, 2, 'Professional Emails and Business Letters', 'Choose appropriate formats and language for professional messages.', 'lesson', 2, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(7, 8, 3, 'Telephone and Workplace Communication', 'Apply proper telephone etiquette and active listening.', 'lesson', 3, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(8, 11, 4, 'Writing Clear Business Correspondence', 'Improve clarity, tone, and organization in business writing.', 'lesson', 4, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(9, 3, 1, 'Introduction to Office Productivity', 'Recognize tools and habits that improve office productivity.', 'lesson', 1, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(10, 6, 2, 'Document Preparation and Formatting', 'Apply consistent formatting to a basic office document.', 'lesson', 2, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(11, 9, 3, 'Spreadsheet and Data Entry Fundamentals', 'Enter data accurately and recognize basic spreadsheet formulas.', 'lesson', 3, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(12, 12, 4, 'Records Management and Data Protection', 'Apply basic records management and data protection practices.', 'lesson', 4, 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `module_number` tinyint(3) UNSIGNED NOT NULL,
  `module_title` varchar(200) NOT NULL,
  `module_description` text DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `course_id`, `module_number`, `module_title`, `module_description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Introduction to Office Administration', 'Understand office administration and the responsibilities of office personnel.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(2, 2, 1, 'Fundamentals of Business Communication', 'Learn the purpose and principles of clear workplace communication.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(3, 3, 1, 'Introduction to Office Productivity', 'Explore tools and habits that support efficient office work.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(4, 1, 2, 'Office Procedures and Workplace Organization', 'Learn routine office procedures and ways to keep a workplace organized.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(5, 2, 2, 'Professional Emails and Business Letters', 'Practice writing professional emails and business letters.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(6, 3, 2, 'Document Preparation and Formatting', 'Learn how to prepare readable, consistent office documents.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(7, 1, 3, 'Filing Systems and Document Organization', 'Explore practical filing systems and document organization methods.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(8, 2, 3, 'Telephone and Workplace Communication', 'Use proper telephone etiquette and effective workplace communication.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(9, 3, 3, 'Spreadsheet and Data Entry Fundamentals', 'Understand spreadsheet structure, accurate data entry, and basic formulas.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(10, 1, 4, 'Office Records and Professional Practice', 'Understand record handling, confidentiality, and professional conduct.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(11, 2, 4, 'Writing Clear Business Correspondence', 'Organize messages and choose suitable language for business correspondence.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09'),
(12, 3, 4, 'Records Management and Data Protection', 'Learn records lifecycle basics and ways to protect information.', 'published', '2026-10-14 10:44:09', '2026-10-14 10:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(50) NOT NULL DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `audience` varchar(100) DEFAULT 'All students'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `notification_type`, `is_read`, `created_at`, `audience`) VALUES
(1, 2, 'Welcome to EduQuest!', 'Start your first course and complete your first lesson to begin your learning journey.', 'announcement', 1, '2026-10-14 10:44:10', 'All students'),
(2, 6, 'New admin teacher', 'Hi guys', 'announcement', 1, '2026-10-16 11:06:19', 'All students'),
(3, 6, 'test', 'test', 'announcement', 0, '2026-10-30 08:52:08', 'All students'),
(4, 6, 'sample', 'hi guys', 'announcement', 0, '2026-11-04 08:27:18', 'All students');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`reset_id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 5, '7f794b61ceecf22adc24433dd1a86d0340b57c918279f83745e23cdb7a1fb00d', '2026-11-05 10:49:34', NULL, '2026-11-05 02:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `account_status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `email`, `password_hash`, `role`, `account_status`, `profile_image`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'EduQuest Administrator', 'admin', 'admin@eduquest.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin', 'active', NULL, '2026-10-14 10:44:08', '2026-10-14 10:44:08', NULL),
(2, 'Sample Student One', 'student1', 'student1@eduquest.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'student', 'active', NULL, '2026-10-14 10:44:08', '2026-10-14 10:44:08', NULL),
(3, 'Sample Student Two', 'student2', 'student2@eduquest.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'student', '', NULL, '2026-10-14 10:44:08', '2026-10-29 05:19:01', NULL),
(4, 'Sample Student Three', 'student3', 'student3@eduquest.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'student', 'active', NULL, '2026-10-14 10:44:08', '2026-10-14 10:44:08', NULL),
(5, 'Ivan Rolf Geringer', 'ivan', 'ivanrolfg@gmail.com', '$2y$10$qGVaUl7e1bfhjJw39ku3V.nUhT6borWYKYfMAbbm9C0e6i/yHFQna', 'student', 'active', 'uploads/profile_pics/profile_5_fe10b49d1fef6a75.jpg', '2026-10-14 10:46:02', '2026-11-05 02:30:50', NULL),
(6, 'Jessa Galang', 'JessaAdmin', 'adminjessa06@gmail.com', '$2y$10$eOSKlMg2d.HCPLVKRDHb3O2o4cly/ZHWrj9JlUIepPsSOoc3A970m', 'admin', 'active', 'uploads/profile_images/admin_6_0a34e715b9fd0011.jpg', '2026-10-14 12:22:00', '2026-11-02 13:41:27', '2026-11-02 21:41:27'),
(7, 'rose ann', 'roseann', 'roseann@gmail.com', '$2y$10$XUNEn46k6zO/yOp5xlMoue9rPJm5cYw2KA.TzGahESaqZXY7mW/hC', 'student', '', NULL, '2026-10-15 12:37:35', '2026-11-05 02:32:50', NULL),
(8, 'Jessa Galang', 'Jessa', 'jessadellova06@gmail.com', '$2y$10$js6vYHFrvppmW5VP4jr8cuZkPnPcqqxJJfyZ.36rzBMIbxQ7c7tU.', 'student', 'active', 'uploads/profile_pics/profile_8_9e52768435a93644.jpg', '2026-10-16 09:42:33', '2026-11-02 12:35:28', NULL),
(9, 'nala', 'nalanal', 'nala@gmail.com', '$2y$10$zfGKGjmWiR.PMHDx3JKBBeTXJgPk3n5cBjWrBoOs1UZrGhADPHbve', 'student', 'active', NULL, '2026-10-16 10:17:26', '2026-10-16 10:17:26', NULL),
(10, 'Ivan Geringer', 'admin2', 'admin2rolf@gmail.com', '$2y$10$ciCbsuj9NSavvffA3E9Q8eonj63uwyJ9HwJdrAlIiGqbaNykkrlTC', 'admin', 'active', NULL, '2026-10-19 03:47:04', '2026-10-19 03:51:54', '2026-10-19 11:48:36'),
(11, 'Rhea Eliza Melencion', 'RheaAdmin', 'rcmelencion06@gmail.com', '$2y$10$xTE/p/ZbvQp9Gz2bCD6.GeGBmxpo88zf1rHkNLCHmTQcoaJwuwOai', 'student', 'active', NULL, '2026-10-21 10:29:32', '2026-11-03 07:54:00', NULL),
(12, 'Paulyn Mae Ramos', 'PaulynAdmin', 'paulynmae@gmail.com', '$2y$10$pHp9d8Gpcgzlm2D1Hzc3BOsdE3uw0sHnCTbRvuRhYjEW3fGLsCWcO', 'admin', 'active', NULL, '2026-10-21 10:49:33', '2026-10-21 10:50:20', NULL),
(13, 'Christine Joy', 'TinAdmin', 'christinetribiana@gmail.com', '$2y$10$7smIwTTxPxaPYMXeYpc9AuU7CZ2v.ytHgrtUrrbriUJ/ElLjW24oy', 'admin', 'inactive', NULL, '2026-10-21 11:44:06', '2026-11-02 13:06:15', NULL),
(14, 'Inday Galang', 'inday', 'inday@gmail.com', '$2y$10$hRXxhK13ocRmKZyzMO8noeqkt.zzK.eXzGcnjoqwhbVDWpTqN0TIe', 'student', 'active', 'uploads/profile_pics/profile_14_9ab33db4d7362857.jpg', '2026-10-29 03:41:21', '2026-11-02 14:00:48', NULL),
(15, 'chu che chum', 'chuchechum', 'chuchechum@gmail.com', '$2y$10$sSPyZnuDpGnGGJSLEZu.i.5589FWnF.e5HMS.AMN7aTmbYwgTa8su', 'student', 'active', NULL, '2026-11-03 07:58:03', '2026-11-03 07:58:03', NULL),
(16, 'Jane Galang', 'Jane Galang', 'Janegalang@gmail.com', '$2y$10$v7pfA4NlmFn/rduaw3YjmOLGCf.ivQn/YbUGRLvrGLtLFTIvTI3re', 'student', 'active', 'uploads/profile_pics/profile_16_1117d54af4aceaac.jpg', '2026-11-04 05:49:47', '2026-11-04 07:15:32', NULL),
(17, 'Jessa Dellova Galang', 'Jess', 'jessagalang@gmail.com', '$2y$10$//.peC8ofDkZq7QtizZZ8.ATVO1Y0.VU1MCkOfwJ4bvYCc9FxWHiu', 'student', 'active', NULL, '2026-11-04 06:31:59', '2026-11-04 06:31:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_achievements`
--

CREATE TABLE `user_achievements` (
  `user_achievement_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `achievement_id` int(10) UNSIGNED NOT NULL,
  `earned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_achievements`
--

INSERT INTO `user_achievements` (`user_achievement_id`, `user_id`, `achievement_id`, `earned_at`) VALUES
(1, 5, 2, '2026-10-15 21:45:11'),
(2, 5, 1, '2026-10-15 21:45:11'),
(3, 5, 4, '2026-10-15 21:45:11'),
(4, 8, 2, '2026-10-16 18:15:04'),
(5, 8, 1, '2026-10-16 18:15:04'),
(6, 8, 4, '2026-10-16 18:15:04'),
(7, 6, 2, '2026-10-25 10:23:37'),
(8, 6, 1, '2026-10-25 10:23:38'),
(9, 14, 2, '2026-10-29 12:11:38'),
(10, 14, 1, '2026-10-29 12:11:38'),
(11, 14, 4, '2026-10-29 12:11:38'),
(12, 6, 4, '2026-11-03 11:58:32'),
(13, 16, 2, '2026-11-04 14:24:24'),
(14, 16, 1, '2026-11-04 14:24:24'),
(15, 17, 2, '2026-11-04 14:33:13'),
(16, 17, 1, '2026-11-04 14:33:13'),
(17, 16, 7, '2026-11-04 23:15:07'),
(18, 16, 4, '2026-11-04 23:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_course_progress`
--

CREATE TABLE `user_course_progress` (
  `course_progress_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('locked','not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_course_progress`
--

INSERT INTO `user_course_progress` (`course_progress_id`, `user_id`, `course_id`, `progress_percent`, `status`, `started_at`, `completed_at`, `updated_at`) VALUES
(1, 5, 1, 100.00, 'completed', '2026-10-15 14:29:49', '2026-10-15 21:03:26', '2026-10-15 13:03:26'),
(8, 5, 2, 25.00, 'in_progress', '2026-10-15 21:06:11', NULL, '2026-10-15 13:06:11'),
(16, 8, 2, 25.00, 'in_progress', '2026-10-16 18:15:02', NULL, '2026-10-16 10:15:02'),
(28, 6, 1, 100.00, 'completed', '2026-10-25 10:23:35', '2026-11-04 10:37:57', '2026-11-04 02:37:57'),
(32, 14, 1, 25.00, 'in_progress', '2026-10-29 12:11:36', NULL, '2026-10-29 04:11:36'),
(73, 16, 1, 100.00, 'completed', '2026-11-04 14:24:20', '2026-11-04 23:20:02', '2026-11-04 15:20:02'),
(76, 17, 1, 75.00, 'in_progress', '2026-11-04 14:33:11', NULL, '2026-11-04 06:47:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_lesson_progress`
--

CREATE TABLE `user_lesson_progress` (
  `lesson_progress_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `status` enum('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_lesson_progress`
--

INSERT INTO `user_lesson_progress` (`lesson_progress_id`, `user_id`, `lesson_id`, `status`, `started_at`, `completed_at`, `updated_at`) VALUES
(1, 5, 1, 'completed', '2026-10-14 18:52:20', '2026-10-14 18:52:24', '2026-10-30 06:22:42'),
(2, 5, 10, 'completed', '2026-10-14 18:54:12', '2026-10-14 19:23:47', '2026-10-15 12:40:14'),
(3, 5, 4, 'completed', '2026-10-14 19:23:33', '2026-10-14 19:23:36', '2026-10-29 04:32:59'),
(4, 5, 7, 'completed', '2026-10-14 19:23:39', '2026-10-14 19:23:42', '2026-10-29 04:32:09'),
(5, 5, 2, 'completed', '2026-10-14 19:23:56', '2026-10-14 19:23:58', '2026-10-29 04:19:01'),
(6, 5, 5, 'completed', '2026-10-14 19:24:01', '2026-10-14 19:24:04', '2026-10-29 04:19:07'),
(7, 5, 8, 'completed', '2026-10-14 19:24:07', '2026-10-14 19:24:15', '2026-10-14 11:24:15'),
(8, 5, 11, 'completed', '2026-10-14 19:24:09', '2026-10-14 19:24:20', '2026-10-16 10:44:47'),
(9, 7, 1, 'completed', '2026-10-15 20:37:38', '2026-10-16 17:10:36', '2026-10-16 09:10:36'),
(10, 5, 3, 'completed', '2026-10-16 17:04:26', '2026-10-16 17:04:29', '2026-10-16 10:45:01'),
(11, 7, 4, 'in_progress', '2026-10-16 17:10:38', NULL, '2026-10-16 09:10:46'),
(12, 8, 1, 'completed', '2026-10-16 17:42:40', '2026-10-16 18:03:45', '2026-11-04 08:10:32'),
(13, 8, 4, 'completed', '2026-10-16 17:43:20', '2026-10-16 18:03:48', '2026-11-04 08:09:46'),
(14, 8, 7, 'completed', '2026-10-16 18:03:49', '2026-10-16 18:03:51', '2026-11-04 08:09:49'),
(15, 8, 10, 'completed', '2026-10-16 18:03:52', '2026-10-16 18:03:54', '2026-11-04 08:09:50'),
(16, 8, 2, 'completed', '2026-10-16 18:03:57', '2026-10-16 18:04:01', '2026-10-26 08:14:23'),
(17, 8, 5, 'in_progress', '2026-10-16 18:04:27', NULL, '2026-10-26 08:14:14'),
(18, 8, 8, 'in_progress', '2026-10-16 18:04:45', NULL, '2026-10-16 10:04:50'),
(19, 8, 11, 'in_progress', '2026-10-16 18:04:54', NULL, '2026-10-16 10:05:02'),
(20, 9, 1, 'completed', '2026-10-16 18:17:30', '2026-10-16 18:17:33', '2026-10-16 10:17:55'),
(21, 6, 1, 'completed', '2026-10-25 10:18:04', '2026-10-25 10:21:51', '2026-11-04 14:38:59'),
(22, 14, 1, 'completed', '2026-10-29 11:42:28', '2026-10-29 11:46:35', '2026-10-29 04:16:11'),
(23, 14, 7, 'completed', '2026-10-29 11:49:05', '2026-10-29 12:04:35', '2026-10-29 04:04:35'),
(24, 14, 4, 'completed', '2026-10-29 11:51:35', '2026-10-29 11:51:38', '2026-10-29 04:18:24'),
(25, 14, 10, 'completed', '2026-10-29 12:02:58', '2026-10-29 12:04:41', '2026-10-29 04:04:41'),
(26, 14, 2, 'completed', '2026-10-29 12:04:49', '2026-10-29 12:04:52', '2026-10-29 04:07:52'),
(27, 14, 5, 'in_progress', '2026-10-29 12:08:00', NULL, '2026-10-29 04:08:00'),
(28, 6, 4, 'completed', '2026-11-03 10:49:58', '2026-11-03 11:03:46', '2026-11-03 07:49:45'),
(29, 6, 7, 'completed', '2026-11-03 11:21:10', '2026-11-03 11:54:07', '2026-11-03 13:37:14'),
(30, 6, 10, 'completed', '2026-11-03 11:21:14', '2026-11-03 11:56:43', '2026-11-04 14:38:46'),
(31, 15, 1, 'completed', '2026-11-03 15:58:07', '2026-11-03 16:00:01', '2026-11-03 09:08:02'),
(32, 15, 4, 'in_progress', '2026-11-03 17:09:01', NULL, '2026-11-03 09:09:01'),
(33, 6, 2, 'completed', '2026-11-04 10:13:29', '2026-11-04 10:13:31', '2026-11-04 14:36:27'),
(34, 16, 1, 'completed', '2026-11-04 14:01:06', '2026-11-04 14:09:55', '2026-11-04 06:57:18'),
(35, 16, 4, 'completed', '2026-11-04 14:25:11', '2026-11-04 23:06:58', '2026-11-04 15:06:58'),
(36, 16, 7, 'completed', '2026-11-04 14:25:14', '2026-11-04 23:12:13', '2026-11-04 15:12:13'),
(37, 16, 10, 'completed', '2026-11-04 14:25:16', '2026-11-04 14:25:19', '2026-11-04 15:15:19'),
(38, 17, 1, 'completed', '2026-11-04 14:32:08', '2026-11-04 14:32:11', '2026-11-04 06:32:11'),
(39, 17, 4, 'completed', '2026-11-04 14:33:17', '2026-11-04 14:33:22', '2026-11-04 06:33:22'),
(40, 17, 7, 'completed', '2026-11-04 14:41:10', '2026-11-04 14:41:13', '2026-11-04 06:41:13'),
(42, 6, 5, 'in_progress', '2026-11-04 22:36:29', NULL, '2026-11-04 14:36:29');

-- --------------------------------------------------------

--
-- Table structure for table `user_mission_progress`
--

CREATE TABLE `user_mission_progress` (
  `mission_progress_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mission_id` int(10) UNSIGNED NOT NULL,
  `status` enum('locked','not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  `progress_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_mission_progress`
--

INSERT INTO `user_mission_progress` (`mission_progress_id`, `user_id`, `mission_id`, `status`, `progress_percent`, `started_at`, `completed_at`, `updated_at`) VALUES
(1, 5, 4, 'completed', 100.00, '2026-10-15 11:56:10', '2026-10-15 14:29:49', '2026-10-15 06:29:49'),
(31, 5, 1, 'completed', 100.00, '2026-10-15 15:02:37', '2026-10-15 15:02:37', '2026-10-15 07:02:37'),
(54, 5, 2, 'completed', 100.00, '2026-10-15 20:59:34', '2026-10-15 21:00:03', '2026-10-15 13:00:03'),
(61, 5, 3, 'completed', 100.00, '2026-10-15 21:03:10', '2026-10-15 21:03:26', '2026-10-15 13:03:26'),
(68, 5, 5, 'completed', 100.00, '2026-10-15 21:05:52', '2026-10-15 21:06:11', '2026-10-15 13:06:11'),
(73, 5, 6, 'not_started', 0.00, NULL, NULL, '2026-10-15 13:06:13'),
(86, 7, 1, 'in_progress', 50.00, '2026-10-16 17:13:13', NULL, '2026-10-16 09:13:13'),
(92, 8, 5, 'completed', 100.00, '2026-10-16 18:11:34', '2026-10-16 18:15:02', '2026-10-16 10:15:02'),
(107, 8, 6, 'not_started', 0.00, NULL, NULL, '2026-10-16 10:15:04'),
(118, 8, 1, 'in_progress', 50.00, '2026-10-16 19:02:12', NULL, '2026-10-16 11:02:12'),
(138, 6, 1, 'completed', 100.00, '2026-10-25 10:22:58', '2026-10-25 10:23:35', '2026-10-25 02:23:35'),
(143, 6, 2, 'completed', 100.00, '2026-11-03 11:20:36', '2026-11-03 12:03:07', '2026-11-03 04:03:07'),
(155, 14, 1, 'completed', 100.00, '2026-10-29 11:46:40', '2026-10-29 12:11:36', '2026-10-29 04:11:36'),
(164, 14, 2, 'not_started', 0.00, NULL, NULL, '2026-10-29 04:11:38'),
(207, 6, 3, 'completed', 100.00, '2026-11-03 21:58:39', '2026-11-03 22:01:54', '2026-11-03 14:01:54'),
(263, 15, 1, 'in_progress', 50.00, '2026-11-03 17:06:57', NULL, '2026-11-03 09:06:57'),
(289, 6, 4, 'completed', 100.00, '2026-11-04 10:36:20', '2026-11-04 10:37:57', '2026-11-04 02:37:57'),
(293, 6, 5, 'in_progress', 50.00, '2026-11-04 10:22:53', NULL, '2026-11-04 02:22:53'),
(308, 16, 1, 'completed', 100.00, '2026-11-04 14:18:42', '2026-11-04 14:24:20', '2026-11-04 06:24:20'),
(314, 16, 2, 'completed', 100.00, '2026-11-04 23:10:51', '2026-11-04 23:12:00', '2026-11-04 15:12:00'),
(318, 17, 1, 'completed', 100.00, '2026-11-04 14:33:04', '2026-11-04 14:33:11', '2026-11-04 06:33:11'),
(323, 17, 2, 'completed', 100.00, '2026-11-04 14:40:16', '2026-11-04 14:40:59', '2026-11-04 06:40:59'),
(329, 17, 3, 'completed', 100.00, '2026-11-04 14:46:50', '2026-11-04 14:47:37', '2026-11-04 06:47:37'),
(335, 17, 4, 'not_started', 0.00, NULL, NULL, '2026-11-04 06:47:39'),
(348, 16, 3, 'completed', 100.00, '2026-11-04 23:14:45', '2026-11-04 23:15:05', '2026-11-04 15:15:05'),
(354, 16, 4, 'completed', 100.00, '2026-11-04 23:19:11', '2026-11-04 23:20:02', '2026-11-04 15:20:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `fk_activities_mission` (`mission_id`);

--
-- Indexes for table `activity_answers`
--
ALTER TABLE `activity_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `uq_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `fk_activity_answers_question` (`question_id`),
  ADD KEY `fk_activity_answers_option` (`selected_option_id`);

--
-- Indexes for table `activity_attempts`
--
ALTER TABLE `activity_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD UNIQUE KEY `uq_user_activity_attempt` (`user_id`,`activity_id`,`attempt_number`),
  ADD KEY `fk_activity_attempts_activity` (`activity_id`);

--
-- Indexes for table `activity_options`
--
ALTER TABLE `activity_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `idx_activity_options_order` (`question_id`,`option_order`);

--
-- Indexes for table `activity_questions`
--
ALTER TABLE `activity_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_activity_question_order` (`activity_id`,`question_order`);

--
-- Indexes for table `admin_preferences`
--
ALTER TABLE `admin_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`challenge_id`),
  ADD KEY `fk_challenges_mission` (`mission_id`);

--
-- Indexes for table `challenge_attempts`
--
ALTER TABLE `challenge_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD UNIQUE KEY `uq_user_challenge_attempt` (`user_id`,`challenge_id`,`attempt_number`),
  ADD KEY `fk_challenge_attempt_challenge` (`challenge_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_number` (`course_number`);

--
-- Indexes for table `final_assessments`
--
ALTER TABLE `final_assessments`
  ADD PRIMARY KEY (`final_assessment_id`),
  ADD UNIQUE KEY `uq_course_final_assessment` (`course_id`);

--
-- Indexes for table `final_assessment_attempts`
--
ALTER TABLE `final_assessment_attempts`
  ADD PRIMARY KEY (`final_attempt_id`),
  ADD UNIQUE KEY `uq_user_final_attempt` (`user_id`,`final_assessment_id`,`attempt_number`),
  ADD KEY `fk_faa_assessment` (`final_assessment_id`);

--
-- Indexes for table `final_assessment_options`
--
ALTER TABLE `final_assessment_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `fk_fao_question` (`question_id`);

--
-- Indexes for table `final_assessment_questions`
--
ALTER TABLE `final_assessment_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `fk_faq_assessment` (`final_assessment_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD UNIQUE KEY `uq_module_lesson_number` (`module_id`,`lesson_number`);

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`mission_id`),
  ADD UNIQUE KEY `uq_module_mission_number` (`module_id`,`mission_number`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `uq_course_module_number` (`course_id`,`module_number`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`user_achievement_id`),
  ADD UNIQUE KEY `uq_user_achievement` (`user_id`,`achievement_id`),
  ADD KEY `fk_ua_achievement` (`achievement_id`);

--
-- Indexes for table `user_course_progress`
--
ALTER TABLE `user_course_progress`
  ADD PRIMARY KEY (`course_progress_id`),
  ADD UNIQUE KEY `uq_user_course` (`user_id`,`course_id`),
  ADD KEY `fk_ucp_course` (`course_id`);

--
-- Indexes for table `user_lesson_progress`
--
ALTER TABLE `user_lesson_progress`
  ADD PRIMARY KEY (`lesson_progress_id`),
  ADD UNIQUE KEY `uq_user_lesson` (`user_id`,`lesson_id`),
  ADD KEY `fk_ulp_lesson` (`lesson_id`);

--
-- Indexes for table `user_mission_progress`
--
ALTER TABLE `user_mission_progress`
  ADD PRIMARY KEY (`mission_progress_id`),
  ADD UNIQUE KEY `uq_user_mission` (`user_id`,`mission_id`),
  ADD KEY `fk_ump_mission` (`mission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `achievement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `activity_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `activity_answers`
--
ALTER TABLE `activity_answers`
  MODIFY `answer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=375;

--
-- AUTO_INCREMENT for table `activity_attempts`
--
ALTER TABLE `activity_attempts`
  MODIFY `attempt_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `activity_options`
--
ALTER TABLE `activity_options`
  MODIFY `option_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=465;

--
-- AUTO_INCREMENT for table `activity_questions`
--
ALTER TABLE `activity_questions`
  MODIFY `question_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `admin_preferences`
--
ALTER TABLE `admin_preferences`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `challenge_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `challenge_attempts`
--
ALTER TABLE `challenge_attempts`
  MODIFY `attempt_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `final_assessments`
--
ALTER TABLE `final_assessments`
  MODIFY `final_assessment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `final_assessment_attempts`
--
ALTER TABLE `final_assessment_attempts`
  MODIFY `final_attempt_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `final_assessment_options`
--
ALTER TABLE `final_assessment_options`
  MODIFY `option_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `final_assessment_questions`
--
ALTER TABLE `final_assessment_questions`
  MODIFY `question_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `lesson_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `mission_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `user_achievement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_course_progress`
--
ALTER TABLE `user_course_progress`
  MODIFY `course_progress_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `user_lesson_progress`
--
ALTER TABLE `user_lesson_progress`
  MODIFY `lesson_progress_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `user_mission_progress`
--
ALTER TABLE `user_mission_progress`
  MODIFY `mission_progress_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=360;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `fk_activities_mission` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`mission_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_answers`
--
ALTER TABLE `activity_answers`
  ADD CONSTRAINT `fk_activity_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `activity_attempts` (`attempt_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_activity_answers_option` FOREIGN KEY (`selected_option_id`) REFERENCES `activity_options` (`option_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_answers_question` FOREIGN KEY (`question_id`) REFERENCES `activity_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_attempts`
--
ALTER TABLE `activity_attempts`
  ADD CONSTRAINT `fk_activity_attempts_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_activity_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_options`
--
ALTER TABLE `activity_options`
  ADD CONSTRAINT `fk_activity_options_question` FOREIGN KEY (`question_id`) REFERENCES `activity_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_questions`
--
ALTER TABLE `activity_questions`
  ADD CONSTRAINT `fk_activity_questions_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_preferences`
--
ALTER TABLE `admin_preferences`
  ADD CONSTRAINT `fk_admin_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `challenges`
--
ALTER TABLE `challenges`
  ADD CONSTRAINT `fk_challenges_mission` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`mission_id`) ON DELETE CASCADE;

--
-- Constraints for table `challenge_attempts`
--
ALTER TABLE `challenge_attempts`
  ADD CONSTRAINT `fk_challenge_attempt_challenge` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_challenge_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `final_assessments`
--
ALTER TABLE `final_assessments`
  ADD CONSTRAINT `fk_final_assessment_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `final_assessment_attempts`
--
ALTER TABLE `final_assessment_attempts`
  ADD CONSTRAINT `fk_faa_assessment` FOREIGN KEY (`final_assessment_id`) REFERENCES `final_assessments` (`final_assessment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_faa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `final_assessment_options`
--
ALTER TABLE `final_assessment_options`
  ADD CONSTRAINT `fk_fao_question` FOREIGN KEY (`question_id`) REFERENCES `final_assessment_questions` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `final_assessment_questions`
--
ALTER TABLE `final_assessment_questions`
  ADD CONSTRAINT `fk_faq_assessment` FOREIGN KEY (`final_assessment_id`) REFERENCES `final_assessments` (`final_assessment_id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `fk_lessons_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE;

--
-- Constraints for table `missions`
--
ALTER TABLE `missions`
  ADD CONSTRAINT `fk_missions_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `fk_modules_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `fk_ua_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`achievement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_course_progress`
--
ALTER TABLE `user_course_progress`
  ADD CONSTRAINT `fk_ucp_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ucp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_lesson_progress`
--
ALTER TABLE `user_lesson_progress`
  ADD CONSTRAINT `fk_ulp_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ulp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_mission_progress`
--
ALTER TABLE `user_mission_progress`
  ADD CONSTRAINT `fk_ump_mission` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`mission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ump_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
