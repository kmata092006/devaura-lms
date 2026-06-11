-- Create and Use Database for library_management_system
DROP DATABASE IF EXISTS library_management_system;
CREATE DATABASE library_management_system;
USE library_management_system;

-- DEPARTMENT TABLE
CREATE TABLE department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
);

-- PERSONNEL ROLE
CREATE TABLE personnel_role (
    personnel_role_id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_role_name VARCHAR(100) NOT NULL UNIQUE
);

-- PERMISSION TABLE
CREATE TABLE permission (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE
);

-- ROLE PERMISSION JUNCTION TABLE
CREATE TABLE role_permission (
    personnel_role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (personnel_role_id, permission_id),
    FOREIGN KEY (personnel_role_id) REFERENCES personnel_role(personnel_role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permission(permission_id) ON DELETE CASCADE
);

-- VISITOR TABLE
CREATE TABLE library_visitor (
    visitor_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    `m.i.` VARCHAR(3)
);

-- LIBRARY MEMBER TABLE
CREATE TABLE library_member (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    member_role VARCHAR(20) NOT NULL,
    reference_number VARCHAR(50) NOT NULL UNIQUE,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    `m.i.` VARCHAR(3)
);

-- BOOK GENRE TABLE
CREATE TABLE book_genre (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(100) NOT NULL
);

-- BOOK AUTHOR TABLE
CREATE TABLE book_author (
    author_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50),
    first_name VARCHAR(50),
    `m.i.` VARCHAR(3)
);

-- BOOK PUBLISHER TABLE
CREATE TABLE book_publisher (
    publisher_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50),
    first_name VARCHAR(50),
    `m.i.` VARCHAR(3),
    date_published DATE
);

-- LIBRARY PERSONNEL TABLE
CREATE TABLE library_personnel (
    personnel_id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_role_id INT NOT NULL,
    department_id INT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    `m.i.` VARCHAR(2),
    shift_day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
    shift_period ENUM('Morning', 'Afternoon'),
    FOREIGN KEY (personnel_role_id) REFERENCES personnel_role(personnel_role_id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES department(department_id) ON DELETE SET NULL
);

-- BOOK TABLE
CREATE TABLE book (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT,
    publisher_id INT,
    genre_id INT,
    isbn VARCHAR(13),
    book_title VARCHAR(255) NOT NULL,
    book_status VARCHAR(30) NOT NULL DEFAULT 'Available'
);

-- ATTENDANCE LOG TABLE
CREATE TABLE attendance_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(20) NOT NULL,
    personnel_id INT,
    member_id INT,
    visitor_id INT,
    log_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    time_in TIME NOT NULL DEFAULT (CURRENT_TIME),
    time_out TIME
);

-- BORROW TRANSACTION TABLE
CREATE TABLE borrow_transaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    return_date DATE,
    due_date DATE NOT NULL,
    borrow_status VARCHAR(30) NOT NULL DEFAULT 'Borrowed'
);

-- AUDIT TRAIL TABLE
CREATE TABLE audit_trail (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT,
    username VARCHAR(50),
    personnel_role VARCHAR(20),
    action_type VARCHAR(50) NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    action_details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- DEFAULT LIBRARY PERSONNEL ACCOUNTS
-- Password for all default users: devaura
-- 1. Seed Roles
INSERT INTO personnel_role (personnel_role_id, personnel_role_name) VALUES 
(1, 'admin'), 
(2, 'staff'), 
(3, 'student staff');

-- 2. Seed Default Accounts
INSERT INTO library_personnel (username, password_hash, personnel_role_id, first_name, last_name) 
VALUES
('admin', '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2', 1, 'System', 'Admin'),
('staff', '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2', 2, 'Library', 'Staff'),
('student staff', '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2', 3, 'Student', 'Assistant');