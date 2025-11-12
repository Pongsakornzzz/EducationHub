<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$error = "";
$success = "";

// เปลี่ยนรหัสผ่าน
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = MD5($_POST["old_password"]);
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    // ตรวจสอบรหัสผ่านเดิม
    $check = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$old_password'");
    if ($check->num_rows === 0) {
        $error = "❌ รหัสผ่านเดิมไม่ถูกต้อง";
    } elseif ($new_password !== $confirm_password) {
        $error = "❌ รหัสผ่านใหม่ไม่ตรงกัน";
    } elseif (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password)) {
        $error = "⚠️ รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวและมีตัวเลข";
    } else {
        // อัปเดตรหัสผ่านใหม่
        $new_password_md5 = MD5($new_password);
        $conn->query("UPDATE users SET password='$new_password_md5' WHERE username='$username'");
        $success = "✅ เปลี่ยนรหัสผ่านสำเร็จแล้ว!";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เปลี่ยนรหัสผ่าน | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    .form-card {
      max-width: 450px;
      margin: 30px auto;
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .form-card h2 {
      margin-top: 0;
      color: #2563eb;
      text-align: center;
    }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: 500; }
    input[type="password"] {
      width: 100%; padding: 10px; border: 1px solid #ccc;
      border-radius: 6px;
    }
    .btn-back {
      background: #6b7280;
      margin-right: 10px;
    }
  </style>
</head>
<body>

<div class="app">
  <main class="content">
    <div class="form-card">
      <h2>🔒 เปลี่ยนรหัสผ่าน</h2>
      <form method="POST">
        <div class="form-group">
          <label>รหัสผ่านเดิม</label>
          <input type="password" name="old_password" required>
        </div>
        <div class="form-group">
          <label>รหัสผ่านใหม่</label>
          <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
          <label>ยืนยันรหัสผ่านใหม่</label>
          <input type="password" name="confirm_password" required>
        </div>

        <?php if ($error): ?>
          <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
          <p class="success"><?= $success ?></p>
        <?php endif; ?>

        <button type="submit" class="btn">ยืนยันเปลี่ยนรหัสผ่าน</button>
        <a href="profile.php" class="btn btn-back">← กลับโปรไฟล์</a>
      </form>
    </div>
  </main>
</div>

</body>
</html>
