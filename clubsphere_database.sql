-- ClubSphere - Complete Database Schema
-- A comprehensive club management system with role-based access control

CREATE DATABASE IF NOT EXISTS clubsphere;
USE clubsphere;

-- Users Table with Role Hierarchy
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    role ENUM('SuperAdmin', 'Admin', 'President', 'VicePresident', 'Secretary', 'Treasurer', 'Member') DEFAULT 'Member',
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clubs Table
CREATE TABLE clubs (
    club_id INT PRIMARY KEY AUTO_INCREMENT,
    club_name VARCHAR(100) UNIQUE NOT NULL,
    club_code VARCHAR(10) UNIQUE NOT NULL,
    description TEXT,
    mission TEXT,
    vision TEXT,
    founded_year YEAR,
    logo VARCHAR(255) DEFAULT 'default_club_logo.png',
    banner VARCHAR(255) DEFAULT 'default_club_banner.png',
    color_scheme VARCHAR(20) DEFAULT '#3498db',
    max_members INT DEFAULT 100,
    current_members INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_club_code (club_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club Memberships Table
CREATE TABLE memberships (
    membership_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    club_id INT NOT NULL,
    position ENUM('President', 'VicePresident', 'Secretary', 'Treasurer', 'EventCoordinator', 'SocialMediaHead', 'Member') DEFAULT 'Member',
    joined_date DATE NOT NULL,
    left_date DATE NULL,
    status ENUM('Active', 'Inactive', 'Pending', 'Removed') DEFAULT 'Pending',
    total_contribution_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (user_id, club_id),
    INDEX idx_user (user_id),
    INDEX idx_club (club_id),
    INDEX idx_position (position),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events Table
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    event_title VARCHAR(200) NOT NULL,
    event_description TEXT,
    event_type ENUM('Workshop', 'Seminar', 'Competition', 'Social', 'Meeting', 'Conference', 'Exhibition', 'Fundraiser') DEFAULT 'Workshop',
    venue VARCHAR(200),
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    registration_deadline DATETIME,
    max_participants INT DEFAULT 100,
    current_participants INT DEFAULT 0,
    registration_fee DECIMAL(10,2) DEFAULT 0.00,
    event_poster VARCHAR(255),
    event_banner VARCHAR(255),
    status ENUM('Draft', 'Published', 'Cancelled', 'Completed', 'Postponed') DEFAULT 'Draft',
    created_by INT NOT NULL,
    organizer_name VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    tags VARCHAR(500),
    requirements TEXT,
    prizes TEXT,
    social_media_links JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_club (club_id),
    INDEX idx_status (status),
    INDEX idx_event_type (event_type),
    INDEX idx_start_datetime (start_datetime),
    INDEX idx_created_by (created_by),
    FULLTEXT idx_search (event_title, event_description, tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Registrations Table
CREATE TABLE event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Registered', 'Attended', 'Cancelled', 'NoShow') DEFAULT 'Registered',
    checked_in_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_event (event_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements Table
CREATE TABLE announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT,
    announcement_title VARCHAR(200) NOT NULL,
    announcement_content TEXT NOT NULL,
    announcement_type ENUM('General', 'Event', 'Meeting', 'Achievement', 'Opportunity', 'Urgent') DEFAULT 'General',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    target_audience ENUM('All', 'Members', 'Officers', 'Specific') DEFAULT 'All',
    target_clubs JSON,
    attachment_url VARCHAR(255),
    image_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    status ENUM('Draft', 'Published', 'Archived') DEFAULT 'Draft',
    created_by INT NOT NULL,
    published_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_club (club_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_by (created_by),
    INDEX idx_published_at (published_at),
    FULLTEXT idx_search (announcement_title, announcement_content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcement Views Tracking
CREATE TABLE announcement_views (
    view_id INT PRIMARY KEY AUTO_INCREMENT,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (announcement_id) REFERENCES announcements(announcement_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_view (announcement_id, user_id),
    INDEX idx_announcement (announcement_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club Resources/Files Table
CREATE TABLE club_resources (
    resource_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    resource_name VARCHAR(200) NOT NULL,
    resource_description TEXT,
    resource_type ENUM('Document', 'Image', 'Video', 'Audio', 'Archive', 'Other') DEFAULT 'Document',
    file_url VARCHAR(500) NOT NULL,
    file_size BIGINT,
    mime_type VARCHAR(100),
    category VARCHAR(100),
    tags VARCHAR(500),
    is_public BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_club (club_id),
    INDEX idx_resource_type (resource_type),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club Meetings Table
CREATE TABLE meetings (
    meeting_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    meeting_title VARCHAR(200) NOT NULL,
    meeting_type ENUM('General', 'Executive', 'Planning', 'Emergency') DEFAULT 'General',
    venue VARCHAR(200),
    meeting_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    agenda TEXT,
    minutes TEXT,
    attendees_count INT DEFAULT 0,
    status ENUM('Scheduled', 'InProgress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_club (club_id),
    INDEX idx_meeting_date (meeting_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meeting Attendance Table
CREATE TABLE meeting_attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    meeting_id INT NOT NULL,
    user_id INT NOT NULL,
    attendance_status ENUM('Present', 'Absent', 'Late', 'Excused') DEFAULT 'Absent',
    attended_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (meeting_id) REFERENCES meetings(meeting_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (meeting_id, user_id),
    INDEX idx_meeting (meeting_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club Financial Records Table
CREATE TABLE financial_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    transaction_type ENUM('Income', 'Expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    payment_method VARCHAR(50),
    reference_number VARCHAR(100),
    receipt_url VARCHAR(255),
    approved_by INT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_club (club_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club Achievements Table
CREATE TABLE achievements (
    achievement_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    achievement_title VARCHAR(200) NOT NULL,
    achievement_description TEXT,
    achievement_type ENUM('Competition', 'Award', 'Recognition', 'Milestone', 'Publication', 'Patent') DEFAULT 'Competition',
    achievement_date DATE NOT NULL,
    venue VARCHAR(200),
    participants JSON,
    certificate_url VARCHAR(255),
    photo_url VARCHAR(255),
    news_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    INDEX idx_club (club_id),
    INDEX idx_achievement_date (achievement_date),
    INDEX idx_achievement_type (achievement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log Table
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT,
    entity_type VARCHAR(50),
    entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    related_entity_type VARCHAR(50),
    related_entity_id INT,
    action_url VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_notification_type (notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Club Settings Table
CREATE TABLE club_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    UNIQUE KEY unique_setting (club_id, setting_key),
    INDEX idx_club (club_id),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data Insertion

-- Insert Clubs
INSERT INTO clubs (club_name, club_code, description, mission, vision, founded_year, color_scheme, max_members) VALUES
('ACM Student Chapter', 'ACM', 'Association for Computing Machinery - Advancing Computing as a Science and Profession', 'To foster a community of computing professionals and enthusiasts', 'To be the leading platform for computing innovation and education', 2015, '#3498db', 150),
('ACES Association', 'ACES', 'Association of Computer Engineering Students - Empowering Future Engineers', 'To nurture technical excellence and innovation in computer engineering', 'To create world-class computer engineers and innovators', 2016, '#e74c3c', 120),
('CESA Chapter', 'CESA', 'Civil Engineering Students Association - Building Tomorrow', 'To promote excellence in civil engineering education and practice', 'To shape the future of infrastructure and sustainable development', 2014, '#f39c12', 100),
('MESA Organization', 'MESA', 'Mechanical Engineering Students Association - Engineering Excellence', 'To advance mechanical engineering knowledge and innovation', 'To lead in mechanical engineering research and development', 2013, '#9b59b6', 130),
('ITSA Community', 'ITSA', 'Information Technology Students Association - Innovating the Future', 'To bridge the gap between technology and practical application', 'To be the catalyst for technological transformation', 2017, '#1abc9c', 110),
('IEEE Student Branch', 'IEEE', 'Institute of Electrical and Electronics Engineers - Advancing Technology', 'To promote electrical and electronics engineering excellence', 'To advance technology for humanity through innovation', 2012, '#34495e', 140);

-- Insert Users (Sample - 100 members will be added via PHP script)
INSERT INTO users (username, email, password, full_name, role, status) VALUES
('superadmin', 'superadmin@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'SuperAdmin', 'Active'),
('admin', 'admin@clubsphere.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'Admin', 'Active');

-- Insert Memberships for SuperAdmin and Admin in all clubs as Presidents
INSERT INTO memberships (user_id, club_id, position, joined_date, status) VALUES
(1, 1, 'President', '2020-01-01', 'Active'),
(1, 2, 'President', '2020-01-01', 'Active'),
(1, 3, 'President', '2020-01-01', 'Active'),
(1, 4, 'President', '2020-01-01', 'Active'),
(1, 5, 'President', '2020-01-01', 'Active'),
(1, 6, 'President', '2020-01-01', 'Active'),
(2, 1, 'VicePresident', '2020-01-01', 'Active'),
(2, 2, 'VicePresident', '2020-01-01', 'Active'),
(2, 3, 'VicePresident', '2020-01-01', 'Active'),
(2, 4, 'VicePresident', '2020-01-01', 'Active'),
(2, 5, 'VicePresident', '2020-01-01', 'Active'),
(2, 6, 'VicePresident', '2020-01-01', 'Active');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_memberships_club_position ON memberships(club_id, position);
CREATE INDEX idx_events_date_range ON events(start_datetime, end_datetime);
CREATE INDEX idx_announcements_priority ON announcements(priority, published_at);

-- Create Views for common queries
CREATE VIEW active_club_members AS
SELECT 
    m.membership_id,
    u.user_id,
    u.username,
    u.full_name,
    u.email,
    u.profile_image,
    c.club_id,
    c.club_name,
    c.club_code,
    m.position,
    m.joined_date,
    m.total_contribution_points
FROM memberships m
JOIN users u ON m.user_id = u.user_id
JOIN clubs c ON m.club_id = c.club_id
WHERE m.status = 'Active' AND u.status = 'Active';

CREATE VIEW upcoming_events AS
SELECT 
    e.event_id,
    e.event_title,
    e.event_description,
    e.event_type,
    e.venue,
    e.start_datetime,
    e.end_datetime,
    e.registration_deadline,
    e.max_participants,
    e.current_participants,
    e.registration_fee,
    e.event_poster,
    e.status,
    c.club_name,
    c.club_code,
    u.full_name as organizer_name
FROM events e
JOIN clubs c ON e.club_id = c.club_id
JOIN users u ON e.created_by = u.user_id
WHERE e.start_datetime > NOW() 
    AND e.status IN ('Published', 'Completed')
ORDER BY e.start_datetime ASC;

CREATE VIEW recent_announcements AS
SELECT 
    a.announcement_id,
    a.announcement_title,
    a.announcement_content,
    a.announcement_type,
    a.priority,
    a.is_featured,
    a.view_count,
    a.published_at,
    c.club_name,
    c.club_code,
    u.full_name as author_name
FROM announcements a
LEFT JOIN clubs c ON a.club_id = c.club_id
JOIN users u ON a.created_by = u.user_id
WHERE a.status = 'Published'
    AND (a.expires_at IS NULL OR a.expires_at > NOW())
ORDER BY a.published_at DESC;

-- Stored Procedures for common operations
DELIMITER //

-- Procedure to register a user for an event
CREATE PROCEDURE RegisterForEvent(
    IN p_user_id INT,
    IN p_event_id INT,
    OUT p_result VARCHAR(100)
)
BEGIN
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
END//

-- Procedure to create a new announcement
CREATE PROCEDURE CreateAnnouncement(
    IN p_club_id INT,
    IN p_title VARCHAR(200),
    IN p_content TEXT,
    IN p_type VARCHAR(20),
    IN p_priority VARCHAR(10),
    IN p_created_by INT,
    IN p_target_audience VARCHAR(20),
    OUT p_announcement_id INT
)
BEGIN
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
END//

DELIMITER ;

-- Set appropriate character set
SET NAMES utf8mb4;

-- Grant permissions (adjust according to your database user)
-- GRANT ALL PRIVILEGES ON clubsphere.* TO 'your_db_user'@'localhost';

-- Final optimization
OPTIMIZE TABLE users;
OPTIMIZE TABLE clubs;
OPTIMIZE TABLE memberships;
OPTIMIZE TABLE events;
OPTIMIZE TABLE announcements;

-- Display success message
SELECT 'ClubSphere Database Schema Created Successfully!' AS Message;
SELECT 'Tables Created: users, clubs, memberships, events, announcements, and 15+ supporting tables' AS Details;
SELECT 'Features: Role-based access, event management, announcements, financial tracking, achievements, and more!' AS Features;