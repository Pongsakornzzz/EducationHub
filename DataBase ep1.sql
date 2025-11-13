DROP DATABASE IF EXISTS school_db;
CREATE DATABASE school_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE school_db;


-- ตารางผู้ใช้ระบบ (admin + student)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- เพิ่มผู้ดูแลระบบเริ่มต้น (admin)
INSERT INTO users (username, password, role)
VALUES ('admin', MD5('123456'), 'admin');


-- ตารางนักเรียน
CREATE TABLE students (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  student_code VARCHAR(20) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  date_of_birth DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางปีการศึกษา
CREATE TABLE academic_years (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  year_label VARCHAR(20) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเทอม
CREATE TABLE terms (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  academic_year_id BIGINT NOT NULL,
  term_name ENUM('1','2','Summer') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);

-- ตารางวิชา
CREATE TABLE subjects (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  subject_code VARCHAR(50) NOT NULL UNIQUE,
  subject_name VARCHAR(255) NOT NULL,
  credits DECIMAL(4,1) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางเก็บคะแนน + เกรด
CREATE TABLE enrollments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  student_id BIGINT NOT NULL,
  term_id BIGINT NOT NULL,
  subject_id BIGINT NOT NULL,
  score DECIMAL(5,2) NULL,

  letter_grade VARCHAR(2) GENERATED ALWAYS AS (
    CASE
      WHEN score IS NULL THEN NULL
      WHEN score >= 80 THEN 'A'
      WHEN score >= 75 THEN 'B+'
      WHEN score >= 70 THEN 'B'
      WHEN score >= 65 THEN 'C+'
      WHEN score >= 60 THEN 'C'
      WHEN score >= 55 THEN 'D+'
      WHEN score >= 50 THEN 'D'
      ELSE 'F'
    END
  ) STORED,

  grade_point DECIMAL(3,2) GENERATED ALWAYS AS (
    CASE
      WHEN score IS NULL THEN NULL
      WHEN score >= 80 THEN 4.00
      WHEN score >= 75 THEN 3.50
      WHEN score >= 70 THEN 3.00
      WHEN score >= 65 THEN 2.50
      WHEN score >= 60 THEN 2.00
      WHEN score >= 55 THEN 1.50
      WHEN score >= 50 THEN 1.00
      ELSE 0.00
    END
  ) STORED,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (term_id) REFERENCES terms(id),
  FOREIGN KEY (subject_id) REFERENCES subjects(id),
  UNIQUE KEY unique_score (student_id, term_id, subject_id)
);
CREATE VIEW v_enrollment_details AS
SELECT 
    e.id AS enrollment_id,
    s.student_code,
    s.first_name,
    s.last_name,
    ay.year_label,
    t.term_name,
    sb.subject_code,
    sb.subject_name,
    sb.credits,
    e.score,
    e.letter_grade,
    e.grade_point
FROM enrollments e
JOIN students s ON e.student_id = s.id
JOIN terms t ON e.term_id = t.id
JOIN academic_years ay ON t.academic_year_id = ay.id
JOIN subjects sb ON e.subject_id = sb.id;

CREATE VIEW v_term_gpa AS
SELECT
    student_code,
    year_label,
    term_name,
    ROUND(SUM(grade_point * credits) / SUM(credits), 2) AS term_gpa
FROM v_enrollment_details
GROUP BY student_code, year_label, term_name;
CREATE VIEW v_cumulative_gpa AS
SELECT
    student_code,
    ROUND(SUM(grade_point * credits) / SUM(credits), 2) AS cumulative_gpa
FROM v_enrollment_details
GROUP BY student_code;

INSERT INTO academic_years (year_label) VALUES
('2024/2025'),
('2025/2026');

INSERT INTO terms (academic_year_id, term_name) VALUES
(1, '1'),
(1, '2'),
(2, '1');

INSERT INTO subjects (subject_code, subject_name, credits) VALUES
('MTH101', 'Mathematics', 3.0),
('SCI101', 'Science', 2.0),
('ENG101', 'English', 2.0),
('THA101', 'Thai Language', 1.0);

INSERT INTO students (student_code, first_name, last_name, date_of_birth) VALUES
('S101', 'Thanawat', 'Somchai', '2008-03-15'),
('S102', 'Pimchanok', 'Sriwan', '2008-07-20');

INSERT INTO enrollments (student_id, term_id, subject_id, score) VALUES
(1, 1, 1, 85.50),
(1, 1, 2, 78.00),
(1, 1, 3, 92.00),
(1, 1, 4, 70.00),

(2, 1, 1, 65.00),
(2, 1, 2, 88.00),
(2, 1, 3, 74.00),
(2, 1, 4, 81.00);

SELECT * FROM users;
SELECT * FROM students;
SELECT * FROM v_term_gpa;
SELECT * FROM v_cumulative_gpa;
 
INSERT INTO users (username, password, role)
VALUES ('student01', MD5('123456'), 'student');

SELECT * FROM users;

ALTER TABLE students
ADD profile_image VARCHAR(255) NULL AFTER date_of_birth;

ALTER TABLE users
ADD student_code VARCHAR(20) NULL AFTER username;

ALTER TABLE students
ADD email VARCHAR(150) NULL AFTER last_name,
ADD phone VARCHAR(20) NULL AFTER email;

CREATE TABLE uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

