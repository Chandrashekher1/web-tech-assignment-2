-- ============================================
-- University Result Management System
-- Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS ResultManagement;
USE ResultManagement;

-- ============================================
-- Users Table (Admin + Students)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID VARCHAR(50) UNIQUE NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) DEFAULT NULL,
    Password VARCHAR(255) NOT NULL,
    Role ENUM('admin', 'student') DEFAULT 'student',
    Class VARCHAR(50) DEFAULT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Semesters Table
-- ============================================
CREATE TABLE IF NOT EXISTS semesters (
    SemesterID INT AUTO_INCREMENT PRIMARY KEY,
    SemesterName VARCHAR(50) NOT NULL UNIQUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Results Table
-- Subjects stored as JSON for dynamic subject support
-- ============================================
CREATE TABLE IF NOT EXISTS results (
    ResultID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID VARCHAR(50) NOT NULL,
    Name VARCHAR(100) NOT NULL,
    SemesterID INT NOT NULL,
    Subjects JSON NOT NULL COMMENT 'JSON object: {"Subject1": 85, "Subject2": 90, ...}',
    TotalMarks DECIMAL(6,2) NOT NULL,
    Percentage DECIMAL(5,2) NOT NULL,
    Class VARCHAR(50) DEFAULT NULL COMMENT 'First Class, Second Class, etc.',
    UploadedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SemesterID) REFERENCES semesters(SemesterID) ON DELETE CASCADE,
    INDEX idx_student (StudentID),
    INDEX idx_semester (SemesterID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Seed Data
-- ============================================

-- Default semesters
INSERT INTO semesters (SemesterName) VALUES
    ('Semester 1'),
    ('Semester 2'),
    ('Semester 3'),
    ('Semester 4'),
    ('Semester 5'),
    ('Semester 6'),
    ('Semester 7'),
    ('Semester 8');

-- Default admin account (password: admin123)
INSERT INTO users (StudentID, Name, Email, Password, Role) VALUES
    ('ADMIN001', 'System Administrator', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample student accounts (password: student123 for all)
INSERT INTO users (StudentID, Name, Email, Password, Role, Class) VALUES
    ('STU001', 'Aarav Sharma', 'aarav@student.edu', '$2y$10$YMjQ5J7dN0eVw8bGxXKz8eM1qF3mW5pO2rZ0vL8aT4cB6dH9iK1jG', 'student', 'CS-A'),
    ('STU002', 'Priya Patel', 'priya@student.edu', '$2y$10$YMjQ5J7dN0eVw8bGxXKz8eM1qF3mW5pO2rZ0vL8aT4cB6dH9iK1jG', 'student', 'CS-A'),
    ('STU003', 'Rohan Gupta', 'rohan@student.edu', '$2y$10$YMjQ5J7dN0eVw8bGxXKz8eM1qF3mW5pO2rZ0vL8aT4cB6dH9iK1jG', 'student', 'CS-A'),
    ('STU004', 'Ananya Reddy', 'ananya@student.edu', '$2y$10$YMjQ5J7dN0eVw8bGxXKz8eM1qF3mW5pO2rZ0vL8aT4cB6dH9iK1jG', 'student', 'CS-B'),
    ('STU005', 'Vikram Singh', 'vikram@student.edu', '$2y$10$YMjQ5J7dN0eVw8bGxXKz8eM1qF3mW5pO2rZ0vL8aT4cB6dH9iK1jG', 'student', 'CS-B');

-- Sample results for testing
INSERT INTO results (StudentID, Name, SemesterID, Subjects, TotalMarks, Percentage, Class) VALUES
    ('STU001', 'Aarav Sharma', 1, '{"Mathematics": 92, "Physics": 88, "Chemistry": 85, "English": 90, "Computer Science": 95}', 450, 90.00, 'Distinction'),
    ('STU002', 'Priya Patel', 1, '{"Mathematics": 78, "Physics": 82, "Chemistry": 75, "English": 88, "Computer Science": 80}', 403, 80.60, 'First Class'),
    ('STU003', 'Rohan Gupta', 1, '{"Mathematics": 65, "Physics": 70, "Chemistry": 60, "English": 72, "Computer Science": 68}', 335, 67.00, 'Second Class'),
    ('STU004', 'Ananya Reddy', 1, '{"Mathematics": 95, "Physics": 91, "Chemistry": 93, "English": 89, "Computer Science": 97}', 465, 93.00, 'Distinction'),
    ('STU005', 'Vikram Singh', 1, '{"Mathematics": 55, "Physics": 60, "Chemistry": 58, "English": 62, "Computer Science": 50}', 285, 57.00, 'Pass Class');
