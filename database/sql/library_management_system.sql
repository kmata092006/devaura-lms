-- Create and Use Database for library_management_system
DROP DATABASE IF EXISTS library_management_system;
CREATE DATABASE library_management_system;
USE library_management_system;

-- DEPARTMENT TABLE
CREATE TABLE department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
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
    personnel_role VARCHAR(20) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    department_id INT,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    `m.i.` VARCHAR(3),
    shift_day VARCHAR(20),
    shift_period VARCHAR(20)
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

-- DEFAULT LIBRARY PERSONNEL ACCOUNTS
-- Password for all default users: devaura
INSERT INTO library_personnel (
    username,
    password_hash,
    personnel_role,
    first_name,
    last_name
) VALUES
(
    'admin',
    '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2',
    'admin',
    'System',
    'Admin'
),
(
    'staff',
    '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2',
    'staff',
    'Library',
    'Staff'
),
(
    'student staff',
    '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2',
    'student staff',
    'Student',
    'Assistant'
);