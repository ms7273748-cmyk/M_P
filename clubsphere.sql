-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 09, 2025 at 07:57 PM
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
-- Database: `clubsphere`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateAnnouncement` (IN `p_club_id` INT, IN `p_title` VARCHAR(200), IN `p_content` TEXT, IN `p_type` VARCHAR(20), IN `p_priority` VARCHAR(10), IN `p_created_by` INT, IN `p_target_audience` VARCHAR(20), OUT `p_announcement_id` INT)   BEGIN
    INSERT INTO announcements (
        club_id, announcement_title, announcement_content, 
        announcement_type, priority, created_by, target_audience,
        status, published_at
    ) VALUES (
        p_club_id, p_title, p_content, p_type, p_priority, 
        p_created_by, p_target_audience, 'Published', NOW()
    );
    
    SET p_announcement_id = LAST_INSERT_ID();
    
    -- Log the activity
    INSERT INTO activity_logs (user_id, action_type, action_description, entity_type, entity_id)
    VALUES (p_created_by, 'ANNOUNCEMENT_CREATED', 'Created new announcement', 'Announcement', p_announcement_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `RegisterForEvent` (IN `p_user_id` INT, IN `p_event_id` INT, OUT `p_result` VARCHAR(100))   BEGIN
    DECLARE v_current_participants INT;
    DECLARE v_max_participants INT;
    DECLARE v_registration_deadline DATETIME;
    DECLARE v_event_status VARCHAR(20);
    
    -- Get event details
    SELECT current_participants, max_participants, registration_deadline, status
    INTO v_current_participants, v_max_participants, v_registration_deadline, v_event_status
    FROM events 
    WHERE event_id = p_event_id;
    
    -- Check if event exists
    IF v_event_status IS NULL THEN
        SET p_result = 'Event not found';
    -- Check if registration is still open
    ELSEIF NOW() > v_registration_deadline THEN
        SET p_result = 'Registration deadline has passed';
    -- Check if event is published
    ELSEIF v_event_status != 'Published' THEN
        SET p_result = 'Event is not available for registration';
    -- Check if event is full
    ELSEIF v_current_participants >= v_max_participants THEN
        SET p_result = 'Event is full';
    -- Check if user is already registered
    ELSEIF EXISTS(SELECT 1 FROM event_registrations WHERE event_id = p_event_id AND user_id = p_user_id) THEN
        SET p_result = 'Already registered for this event';
    ELSE
        -- Register the user
        INSERT INTO event_registrations (event_id, user_id) VALUES (p_event_id, p_user_id);
        
        -- Update event participant count
        UPDATE events SET current_participants = current_participants + 1 WHERE event_id = p_event_id;
        
        -- Log the activity
        INSERT INTO activity_logs (user_id, action_type, action_description, entity_type, entity_id)
        VALUES (p_user_id, 'EVENT_REGISTRATION', 'Registered for event', 'Event', p_event_id);
        
        SET p_result = 'Registration successful';
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `achievement_title` varchar(200) NOT NULL,
  `achievement_description` text DEFAULT NULL,
  `achievement_type` enum('Competition','Award','Recognition','Milestone','Publication','Patent') DEFAULT 'Competition',
  `achievement_date` date NOT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `participants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`participants`)),
  `certificate_url` varchar(255) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `news_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_club_members`
-- (See below for the actual view)
--
CREATE TABLE `active_club_members` (
`membership_id` int(11)
,`user_id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`email` varchar(100)
,`profile_image` varchar(255)
,`club_id` int(11)
,`club_name` varchar(100)
,`club_code` varchar(10)
,`position` enum('President','VicePresident','Secretary','Treasurer','EventCoordinator','SocialMediaHead','Member')
,`joined_date` date
,`total_contribution_points` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_description` text DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action_type`, `action_description`, `entity_type`, `entity_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'USER_STATUS_CHANGED', 'User 1 status -> Inactive', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-07 13:40:08'),
(2, 2, 'USER_STATUS_CHANGED', 'User 1 status -> Active', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-07 13:40:10'),
(3, 2, 'USER_STATUS_CHANGED', 'User 7 status -> Inactive', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-08 05:42:44'),
(4, 2, 'USER_STATUS_CHANGED', 'User 7 status -> Active', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-08 05:42:49'),
(5, 1, 'LOGIN_FAILED', 'Invalid password', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 17:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `announcement_title` varchar(200) NOT NULL,
  `announcement_content` text NOT NULL,
  `announcement_type` enum('General','Event','Meeting','Achievement','Opportunity','Urgent') DEFAULT 'General',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `target_audience` enum('All','Members','Officers','Specific') DEFAULT 'All',
  `target_clubs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_clubs`)),
  `attachment_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `status` enum('Draft','Published','Archived') DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `club_id`, `announcement_title`, `announcement_content`, `announcement_type`, `priority`, `target_audience`, `target_clubs`, `attachment_url`, `image_url`, `is_featured`, `view_count`, `status`, `created_by`, `published_at`, `expires_at`, `created_at`, `updated_at`, `message`) VALUES
(1, 1, 'ACM Announcement 1', 'ACM Student Chapter shared update 1 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 35, '2025-10-29 10:00:00', '2025-11-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(2, 1, 'ACM Announcement 2', 'ACM Student Chapter shared update 2 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 42, '2025-10-19 10:00:00', '2025-11-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(3, 1, 'ACM Announcement 3', 'ACM Student Chapter shared update 3 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 23, '2025-10-09 10:00:00', '2025-11-08 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(4, 1, 'ACM Announcement 4', 'ACM Student Chapter shared update 4 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 9, '2025-09-29 10:00:00', '2025-10-29 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(5, 1, 'ACM Announcement 5', 'ACM Student Chapter shared update 5 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 21, '2025-09-19 10:00:00', '2025-10-19 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(6, 1, 'ACM Announcement 6', 'ACM Student Chapter shared update 6 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 33, '2025-11-18 10:00:00', '2025-12-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(7, 1, 'ACM Announcement 7', 'ACM Student Chapter shared update 7 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 8, '2025-11-28 10:00:00', '2025-12-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(8, 1, 'ACM Announcement 8', 'ACM Student Chapter shared update 8 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 40, '2025-12-08 10:00:00', '2026-01-07 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(9, 1, 'ACM Announcement 9', 'ACM Student Chapter shared update 9 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 44, '2025-12-18 10:00:00', '2026-01-17 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(10, 1, 'ACM Announcement 10', 'ACM Student Chapter shared update 10 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 22, '2025-12-28 10:00:00', '2026-01-27 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(11, 2, 'ACES Announcement 1', 'ACES Association shared update 1 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 52, '2025-10-29 04:30:00', '2025-11-28 04:30:00', '2025-11-07 14:12:57', '2025-11-07 14:12:57', ''),
(12, 2, 'ACES Announcement 2', 'ACES Association shared update 2 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 30, '2025-10-19 10:00:00', '2025-11-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(13, 2, 'ACES Announcement 3', 'ACES Association shared update 3 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 31, '2025-10-09 10:00:00', '2025-11-08 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(14, 2, 'ACES Announcement 4', 'ACES Association shared update 4 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 17, '2025-09-29 10:00:00', '2025-10-29 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(15, 2, 'ACES Announcement 5', 'ACES Association shared update 5 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 45, '2025-09-19 10:00:00', '2025-10-19 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(16, 2, 'ACES Announcement 6', 'ACES Association shared update 6 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 3, '2025-11-18 10:00:00', '2025-12-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(17, 2, 'ACES Announcement 7', 'ACES Association shared update 7 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 11, '2025-11-28 10:00:00', '2025-12-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(18, 2, 'ACES Announcement 8', 'ACES Association shared update 8 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 6, '2025-12-08 10:00:00', '2026-01-07 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(19, 2, 'ACES Announcement 9', 'ACES Association shared update 9 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 32, '2025-12-18 10:00:00', '2026-01-17 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(20, 2, 'ACES Announcement 10', 'ACES Association shared update 10 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 30, '2025-12-28 10:00:00', '2026-01-27 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(21, 3, 'CESA Announcement 1', 'CESA Chapter shared update 1 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 42, '2025-10-29 10:00:00', '2025-11-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(22, 3, 'CESA Announcement 2', 'CESA Chapter shared update 2 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 34, '2025-10-19 10:00:00', '2025-11-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(23, 3, 'CESA Announcement 3', 'CESA Chapter shared update 3 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 33, '2025-10-09 10:00:00', '2025-11-08 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(24, 3, 'CESA Announcement 4', 'CESA Chapter shared update 4 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 29, '2025-09-29 10:00:00', '2025-10-29 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(25, 3, 'CESA Announcement 5', 'CESA Chapter shared update 5 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 36, '2025-09-19 10:00:00', '2025-10-19 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(26, 3, 'CESA Announcement 6', 'CESA Chapter shared update 6 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 11, '2025-11-18 10:00:00', '2025-12-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(27, 3, 'CESA Announcement 7', 'CESA Chapter shared update 7 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 17, '2025-11-28 10:00:00', '2025-12-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(28, 3, 'CESA Announcement 8', 'CESA Chapter shared update 8 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 16, '2025-12-08 10:00:00', '2026-01-07 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(29, 3, 'CESA Announcement 9', 'CESA Chapter shared update 9 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 30, '2025-12-18 10:00:00', '2026-01-17 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(30, 3, 'CESA Announcement 10', 'CESA Chapter shared update 10 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 12, '2025-12-28 10:00:00', '2026-01-27 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(31, 4, 'MESA Announcement 1', 'MESA Organization shared update 1 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 46, '2025-10-29 10:00:00', '2025-11-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(32, 4, 'MESA Announcement 2', 'MESA Organization shared update 2 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 15, '2025-10-19 10:00:00', '2025-11-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(33, 4, 'MESA Announcement 3', 'MESA Organization shared update 3 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 39, '2025-10-09 10:00:00', '2025-11-08 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(34, 4, 'MESA Announcement 4', 'MESA Organization shared update 4 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 37, '2025-09-29 10:00:00', '2025-10-29 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(35, 4, 'MESA Announcement 5', 'MESA Organization shared update 5 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 12, '2025-09-19 10:00:00', '2025-10-19 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(36, 4, 'MESA Announcement 6', 'MESA Organization shared update 6 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 51, '2025-11-18 10:00:00', '2025-12-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(37, 4, 'MESA Announcement 7', 'MESA Organization shared update 7 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 43, '2025-11-28 10:00:00', '2025-12-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(38, 4, 'MESA Announcement 8', 'MESA Organization shared update 8 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 13, '2025-12-08 10:00:00', '2026-01-07 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(39, 4, 'MESA Announcement 9', 'MESA Organization shared update 9 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 34, '2025-12-18 10:00:00', '2026-01-17 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(40, 4, 'MESA Announcement 10', 'MESA Organization shared update 10 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 23, '2025-12-28 10:00:00', '2026-01-27 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(41, 5, 'ITSA Announcement 1', 'ITSA Community shared update 1 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 17, '2025-10-29 10:00:00', '2025-11-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(42, 5, 'ITSA Announcement 2', 'ITSA Community shared update 2 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 16, '2025-10-19 10:00:00', '2025-11-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(43, 5, 'ITSA Announcement 3', 'ITSA Community shared update 3 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 23, '2025-10-09 10:00:00', '2025-11-08 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(44, 5, 'ITSA Announcement 4', 'ITSA Community shared update 4 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 6, '2025-09-29 10:00:00', '2025-10-29 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(45, 5, 'ITSA Announcement 5', 'ITSA Community shared update 5 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 39, '2025-09-19 10:00:00', '2025-10-19 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(46, 5, 'ITSA Announcement 6', 'ITSA Community shared update 6 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 22, '2025-11-18 10:00:00', '2025-12-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(47, 5, 'ITSA Announcement 7', 'ITSA Community shared update 7 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 44, '2025-11-28 10:00:00', '2025-12-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(48, 5, 'ITSA Announcement 8', 'ITSA Community shared update 8 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 37, '2025-12-08 10:00:00', '2026-01-07 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(49, 5, 'ITSA Announcement 9', 'ITSA Community shared update 9 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 43, '2025-12-18 10:00:00', '2026-01-17 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(50, 5, 'ITSA Announcement 10', 'ITSA Community shared update 10 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 45, '2025-12-28 10:00:00', '2026-01-27 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(51, 6, 'IEEE Announcement 1', 'IEEE Student Branch shared update 1 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 42, '2025-10-29 10:00:00', '2025-11-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(52, 6, 'IEEE Announcement 2', 'IEEE Student Branch shared update 2 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 17, '2025-10-19 10:00:00', '2025-11-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(53, 6, 'IEEE Announcement 3', 'IEEE Student Branch shared update 3 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 52, '2025-10-09 10:00:00', '2025-11-08 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(54, 6, 'IEEE Announcement 4', 'IEEE Student Branch shared update 4 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 40, '2025-09-29 10:00:00', '2025-10-29 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(55, 6, 'IEEE Announcement 5', 'IEEE Student Branch shared update 5 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 20, '2025-09-19 10:00:00', '2025-10-19 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(56, 6, 'IEEE Announcement 6', 'IEEE Student Branch shared update 6 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 18, '2025-11-18 10:00:00', '2025-12-18 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(57, 6, 'IEEE Announcement 7', 'IEEE Student Branch shared update 7 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 28, '2025-11-28 10:00:00', '2025-12-28 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(58, 6, 'IEEE Announcement 8', 'IEEE Student Branch shared update 8 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 36, '2025-12-08 10:00:00', '2026-01-07 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(59, 6, 'IEEE Announcement 9', 'IEEE Student Branch shared update 9 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 24, '2025-12-18 10:00:00', '2026-01-17 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', ''),
(60, 6, 'IEEE Announcement 10', 'IEEE Student Branch shared update 10 regarding upcoming sessions, achievements, and opportunities.', 'General', 'Medium', 'All', NULL, NULL, NULL, 0, 0, 'Published', 44, '2025-12-28 10:00:00', '2026-01-27 10:00:00', '2025-11-07 19:42:57', '2025-11-07 19:42:57', '');

-- --------------------------------------------------------

--
-- Table structure for table `announcement_views`
--

CREATE TABLE `announcement_views` (
  `view_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `club_id` int(11) NOT NULL,
  `club_name` varchar(100) NOT NULL,
  `club_code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `founded_year` year(4) DEFAULT NULL,
  `logo` varchar(255) DEFAULT 'default_club_logo.png',
  `banner` varchar(255) DEFAULT 'default_club_banner.png',
  `color_scheme` varchar(20) DEFAULT '#3498db',
  `max_members` int(11) DEFAULT 100,
  `current_members` int(11) DEFAULT 0,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`club_id`, `club_name`, `club_code`, `description`, `mission`, `vision`, `founded_year`, `logo`, `banner`, `color_scheme`, `max_members`, `current_members`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ACM Student Chapter', 'ACM', 'Association for Computing Machinery - Advancing Computing as a Science and Profession', 'To foster a community of computing professionals and enthusiasts', 'To be the leading platform for computing innovation and education', '2015', 'default_club_logo.png', 'default_club_banner.png', '#3498db', 150, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(2, 'ACES Association', 'ACES', 'Association of Computer Engineering Students - Empowering Future Engineers', 'To nurture technical excellence and innovation in computer engineering', 'To create world-class computer engineers and innovators', '2016', 'default_club_logo.png', 'default_club_banner.png', '#e74c3c', 120, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(3, 'CESA Chapter', 'CESA', 'Civil Engineering Students Association - Building Tomorrow', 'To promote excellence in civil engineering education and practice', 'To shape the future of infrastructure and sustainable development', '2014', 'default_club_logo.png', 'default_club_banner.png', '#f39c12', 100, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(4, 'MESA Organization', 'MESA', 'Mechanical Engineering Students Association - Engineering Excellence', 'To advance mechanical engineering knowledge and innovation', 'To lead in mechanical engineering research and development', '2013', 'default_club_logo.png', 'default_club_banner.png', '#9b59b6', 130, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(5, 'ITSA Community', 'ITSA', 'Information Technology Students Association - Innovating the Future', 'To bridge the gap between technology and practical application', 'To be the catalyst for technological transformation', '2017', 'default_club_logo.png', 'default_club_banner.png', '#1abc9c', 110, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(6, 'IEEE Student Branch', 'IEEE', 'Institute of Electrical and Electronics Engineers - Advancing Technology', 'To promote electrical and electronics engineering excellence', 'To advance technology for humanity through innovation', '2012', 'default_club_logo.png', 'default_club_banner.png', '#34495e', 140, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(8, 'm', 'M', 'mi', NULL, NULL, NULL, 'default_club_logo.png', 'default_club_banner.png', 'green', 100, 0, 'Active', '2025-11-09 13:50:22', '2025-11-09 14:06:33');

-- --------------------------------------------------------

--
-- Table structure for table `club_resources`
--

CREATE TABLE `club_resources` (
  `resource_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `resource_name` varchar(200) NOT NULL,
  `resource_description` text DEFAULT NULL,
  `resource_type` enum('Document','Image','Video','Audio','Archive','Other') DEFAULT 'Document',
  `file_url` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `download_count` int(11) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `club_settings`
--

CREATE TABLE `club_settings` (
  `setting_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `event_title` varchar(200) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_type` enum('Workshop','Seminar','Competition','Social','Meeting','Conference','Exhibition','Fundraiser') DEFAULT 'Workshop',
  `venue` varchar(200) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `max_participants` int(11) DEFAULT 100,
  `current_participants` int(11) DEFAULT 0,
  `registration_fee` decimal(10,2) DEFAULT 0.00,
  `event_poster` varchar(255) DEFAULT NULL,
  `event_banner` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Published','Cancelled','Completed','Postponed') DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `organizer_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `prizes` text DEFAULT NULL,
  `social_media_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media_links`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `club_id`, `event_title`, `event_description`, `event_type`, `venue`, `start_datetime`, `end_datetime`, `registration_deadline`, `max_participants`, `current_participants`, `registration_fee`, `event_poster`, `event_banner`, `status`, `created_by`, `organizer_name`, `contact_email`, `contact_phone`, `tags`, `requirements`, `prizes`, `social_media_links`, `created_at`, `updated_at`) VALUES
(1, 1, 'ACM Event 1', 'ACM Student Chapter organized event 1 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-24 10:00:00', '2025-10-24 13:00:00', '2025-10-22 10:00:00', 100, 11, 0.00, NULL, NULL, 'Completed', 19, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(2, 1, 'ACM Event 2', 'ACM Student Chapter organized event 2 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-09 10:00:00', '2025-10-09 13:00:00', '2025-10-07 10:00:00', 100, 80, 0.00, NULL, NULL, 'Completed', 11, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(3, 1, 'ACM Event 3', 'ACM Student Chapter organized event 3 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-24 10:00:00', '2025-09-24 13:00:00', '2025-09-22 10:00:00', 100, 53, 0.00, NULL, NULL, 'Completed', 28, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(4, 1, 'ACM Event 4', 'ACM Student Chapter organized event 4 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-09 10:00:00', '2025-09-09 13:00:00', '2025-09-07 10:00:00', 100, 23, 0.00, NULL, NULL, 'Completed', 11, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(5, 1, 'ACM Event 5', 'ACM Student Chapter organized event 5 for skill enhancement.', 'Workshop', 'Main Hall', '2025-08-25 10:00:00', '2025-08-25 13:00:00', '2025-08-23 10:00:00', 100, 48, 0.00, NULL, NULL, 'Completed', 34, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(6, 1, 'ACM Event 6', 'ACM Student Chapter organized event 6 for skill enhancement.', 'Workshop', 'Main Hall', '2025-11-23 10:00:00', '2025-11-23 13:00:00', '2025-11-21 10:00:00', 100, 29, 0.00, NULL, NULL, 'Published', 34, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(7, 1, 'ACM Event 7', 'ACM Student Chapter organized event 7 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-08 10:00:00', '2025-12-08 13:00:00', '2025-12-06 10:00:00', 100, 26, 0.00, NULL, NULL, 'Published', 16, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(8, 1, 'ACM Event 8', 'ACM Student Chapter organized event 8 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-23 10:00:00', '2025-12-23 13:00:00', '2025-12-21 10:00:00', 100, 51, 0.00, NULL, NULL, 'Published', 30, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(9, 1, 'ACM Event 9', 'ACM Student Chapter organized event 9 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-07 10:00:00', '2026-01-07 13:00:00', '2026-01-05 10:00:00', 100, 38, 0.00, NULL, NULL, 'Published', 48, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(10, 1, 'ACM Event 10', 'ACM Student Chapter organized event 10 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-22 10:00:00', '2026-01-22 13:00:00', '2026-01-20 10:00:00', 100, 76, 0.00, NULL, NULL, 'Published', 16, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(11, 2, 'ACES Event 1', 'ACES Association organized event 1 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-24 10:00:00', '2025-10-24 13:00:00', '2025-10-22 10:00:00', 100, 37, 0.00, NULL, NULL, 'Completed', 36, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(12, 2, 'ACES Event 2', 'ACES Association organized event 2 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-09 10:00:00', '2025-10-09 13:00:00', '2025-10-07 10:00:00', 100, 64, 0.00, NULL, NULL, 'Completed', 29, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(13, 2, 'ACES Event 3', 'ACES Association organized event 3 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-24 10:00:00', '2025-09-24 13:00:00', '2025-09-22 10:00:00', 100, 34, 0.00, NULL, NULL, 'Completed', 8, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(14, 2, 'ACES Event 4', 'ACES Association organized event 4 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-09 10:00:00', '2025-09-09 13:00:00', '2025-09-07 10:00:00', 100, 29, 0.00, NULL, NULL, 'Completed', 15, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(15, 2, 'ACES Event 5', 'ACES Association organized event 5 for skill enhancement.', 'Workshop', 'Main Hall', '2025-08-25 10:00:00', '2025-08-25 13:00:00', '2025-08-23 10:00:00', 100, 48, 0.00, NULL, NULL, 'Completed', 36, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(16, 2, 'ACES Event 6', 'ACES Association organized event 6 for skill enhancement.', 'Workshop', 'Main Hall', '2025-11-23 10:00:00', '2025-11-23 13:00:00', '2025-11-21 10:00:00', 100, 39, 0.00, NULL, NULL, 'Published', 23, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(17, 2, 'ACES Event 7', 'ACES Association organized event 7 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-08 10:00:00', '2025-12-08 13:00:00', '2025-12-06 10:00:00', 100, 23, 0.00, NULL, NULL, 'Published', 6, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(18, 2, 'ACES Event 8', 'ACES Association organized event 8 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-23 10:00:00', '2025-12-23 13:00:00', '2025-12-21 10:00:00', 100, 19, 0.00, NULL, NULL, 'Published', 31, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(19, 2, 'ACES Event 9', 'ACES Association organized event 9 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-07 10:00:00', '2026-01-07 13:00:00', '2026-01-05 10:00:00', 100, 55, 0.00, NULL, NULL, 'Published', 18, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(20, 2, 'ACES Event 10', 'ACES Association organized event 10 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-22 10:00:00', '2026-01-22 13:00:00', '2026-01-20 10:00:00', 100, 52, 0.00, NULL, NULL, 'Published', 4, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(21, 3, 'CESA Event 1', 'CESA Chapter organized event 1 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-24 10:00:00', '2025-10-24 13:00:00', '2025-10-22 10:00:00', 100, 60, 0.00, NULL, NULL, 'Completed', 23, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(22, 3, 'CESA Event 2', 'CESA Chapter organized event 2 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-09 10:00:00', '2025-10-09 13:00:00', '2025-10-07 10:00:00', 100, 30, 0.00, NULL, NULL, 'Completed', 10, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(23, 3, 'CESA Event 3', 'CESA Chapter organized event 3 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-24 10:00:00', '2025-09-24 13:00:00', '2025-09-22 10:00:00', 100, 15, 0.00, NULL, NULL, 'Completed', 29, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(24, 3, 'CESA Event 4', 'CESA Chapter organized event 4 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-09 10:00:00', '2025-09-09 13:00:00', '2025-09-07 10:00:00', 100, 54, 0.00, NULL, NULL, 'Completed', 52, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(25, 3, 'CESA Event 5', 'CESA Chapter organized event 5 for skill enhancement.', 'Workshop', 'Main Hall', '2025-08-25 10:00:00', '2025-08-25 13:00:00', '2025-08-23 10:00:00', 100, 31, 0.00, NULL, NULL, 'Completed', 31, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(26, 3, 'CESA Event 6', 'CESA Chapter organized event 6 for skill enhancement.', 'Workshop', 'Main Hall', '2025-11-23 10:00:00', '2025-11-23 13:00:00', '2025-11-21 10:00:00', 100, 50, 0.00, NULL, NULL, 'Published', 9, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(27, 3, 'CESA Event 7', 'CESA Chapter organized event 7 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-08 10:00:00', '2025-12-08 13:00:00', '2025-12-06 10:00:00', 100, 55, 0.00, NULL, NULL, 'Published', 40, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(28, 3, 'CESA Event 8', 'CESA Chapter organized event 8 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-23 10:00:00', '2025-12-23 13:00:00', '2025-12-21 10:00:00', 100, 51, 0.00, NULL, NULL, 'Published', 41, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(29, 3, 'CESA Event 9', 'CESA Chapter organized event 9 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-07 10:00:00', '2026-01-07 13:00:00', '2026-01-05 10:00:00', 100, 75, 0.00, NULL, NULL, 'Published', 37, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(30, 3, 'CESA Event 10', 'CESA Chapter organized event 10 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-22 10:00:00', '2026-01-22 13:00:00', '2026-01-20 10:00:00', 100, 55, 0.00, NULL, NULL, 'Published', 30, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(31, 4, 'MESA Event 1', 'MESA Organization organized event 1 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-24 10:00:00', '2025-10-24 13:00:00', '2025-10-22 10:00:00', 100, 79, 0.00, NULL, NULL, 'Completed', 48, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(32, 4, 'MESA Event 2', 'MESA Organization organized event 2 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-09 10:00:00', '2025-10-09 13:00:00', '2025-10-07 10:00:00', 100, 14, 0.00, NULL, NULL, 'Completed', 12, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(33, 4, 'MESA Event 3', 'MESA Organization organized event 3 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-24 10:00:00', '2025-09-24 13:00:00', '2025-09-22 10:00:00', 100, 71, 0.00, NULL, NULL, 'Completed', 43, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(34, 4, 'MESA Event 4', 'MESA Organization organized event 4 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-09 10:00:00', '2025-09-09 13:00:00', '2025-09-07 10:00:00', 100, 41, 0.00, NULL, NULL, 'Completed', 17, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(35, 4, 'MESA Event 5', 'MESA Organization organized event 5 for skill enhancement.', 'Workshop', 'Main Hall', '2025-08-25 10:00:00', '2025-08-25 13:00:00', '2025-08-23 10:00:00', 100, 68, 0.00, NULL, NULL, 'Completed', 22, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(36, 4, 'MESA Event 6', 'MESA Organization organized event 6 for skill enhancement.', 'Workshop', 'Main Hall', '2025-11-23 10:00:00', '2025-11-23 13:00:00', '2025-11-21 10:00:00', 100, 14, 0.00, NULL, NULL, 'Published', 24, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(37, 4, 'MESA Event 7', 'MESA Organization organized event 7 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-08 10:00:00', '2025-12-08 13:00:00', '2025-12-06 10:00:00', 100, 45, 0.00, NULL, NULL, 'Published', 33, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(38, 4, 'MESA Event 8', 'MESA Organization organized event 8 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-23 10:00:00', '2025-12-23 13:00:00', '2025-12-21 10:00:00', 100, 19, 0.00, NULL, NULL, 'Published', 47, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(39, 4, 'MESA Event 9', 'MESA Organization organized event 9 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-07 10:00:00', '2026-01-07 13:00:00', '2026-01-05 10:00:00', 100, 17, 0.00, NULL, NULL, 'Published', 15, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(40, 4, 'MESA Event 10', 'MESA Organization organized event 10 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-22 10:00:00', '2026-01-22 13:00:00', '2026-01-20 10:00:00', 100, 39, 0.00, NULL, NULL, 'Published', 17, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(41, 5, 'ITSA Event 1', 'ITSA Community organized event 1 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-24 10:00:00', '2025-10-24 13:00:00', '2025-10-22 10:00:00', 100, 61, 0.00, NULL, NULL, 'Completed', 21, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(42, 5, 'ITSA Event 2', 'ITSA Community organized event 2 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-09 10:00:00', '2025-10-09 13:00:00', '2025-10-07 10:00:00', 100, 35, 0.00, NULL, NULL, 'Completed', 41, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(43, 5, 'ITSA Event 3', 'ITSA Community organized event 3 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-24 10:00:00', '2025-09-24 13:00:00', '2025-09-22 10:00:00', 100, 59, 0.00, NULL, NULL, 'Completed', 46, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(44, 5, 'ITSA Event 4', 'ITSA Community organized event 4 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-09 10:00:00', '2025-09-09 13:00:00', '2025-09-07 10:00:00', 100, 18, 0.00, NULL, NULL, 'Completed', 35, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(45, 5, 'ITSA Event 5', 'ITSA Community organized event 5 for skill enhancement.', 'Workshop', 'Main Hall', '2025-08-25 10:00:00', '2025-08-25 13:00:00', '2025-08-23 10:00:00', 100, 27, 0.00, NULL, NULL, 'Completed', 3, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(46, 5, 'ITSA Event 6', 'ITSA Community organized event 6 for skill enhancement.', 'Workshop', 'Main Hall', '2025-11-23 10:00:00', '2025-11-23 13:00:00', '2025-11-21 10:00:00', 100, 45, 0.00, NULL, NULL, 'Published', 41, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(47, 5, 'ITSA Event 7', 'ITSA Community organized event 7 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-08 10:00:00', '2025-12-08 13:00:00', '2025-12-06 10:00:00', 100, 36, 0.00, NULL, NULL, 'Published', 22, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(48, 5, 'ITSA Event 8', 'ITSA Community organized event 8 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-23 10:00:00', '2025-12-23 13:00:00', '2025-12-21 10:00:00', 100, 60, 0.00, NULL, NULL, 'Published', 41, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(49, 5, 'ITSA Event 9', 'ITSA Community organized event 9 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-07 10:00:00', '2026-01-07 13:00:00', '2026-01-05 10:00:00', 100, 51, 0.00, NULL, NULL, 'Published', 46, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(50, 5, 'ITSA Event 10', 'ITSA Community organized event 10 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-22 10:00:00', '2026-01-22 13:00:00', '2026-01-20 10:00:00', 100, 29, 0.00, NULL, NULL, 'Published', 34, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(51, 6, 'IEEE Event 1', 'IEEE Student Branch organized event 1 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-24 10:00:00', '2025-10-24 13:00:00', '2025-10-22 10:00:00', 100, 32, 0.00, NULL, NULL, 'Completed', 48, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(52, 6, 'IEEE Event 2', 'IEEE Student Branch organized event 2 for skill enhancement.', 'Workshop', 'Main Hall', '2025-10-09 10:00:00', '2025-10-09 13:00:00', '2025-10-07 10:00:00', 100, 71, 0.00, NULL, NULL, 'Completed', 39, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(53, 6, 'IEEE Event 3', 'IEEE Student Branch organized event 3 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-24 10:00:00', '2025-09-24 13:00:00', '2025-09-22 10:00:00', 100, 30, 0.00, NULL, NULL, 'Completed', 22, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(54, 6, 'IEEE Event 4', 'IEEE Student Branch organized event 4 for skill enhancement.', 'Workshop', 'Main Hall', '2025-09-09 10:00:00', '2025-09-09 13:00:00', '2025-09-07 10:00:00', 100, 32, 0.00, NULL, NULL, 'Completed', 24, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(55, 6, 'IEEE Event 5', 'IEEE Student Branch organized event 5 for skill enhancement.', 'Workshop', 'Main Hall', '2025-08-25 10:00:00', '2025-08-25 13:00:00', '2025-08-23 10:00:00', 100, 30, 0.00, NULL, NULL, 'Completed', 18, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(56, 6, 'IEEE Event 6', 'IEEE Student Branch organized event 6 for skill enhancement.', 'Workshop', 'Main Hall', '2025-11-23 10:00:00', '2025-11-23 13:00:00', '2025-11-21 10:00:00', 100, 19, 0.00, NULL, NULL, 'Published', 27, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(57, 6, 'IEEE Event 7', 'IEEE Student Branch organized event 7 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-08 10:00:00', '2025-12-08 13:00:00', '2025-12-06 10:00:00', 100, 76, 0.00, NULL, NULL, 'Published', 22, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(58, 6, 'IEEE Event 8', 'IEEE Student Branch organized event 8 for skill enhancement.', 'Workshop', 'Main Hall', '2025-12-23 10:00:00', '2025-12-23 13:00:00', '2025-12-21 10:00:00', 100, 17, 0.00, NULL, NULL, 'Published', 17, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(59, 6, 'IEEE Event 9', 'IEEE Student Branch organized event 9 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-07 10:00:00', '2026-01-07 13:00:00', '2026-01-05 10:00:00', 100, 36, 0.00, NULL, NULL, 'Published', 26, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(60, 6, 'IEEE Event 10', 'IEEE Student Branch organized event 10 for skill enhancement.', 'Workshop', 'Main Hall', '2026-01-22 10:00:00', '2026-01-22 13:00:00', '2026-01-20 10:00:00', 100, 20, 0.00, NULL, NULL, 'Published', 4, NULL, 'info@clubsphere.com', '+91-9876500000', 'tech,innovation', 'Laptop required.', 'Certificates for winners.', '[\"https://example.com\"]', '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(61, 1, '100', 'A', 'Seminar', 'SVKM IOT DHULE', '2025-12-12 10:00:00', '2025-12-12 17:00:00', NULL, 100, 0, 0.00, NULL, NULL, 'Published', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-09 13:31:34', '2025-11-09 13:31:34'),
(62, 1, 'A', 'A', 'Seminar', 'SVKM IOT DHULE', '2025-10-10 10:00:00', '2025-10-10 17:00:00', NULL, 100, 0, 0.00, NULL, NULL, 'Published', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-09 13:32:19', '2025-11-09 13:32:19');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Registered','Attended','Cancelled','NoShow') DEFAULT 'Registered',
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `user_id`, `registration_date`, `status`, `checked_in_at`, `notes`) VALUES
(1, 40, 3, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(2, 8, 3, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(3, 51, 3, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(4, 55, 4, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(5, 42, 4, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(6, 13, 4, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(7, 29, 5, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(8, 5, 5, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(9, 31, 5, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(10, 60, 6, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(11, 13, 6, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(12, 17, 6, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(13, 15, 7, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(14, 30, 7, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(15, 49, 7, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(16, 41, 8, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(17, 33, 8, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(18, 4, 8, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(19, 50, 9, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(20, 10, 9, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(21, 21, 9, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(22, 54, 10, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(23, 55, 10, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(24, 9, 10, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(25, 14, 11, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(26, 55, 11, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(27, 49, 11, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(28, 49, 12, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(29, 46, 12, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(30, 31, 12, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(31, 23, 13, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(32, 29, 13, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(33, 3, 13, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(34, 6, 14, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(35, 45, 14, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(36, 58, 14, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(37, 50, 15, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(38, 38, 15, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(39, 32, 15, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(40, 58, 16, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(41, 19, 16, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(42, 44, 16, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(43, 50, 17, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(44, 18, 17, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(45, 29, 17, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(46, 36, 18, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(47, 56, 18, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(48, 60, 18, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(49, 26, 19, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(50, 48, 19, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(51, 37, 19, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(52, 4, 20, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(53, 55, 20, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(54, 19, 20, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(55, 22, 21, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(56, 38, 21, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(57, 59, 21, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(58, 51, 22, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(59, 37, 22, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(60, 56, 22, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(61, 37, 23, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(62, 13, 23, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(63, 8, 23, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(64, 41, 24, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(65, 23, 24, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(66, 21, 24, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(67, 54, 25, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(68, 10, 25, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(69, 40, 25, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(70, 47, 26, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(71, 2, 26, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(72, 22, 26, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(73, 19, 27, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(74, 48, 27, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(75, 6, 27, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(76, 25, 28, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(77, 10, 28, '2025-11-07 19:42:57', 'Registered', NULL, NULL),
(78, 19, 28, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(79, 19, 29, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(80, 7, 29, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(81, 31, 29, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(82, 17, 30, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(83, 23, 30, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(84, 21, 30, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(85, 60, 31, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(86, 42, 31, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(87, 11, 31, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(88, 44, 32, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(89, 26, 32, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(90, 47, 32, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(91, 22, 33, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(92, 24, 33, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(93, 56, 33, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(94, 52, 34, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(95, 17, 34, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(96, 14, 34, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(97, 4, 35, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(98, 5, 35, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(99, 51, 35, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(100, 8, 36, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(101, 60, 36, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(102, 32, 36, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(103, 54, 37, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(104, 24, 37, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(105, 4, 37, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(106, 56, 38, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(107, 4, 38, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(108, 2, 38, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(109, 35, 39, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(110, 17, 39, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(111, 56, 39, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(112, 25, 40, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(113, 49, 40, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(114, 18, 40, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(115, 9, 41, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(116, 1, 41, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(117, 46, 41, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(118, 3, 42, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(119, 24, 42, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(120, 41, 42, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(121, 37, 43, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(122, 39, 43, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(123, 22, 43, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(124, 2, 44, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(125, 55, 44, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(126, 5, 44, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(127, 40, 45, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(128, 23, 45, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(129, 10, 45, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(130, 58, 46, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(131, 9, 46, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(132, 17, 46, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(133, 30, 47, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(134, 44, 47, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(135, 36, 47, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(136, 30, 48, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(137, 5, 48, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(138, 26, 48, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(139, 13, 49, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(140, 26, 49, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(141, 9, 49, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(142, 59, 50, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(143, 35, 50, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(144, 20, 50, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(145, 23, 51, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(146, 32, 51, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(147, 57, 51, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(148, 22, 52, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(149, 58, 52, '2025-11-07 19:42:58', 'Registered', NULL, NULL),
(150, 5, 52, '2025-11-07 19:42:58', 'Registered', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `financial_records`
--

CREATE TABLE `financial_records` (
  `record_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `transaction_type` enum('Income','Expense') NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_url` varchar(255) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `ip_address` varchar(64) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `meeting_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `meeting_title` varchar(200) NOT NULL,
  `meeting_type` enum('General','Executive','Planning','Emergency') DEFAULT 'General',
  `venue` varchar(200) DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `minutes` text DEFAULT NULL,
  `attendees_count` int(11) DEFAULT 0,
  `status` enum('Scheduled','InProgress','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_attendance`
--

CREATE TABLE `meeting_attendance` (
  `attendance_id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attendance_status` enum('Present','Absent','Late','Excused') DEFAULT 'Absent',
  `attended_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE `memberships` (
  `membership_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `position` enum('President','VicePresident','Secretary','Treasurer','EventCoordinator','SocialMediaHead','Member') DEFAULT 'Member',
  `joined_date` date NOT NULL,
  `left_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Pending','Removed') DEFAULT 'Pending',
  `total_contribution_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `memberships`
--

INSERT INTO `memberships` (`membership_id`, `user_id`, `club_id`, `position`, `joined_date`, `left_date`, `status`, `total_contribution_points`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'President', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(2, 1, 2, 'President', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(3, 1, 3, 'President', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(4, 1, 4, 'President', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(5, 1, 5, 'President', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(6, 1, 6, 'President', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(7, 2, 1, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(8, 2, 2, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(9, 2, 3, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(10, 2, 4, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(11, 2, 5, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(12, 2, 6, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06'),
(13, 3, 1, 'Member', '2023-01-01', NULL, 'Active', 96, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(14, 4, 2, 'Secretary', '2023-01-01', NULL, 'Active', 51, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(15, 5, 3, 'Member', '2023-01-01', NULL, 'Active', 24, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(16, 6, 4, 'Treasurer', '2023-01-01', NULL, 'Active', 78, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(17, 7, 5, 'Member', '2023-01-01', NULL, 'Active', 112, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(18, 8, 6, 'Member', '2023-01-01', NULL, 'Active', 68, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(19, 9, 1, 'Member', '2023-01-01', NULL, 'Active', 119, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(20, 10, 2, 'Member', '2023-01-01', NULL, 'Active', 5, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(21, 11, 3, 'Member', '2023-01-01', NULL, 'Active', 126, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(22, 12, 4, 'Member', '2023-01-01', NULL, 'Active', 6, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(23, 13, 5, 'Member', '2023-01-01', NULL, 'Active', 35, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(24, 14, 6, 'Member', '2023-01-01', NULL, 'Active', 28, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(25, 15, 1, 'Member', '2023-01-01', NULL, 'Active', 15, '2025-11-07 19:42:56', '2025-11-07 19:42:56'),
(26, 16, 2, 'Member', '2023-01-01', NULL, 'Active', 125, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(27, 17, 3, 'Treasurer', '2023-01-01', NULL, 'Active', 101, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(28, 18, 4, 'Treasurer', '2023-01-01', NULL, 'Active', 11, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(29, 19, 5, 'Treasurer', '2023-01-01', NULL, 'Active', 30, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(30, 20, 6, 'Member', '2023-01-01', NULL, 'Active', 121, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(31, 21, 1, 'Treasurer', '2023-01-01', NULL, 'Active', 32, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(32, 22, 2, 'Treasurer', '2023-01-01', NULL, 'Active', 4, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(33, 23, 3, 'Secretary', '2023-01-01', NULL, 'Active', 54, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(34, 24, 4, 'Member', '2023-01-01', NULL, 'Active', 60, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(35, 25, 5, 'Treasurer', '2023-01-01', NULL, 'Active', 138, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(36, 26, 6, 'Member', '2023-01-01', NULL, 'Active', 125, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(37, 27, 1, 'Member', '2023-01-01', NULL, 'Active', 23, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(38, 28, 2, 'Member', '2023-01-01', NULL, 'Active', 86, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(39, 29, 3, 'Secretary', '2023-01-01', NULL, 'Active', 42, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(40, 30, 4, 'Member', '2023-01-01', NULL, 'Active', 102, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(41, 31, 5, 'Member', '2023-01-01', NULL, 'Active', 64, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(42, 32, 6, 'Treasurer', '2023-01-01', NULL, 'Active', 102, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(43, 33, 1, 'Member', '2023-01-01', NULL, 'Active', 73, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(44, 34, 2, 'Member', '2023-01-01', NULL, 'Active', 137, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(45, 35, 3, 'Member', '2023-01-01', NULL, 'Active', 136, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(46, 36, 4, 'Member', '2023-01-01', NULL, 'Active', 101, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(47, 37, 5, 'Member', '2023-01-01', NULL, 'Active', 5, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(48, 38, 6, 'Secretary', '2023-01-01', NULL, 'Active', 50, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(49, 39, 1, 'Member', '2023-01-01', NULL, 'Active', 10, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(50, 40, 2, 'Member', '2023-01-01', NULL, 'Active', 133, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(51, 41, 3, 'Secretary', '2023-01-01', NULL, 'Active', 86, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(52, 42, 4, 'Member', '2023-01-01', NULL, 'Active', 33, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(53, 43, 5, 'Member', '2023-01-01', NULL, 'Active', 81, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(54, 44, 6, 'Treasurer', '2023-01-01', NULL, 'Active', 59, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(55, 45, 1, 'Member', '2023-01-01', NULL, 'Active', 43, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(56, 46, 2, 'Member', '2023-01-01', NULL, 'Active', 17, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(57, 47, 3, 'Treasurer', '2023-01-01', NULL, 'Active', 149, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(58, 48, 4, 'Secretary', '2023-01-01', NULL, 'Active', 108, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(59, 49, 5, 'Treasurer', '2023-01-01', NULL, 'Active', 86, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(60, 50, 6, 'Secretary', '2023-01-01', NULL, 'Active', 131, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(61, 51, 1, 'Member', '2023-01-01', NULL, 'Active', 112, '2025-11-07 19:42:57', '2025-11-07 19:42:57'),
(62, 52, 2, 'Member', '2023-01-01', NULL, 'Active', 24, '2025-11-07 19:42:57', '2025-11-07 19:42:57');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `related_entity_type` varchar(50) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_announcements`
-- (See below for the actual view)
--
CREATE TABLE `recent_announcements` (
`announcement_id` int(11)
,`announcement_title` varchar(200)
,`announcement_content` text
,`announcement_type` enum('General','Event','Meeting','Achievement','Opportunity','Urgent')
,`priority` enum('Low','Medium','High','Urgent')
,`is_featured` tinyint(1)
,`view_count` int(11)
,`published_at` timestamp
,`club_name` varchar(100)
,`club_code` varchar(10)
,`author_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Registered','Cancelled') DEFAULT 'Registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'ClubSphere', '2025-11-08 00:02:06'),
(2, 'tagline', 'Where Passion Meets Innovation', '2025-11-08 00:02:06'),
(3, 'default_club_color', 'blue', '2025-11-08 00:02:06'),
(4, 'event_visibility', 'public', '2025-11-08 00:02:06'),
(5, 'max_upload_mb', '10', '2025-11-08 00:02:06'),
(6, 'contact_email', 'admin@clubsphere.com', '2025-11-08 00:02:06'),
(7, 'contact_phone', '+91-9876543210', '2025-11-08 00:02:06');

-- --------------------------------------------------------

--
-- Stand-in structure for view `upcoming_events`
-- (See below for the actual view)
--
CREATE TABLE `upcoming_events` (
`event_id` int(11)
,`event_title` varchar(200)
,`event_description` text
,`event_type` enum('Workshop','Seminar','Competition','Social','Meeting','Conference','Exhibition','Fundraiser')
,`venue` varchar(200)
,`start_datetime` datetime
,`end_datetime` datetime
,`registration_deadline` datetime
,`max_participants` int(11)
,`current_participants` int(11)
,`registration_fee` decimal(10,2)
,`event_poster` varchar(255)
,`status` enum('Draft','Published','Cancelled','Completed','Postponed')
,`club_name` varchar(100)
,`club_code` varchar(10)
,`organizer_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.jpg',
  `role` enum('SuperAdmin','Admin','President','VicePresident','Secretary','Treasurer','Member') DEFAULT 'Member',
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `phone`, `profile_image`, `role`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'superadmin', 'superadmin@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', NULL, 'default.jpg', 'SuperAdmin', 'Active', '2025-11-07 13:11:06', '2025-11-07 13:40:10', NULL),
(2, 'admin', 'admin@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', NULL, 'default.jpg', 'Admin', 'Active', '2025-11-07 13:11:06', '2025-11-07 18:55:29', NULL),
(3, 'user3', 'user3@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User3 Test3', '+91-9828553103', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(4, 'user4', 'user4@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User4 Test4', '+91-9883008358', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(5, 'user5', 'user5@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User5 Test5', '+91-9851558122', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(6, 'user6', 'user6@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User6 Test6', '+91-9857375482', 'default.jpg', 'VicePresident', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(7, 'user7', 'user7@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User7 Test7', '+91-9836467558', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-08 05:42:49', NULL),
(8, 'user8', 'user8@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User8 Test8', '+91-9873795948', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(9, 'user9', 'user9@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User9 Test9', '+91-9814005193', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(10, 'user10', 'user10@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User10 Test10', '+91-9862854044', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(11, 'user11', 'user11@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User11 Test11', '+91-9844326061', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(12, 'user12', 'user12@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User12 Test12', '+91-9833745776', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(13, 'user13', 'user13@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User13 Test13', '+91-9823449067', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(14, 'user14', 'user14@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User14 Test14', '+91-9882113952', 'default.jpg', 'VicePresident', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(15, 'user15', 'user15@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User15 Test15', '+91-9816872984', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(16, 'user16', 'user16@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User16 Test16', '+91-9846578156', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(17, 'user17', 'user17@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User17 Test17', '+91-9892781837', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(18, 'user18', 'user18@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User18 Test18', '+91-9865892186', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(19, 'user19', 'user19@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User19 Test19', '+91-9894515365', 'default.jpg', 'VicePresident', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(20, 'user20', 'user20@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User20 Test20', '+91-9870428277', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(21, 'user21', 'user21@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User21 Test21', '+91-9885999715', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(22, 'user22', 'user22@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User22 Test22', '+91-9828755749', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(23, 'user23', 'user23@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User23 Test23', '+91-9824845711', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(24, 'user24', 'user24@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User24 Test24', '+91-9832491020', 'default.jpg', 'VicePresident', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(25, 'user25', 'user25@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User25 Test25', '+91-9869975490', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(26, 'user26', 'user26@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User26 Test26', '+91-9814433071', 'default.jpg', 'Member', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(27, 'user27', 'user27@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User27 Test27', '+91-9870114886', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(28, 'user28', 'user28@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User28 Test28', '+91-9844299106', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(29, 'user29', 'user29@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User29 Test29', '+91-9832056945', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(30, 'user30', 'user30@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User30 Test30', '+91-9872004935', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(31, 'user31', 'user31@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User31 Test31', '+91-9868462202', 'default.jpg', 'VicePresident', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(32, 'user32', 'user32@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User32 Test32', '+91-9827578767', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(33, 'user33', 'user33@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User33 Test33', '+91-9812567664', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(34, 'user34', 'user34@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User34 Test34', '+91-9893149094', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(35, 'user35', 'user35@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User35 Test35', '+91-9869522916', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(36, 'user36', 'user36@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User36 Test36', '+91-9846805985', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(37, 'user37', 'user37@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User37 Test37', '+91-9814432756', 'default.jpg', 'VicePresident', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(38, 'user38', 'user38@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User38 Test38', '+91-9881115565', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(39, 'user39', 'user39@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User39 Test39', '+91-9848025813', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(40, 'user40', 'user40@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User40 Test40', '+91-9861162897', 'default.jpg', 'Member', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(41, 'user41', 'user41@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User41 Test41', '+91-9879913787', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(42, 'user42', 'user42@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User42 Test42', '+91-9860668940', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(43, 'user43', 'user43@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User43 Test43', '+91-9814495784', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(44, 'user44', 'user44@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User44 Test44', '+91-9890250674', 'default.jpg', 'Member', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(45, 'user45', 'user45@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User45 Test45', '+91-9860889929', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(46, 'user46', 'user46@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User46 Test46', '+91-9877121353', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(47, 'user47', 'user47@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User47 Test47', '+91-9885567719', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(48, 'user48', 'user48@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User48 Test48', '+91-9855696307', 'default.jpg', 'Admin', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(49, 'user49', 'user49@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User49 Test49', '+91-9859528674', 'default.jpg', 'President', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(50, 'user50', 'user50@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User50 Test50', '+91-9819310950', 'default.jpg', 'Treasurer', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(51, 'user51', 'user51@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User51 Test51', '+91-9830886655', 'default.jpg', 'Member', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL),
(52, 'user52', 'user52@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User52 Test52', '+91-9834471796', 'default.jpg', 'Secretary', 'Active', '2025-11-07 19:42:56', '2025-11-07 19:42:56', NULL);

-- --------------------------------------------------------

--
-- Structure for view `active_club_members`
--
DROP TABLE IF EXISTS `active_club_members`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_club_members`  AS SELECT `m`.`membership_id` AS `membership_id`, `u`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`profile_image` AS `profile_image`, `c`.`club_id` AS `club_id`, `c`.`club_name` AS `club_name`, `c`.`club_code` AS `club_code`, `m`.`position` AS `position`, `m`.`joined_date` AS `joined_date`, `m`.`total_contribution_points` AS `total_contribution_points` FROM ((`memberships` `m` join `users` `u` on(`m`.`user_id` = `u`.`user_id`)) join `clubs` `c` on(`m`.`club_id` = `c`.`club_id`)) WHERE `m`.`status` = 'Active' AND `u`.`status` = 'Active' ;

-- --------------------------------------------------------

--
-- Structure for view `recent_announcements`
--
DROP TABLE IF EXISTS `recent_announcements`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_announcements`  AS SELECT `a`.`announcement_id` AS `announcement_id`, `a`.`announcement_title` AS `announcement_title`, `a`.`announcement_content` AS `announcement_content`, `a`.`announcement_type` AS `announcement_type`, `a`.`priority` AS `priority`, `a`.`is_featured` AS `is_featured`, `a`.`view_count` AS `view_count`, `a`.`published_at` AS `published_at`, `c`.`club_name` AS `club_name`, `c`.`club_code` AS `club_code`, `u`.`full_name` AS `author_name` FROM ((`announcements` `a` left join `clubs` `c` on(`a`.`club_id` = `c`.`club_id`)) join `users` `u` on(`a`.`created_by` = `u`.`user_id`)) WHERE `a`.`status` = 'Published' AND (`a`.`expires_at` is null OR `a`.`expires_at` > current_timestamp()) ORDER BY `a`.`published_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `upcoming_events`
--
DROP TABLE IF EXISTS `upcoming_events`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `upcoming_events`  AS SELECT `e`.`event_id` AS `event_id`, `e`.`event_title` AS `event_title`, `e`.`event_description` AS `event_description`, `e`.`event_type` AS `event_type`, `e`.`venue` AS `venue`, `e`.`start_datetime` AS `start_datetime`, `e`.`end_datetime` AS `end_datetime`, `e`.`registration_deadline` AS `registration_deadline`, `e`.`max_participants` AS `max_participants`, `e`.`current_participants` AS `current_participants`, `e`.`registration_fee` AS `registration_fee`, `e`.`event_poster` AS `event_poster`, `e`.`status` AS `status`, `c`.`club_name` AS `club_name`, `c`.`club_code` AS `club_code`, `u`.`full_name` AS `organizer_name` FROM ((`events` `e` join `clubs` `c` on(`e`.`club_id` = `c`.`club_id`)) join `users` `u` on(`e`.`created_by` = `u`.`user_id`)) WHERE `e`.`start_datetime` > current_timestamp() AND `e`.`status` in ('Published','Completed') ORDER BY `e`.`start_datetime` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_achievement_date` (`achievement_date`),
  ADD KEY `idx_achievement_type` (`achievement_type`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_announcements_priority` (`priority`,`published_at`);
ALTER TABLE `announcements` ADD FULLTEXT KEY `idx_search` (`announcement_title`,`announcement_content`);

--
-- Indexes for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`view_id`),
  ADD UNIQUE KEY `unique_view` (`announcement_id`,`user_id`),
  ADD KEY `idx_announcement` (`announcement_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`club_id`),
  ADD UNIQUE KEY `club_name` (`club_name`),
  ADD UNIQUE KEY `club_code` (`club_code`),
  ADD KEY `idx_club_code` (`club_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `club_resources`
--
ALTER TABLE `club_resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_resource_type` (`resource_type`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `club_settings`
--
ALTER TABLE `club_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_setting` (`club_id`,`setting_key`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_start_datetime` (`start_datetime`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_events_date_range` (`start_datetime`,`end_datetime`);
ALTER TABLE `events` ADD FULLTEXT KEY `idx_search` (`event_title`,`event_description`,`tags`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `attempted_at` (`attempted_at`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`meeting_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_meeting_date` (`meeting_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`meeting_id`,`user_id`),
  ADD KEY `idx_meeting` (`meeting_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships`
  ADD PRIMARY KEY (`membership_id`),
  ADD UNIQUE KEY `unique_membership` (`user_id`,`club_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_club` (`club_id`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_memberships_club_position` (`club_id`,`position`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_notification_type` (`notification_type`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD UNIQUE KEY `unique_registration` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `club_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `club_resources`
--
ALTER TABLE `club_resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `club_settings`
--
ALTER TABLE `club_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `financial_records`
--
ALTER TABLE `financial_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `meeting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `achievements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD CONSTRAINT `announcement_views_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `club_resources`
--
ALTER TABLE `club_resources`
  ADD CONSTRAINT `club_resources_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `club_resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `club_settings`
--
ALTER TABLE `club_settings`
  ADD CONSTRAINT `club_settings_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD CONSTRAINT `financial_records_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `financial_records_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_records_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meetings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  ADD CONSTRAINT `meeting_attendance_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`meeting_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meeting_attendance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `memberships`
--
ALTER TABLE `memberships`
  ADD CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
