<?php
session_start();
include "db_connect.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_code = trim($_POST['student_code']);
    $first_name   = trim($_POST['first_name']);
    $last_name    = trim($_POST['last_name']);
    $username     = trim($_POST['username']);
    $password     = trim($_POST['password']);
    $confirm      = trim($_POST['confirm']);

    // ตรวจสอบรหัสผ่านซ้ำ
    if ($password !== $confirm) {
        $error = "❌ รหัสผ่านไม่ตรงกัน";
    } else {
        // ตรวจสอบ username ซ้ำ
        $check_user = $conn->query("SELECT * FROM users WHERE username = '$username'");
        if ($check_user->num_rows > 0) {
            $error = "❌ ชื่อผู้ใช้นี้ถูกใช้แล้ว";
        } else {
            // บันทึกข้อมูลลงตาราง students และ users
            $conn->query("INSERT INTO students (student_code, first_name, last_name) 
                          VALUES ('$student_code', '$first_name', '$last_name')");
            $conn->query("INSERT INTO users (username, student_code, password, role) 
                          VALUES ('$username', '$student_code', MD5('$password'), 'student')");
            $success = "✅ สมัครสมาชิกสำเร็จ! <a href='login.php'>เข้าสู่ระบบ</a>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก | Education Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="center-box">
    <h2>สมัครสมาชิก</h2>
    <p style="color:#555; margin-bottom:15px;">สร้างบัญชีเพื่อเข้าระบบ Education Hub</p>

    <form method="POST">
        <input type="text" name="student_code" placeholder="รหัสนักเรียน" required>
        <input type="text" name="first_name" placeholder="ชื่อจริง" required>
        <input type="text" name="last_name" placeholder="นามสกุล" required>
        <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
        <input type="password" name="password" placeholder="รหัสผ่าน" required>
        <input type="password" name="confirm" placeholder="ยืนยันรหัสผ่าน" required>
        <button type="submit">สมัครสมาชิก</button>
    </form>

    <?php if (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <p style="margin-top: 12px;">
        มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a>
    </p>
</div>

</body>
</html>
