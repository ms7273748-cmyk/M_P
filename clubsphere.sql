-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 08:31 PM
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
(2, 2, 'USER_STATUS_CHANGED', 'User 1 status -> Active', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-07 13:40:10');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(6, 'IEEE Student Branch', 'IEEE', 'Institute of Electrical and Electronics Engineers - Advancing Technology', 'To promote electrical and electronics engineering excellence', 'To advance technology for humanity through innovation', '2012', 'default_club_logo.png', 'default_club_banner.png', '#34495e', 140, 0, 'Active', '2025-11-07 13:11:06', '2025-11-07 13:11:06');

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
(12, 2, 6, 'VicePresident', '2020-01-01', NULL, 'Active', 0, '2025-11-07 13:11:06', '2025-11-07 13:11:06');

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
(2, 'admin', 'admin@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', NULL, 'default.jpg', 'Admin', 'Active', '2025-11-07 13:11:06', '2025-11-07 18:55:29', NULL);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `club_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_records`
--
ALTER TABLE `financial_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
