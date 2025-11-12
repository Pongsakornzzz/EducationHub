<?php
/**
 * Education Hub – Initial Database Setup
 * --------------------------------------
 * รันไฟล์นี้ครั้งเดียวเพื่อสร้างฐานข้อมูลและตารางสำคัญ
 */

$host = "localhost";
$user = "root";
$pass = "Ds.49681";
$dbname = "school_db";

// STEP 1: Connect to MySQL (ไม่เจาะจงชื่อ DB)
$conn = new mysqli($host, $user, $pass);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}

// STEP 2: Create Database if not exists
if (!$conn->select_db($dbname)) {
    $sql = "CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "✅ Database '$dbname' created successfully.<br>";
        $conn->select_db($dbname);
    } else {
        die("❌ Cannot create database: " . $conn->error);
    }
} else {
    echo "ℹ️ Database '$dbname' already exists.<br>";
}

// STEP 3: Create tables
$tables = [
"users" => "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('student','admin') DEFAULT 'student',
        student_code VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
",
"students" => "
    CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_code VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
",
"subjects" => "
    CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_code VARCHAR(20) UNIQUE,
        subject_name VARCHAR(100),
        credits INT DEFAULT 3
    ) ENGINE=InnoDB;
",
"enrollments" => "
    CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        term_id VARCHAR(10),
        score DECIMAL(5,2),
        letter_grade VARCHAR(2),
        grade_point DECIMAL(3,2),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
"
];

// STEP 4: Execute each table creation
foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "✅ Table '$name' ready.<br>";
    } else {
        echo "❌ Error creating '$name': " . $conn->error . "<br>";
    }
}

// STEP 5: Create default admin (if not exists)
$adminCheck = $conn->query("SELECT * FROM users WHERE username='admin' LIMIT 1");
if ($adminCheck->num_rows === 0) {
    $password = md5('admin123');
    $conn->query("INSERT INTO users (username, password, role) VALUES ('admin', '$password', 'admin')");
    echo "✅ Default admin created (username: admin / password: admin123)<br>";
} else {
    echo "ℹ️ Admin account already exists.<br>";
}

echo "<hr>🎉 Setup complete!<br><a href='login.php'>👉 ไปหน้าเข้าสู่ระบบ</a>";

$conn->close();
?>
