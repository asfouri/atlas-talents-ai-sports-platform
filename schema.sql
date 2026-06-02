-- Atlas Talents Database Schema
-- Production-safe schema only. Optional demo data lives in seed_demo.sql.

CREATE DATABASE IF NOT EXISTS atlas_talents CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE atlas_talents;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(180) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('teacher','student','manager','recruiter','coach','admin') NOT NULL,
    club       VARCHAR(120),
    ville      VARCHAR(80),
    phone      VARCHAR(20),
    avatar_url VARCHAR(255),
    student_id INT NULL,
    is_active  TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    name       VARCHAR(120) NOT NULL,
    age        TINYINT,
    ville      VARCHAR(80),
    sport      VARCHAR(80),
    school     VARCHAR(120),
    avatar_url VARCHAR(255),
    is_active  TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS videos (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    student_id           INT NOT NULL,
    teacher_id           INT NOT NULL,
    filename             VARCHAR(255),
    storage_path         VARCHAR(255),
    original_name        VARCHAR(255),
    mime_type            VARCHAR(120),
    file_size            INT,
    duration_seconds     DECIMAL(8,2),
    frame_count          TINYINT,
    perf_type            VARCHAR(80),
    vitesse              TINYINT,
    coordination         TINYINT,
    endurance            TINYINT,
    `force`              TINYINT,
    souplesse            TINYINT,
    score_global         TINYINT,
    analysis_provider    VARCHAR(40),
    ai_confidence        TINYINT,
    ai_summary           TEXT,
    ai_strengths         TEXT,
    ai_improvements      TEXT,
    ai_recommendations   TEXT,
    recruiter_highlights TEXT,
    analysis_payload     LONGTEXT,
    analysis_error       TEXT,
    ai_status            ENUM('pending','processing','done','failed') DEFAULT 'pending',
    analyzed_at          DATETIME,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS favorites (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    recruiter_id INT NOT NULL,
    student_id   INT NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (recruiter_id, student_id),
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS coach_students (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    coach_id   INT NOT NULL,
    student_id INT NOT NULL,
    start_date DATE,
    notes      TEXT,
    FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sender_id    INT NOT NULL,
    recipient_id INT NOT NULL,
    student_id   INT NULL,
    body         TEXT NOT NULL,
    is_read      TINYINT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_sender (sender_id, created_at),
    INDEX idx_messages_recipient (recipient_id, created_at),
    INDEX idx_messages_pair (sender_id, recipient_id, created_at),
    INDEX idx_messages_student (student_id),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    type       VARCHAR(40),
    message    TEXT,
    is_read    TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
