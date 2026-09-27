CREATE DATABASE IF NOT EXISTS buyunda_primary_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buyunda_primary_school;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ADMIN','TEACHER') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS school_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_name VARCHAR(150) NOT NULL,
    school_email VARCHAR(150) NOT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    address TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_rooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    class_teacher_id BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_class_rooms_name (name),
    KEY idx_class_rooms_teacher (class_teacher_id),
    CONSTRAINT fk_class_rooms_teacher
        FOREIGN KEY (class_teacher_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pupils (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reg_number VARCHAR(50) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    parent_email VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pupils_reg_number (reg_number),
    KEY idx_pupils_class_name (class_name),
    KEY idx_pupils_parent_email (parent_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    sign_in_time TIME NOT NULL,
    status ENUM('PRESENT','ABSENT','LATE') NOT NULL DEFAULT 'PRESENT',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_teacher_date (teacher_id, date),
    KEY idx_attendance_date (date),
    KEY idx_attendance_teacher (teacher_id),
    CONSTRAINT fk_attendance_teacher
        FOREIGN KEY (teacher_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pupil_id BIGINT UNSIGNED NOT NULL,
    term ENUM('TERM_1','TERM_2','TERM_3') NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    marks DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    comments TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_report_cards_pupil (pupil_id),
    KEY idx_report_cards_term_year (pupil_id, term, academic_year),
    KEY idx_report_cards_subject (subject),
    CONSTRAINT fk_report_cards_pupil
        FOREIGN KEY (pupil_id) REFERENCES pupils (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_subjects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_teacher_subjects_teacher (teacher_id),
    CONSTRAINT fk_teacher_subjects_teacher
        FOREIGN KEY (teacher_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient_email VARCHAR(150) NOT NULL,
    recipient_name VARCHAR(150) NOT NULL DEFAULT '',
    notification_type VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('SENT','FAILED','LOGGED') NOT NULL DEFAULT 'LOGGED',
    error_details TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_type (notification_type),
    KEY idx_notifications_status (status),
    KEY idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO school_settings (school_name, school_email, contact_phone, address)
VALUES
('Buyunda Primary School', 'admin@buyundaprimaryschool.org', '+256 700 000 000', 'Buyunda, Uganda')
ON DUPLICATE KEY UPDATE
school_name = VALUES(school_name),
school_email = VALUES(school_email),
contact_phone = VALUES(contact_phone),
address = VALUES(address);

SELECT 'Buyunda Primary School schema initialized successfully.' AS status;
