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
    member_role VARCHAR(20),
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
    last_name VARCHAR(50) NOT NULL, 
    first_name VARCHAR(50) NOT NULL,
    `m.i.` VARCHAR(3)
);

-- BOOK PUBLISHER TABLE
CREATE TABLE book_publisher (
    publisher_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL, 
    `m.i.` VARCHAR(3),
    date_published DATE
);

-- LIBRARY PERSONNEL TABLE
CREATE TABLE library_personnel (
    personnel_id INT AUTO_INCREMENT PRIMARY KEY, 
    personnel_role VARCHAR(20),
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    department_id INT, 
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL, 
    `m.i.` VARCHAR(3),
    shift_day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
    shift_period ENUM('Morning', 'Afternoon'),

    CONSTRAINT fk_personnel_department
        FOREIGN KEY (department_id)
        REFERENCES department (department_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL 
);

-- BOOK TABLE
CREATE TABLE book (
    book_id INT AUTO_INCREMENT PRIMARY KEY, 
    author_id INT, 
    publisher_id INT, 
    genre_id INT, 
    isbn VARCHAR(13),
    book_title VARCHAR(255) NOT NULL, 
    book_status ENUM('Available', 'Borrowed', 'Reserved', 'Lost', 'Damaged', 'Under Repair') NOT NULL DEFAULT 'Available', 

    CONSTRAINT fk_book_author
        FOREIGN KEY (author_id)
        REFERENCES book_author (author_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_book_publisher
        FOREIGN KEY (publisher_id)
        REFERENCES book_publisher (publisher_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_book_genre
        FOREIGN KEY (genre_id)
        REFERENCES book_genre (genre_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
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
    time_out TIME,

    CONSTRAINT fk_log_personnel
        FOREIGN KEY (personnel_id)
        REFERENCES library_personnel (personnel_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_log_member
        FOREIGN KEY (member_id)
        REFERENCES library_member (member_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_log_visitor
        FOREIGN KEY (visitor_id)
        REFERENCES library_visitor (visitor_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- BORROW TRANSACTION TABLE
CREATE TABLE borrow_transaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY, 
    member_id INT, 
    book_id INT, 
    borrow_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    return_date DATE,
    due_date DATE NOT NULL,
    borrow_status ENUM('Borrowed', 'Returned', 'Overdue', 'Lost') NOT NULL DEFAULT 'Borrowed',

    CONSTRAINT fk_borrow_member
        FOREIGN KEY (member_id)
        REFERENCES library_member (member_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_borrow_book
        FOREIGN KEY (book_id)
        REFERENCES book (book_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    
    CONSTRAINT check_due_date
        CHECK (due_date >= borrow_date)
);

INSERT INTO library_personnel (username, password_hash, personnel_role, first_name, last_name) VALUES 
('admin', '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2', 'admin', 'System', 'Admin'),
('staff', '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2', 'staff', 'Library', 'Staff'),
('student staff', '$2y$10$p52bg0H4GBDR2Z.ve6jb/umrcgLhkWAl/coNYM7Eyl6H37vP/AoU2', 'student staff', 'Student', 'Assistant');