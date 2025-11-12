<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// ดึงข้อมูลนักเรียน
$userQuery = $conn->query("SELECT student_code FROM users WHERE username='$username'");
$user = $userQuery->fetch_assoc();
$student_code = $user['student_code'];

$studentQuery = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code='$student_code'");
$student = $studentQuery->fetch_assoc();
$student_id = $student['id'];
$full_name = $student['first_name'] . " " . $student['last_name'];

$message = "";
$error = "";

// 📥 เมื่อมีการอัปโหลด
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['transcript'])) {
    $file = $_FILES['transcript'];
    $allowed = ['pdf', 'xlsx', 'xls'];

    if ($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $newName = "transcript_" . $student_code . "_" . time() . "." . $ext;
            $uploadDir = "../uploads/";

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filePath = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $conn->query("INSERT INTO uploads (student_id, file_name) VALUES ('$student_id', '$newName')");
                $message = "✅ อัปโหลดสำเร็จ! ระบบจะตรวจสอบไฟล์ของคุณเร็ว ๆ นี้";
            } else {
                $error = "❌ เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            }
        } else {
            $error = "❌ รองรับเฉพาะไฟล์ PDF หรือ Excel เท่านั้น";
        }
    } else {
        $error = "❌ กรุณาเลือกไฟล์ก่อนอัปโหลด";
    }
}

// 📄 ดึงไฟล์ล่าสุดของนักเรียน
$fileQuery = $conn->query("
    SELECT file_name, upload_date, status
    FROM uploads
    WHERE student_id='$student_id'
    ORDER BY upload_date DESC
    LIMIT 1
");
$file = $fileQuery->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>อัปโหลดผลการเรียน | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    .upload-box {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      max-width: 500px;
      margin: 0 auto;
      text-align: center;
    }
    .upload-box input[type=file] {
      display: block;
      margin: 15px auto;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      width: 90%;
    }
    .upload-box button {
      background: #2563eb;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
    }
    .upload-box button:hover { background: #1e4ed8; }
    .file-info {
      margin-top: 20px;
      background: #f3f4f6;
      padding: 15px;
      border-radius: 8px;
      text-align: left;
    }
    .success { color: green; margin-top: 15px; }
    .error { color: red; margin-top: 15px; }
  </style>
</head>
<body>

<div class="app">
  <aside class="sidebar">
    <div class="brand">EDUCATION HUB</div>
    <ul class="menu">
      <li><a href="index.php"><i data-feather="home"></i> หน้าหลัก 🏠</a></li>
      <li><a href="profile.php"><i data-feather="user"></i> ข้อมูลส่วนตัว 👤</a></li>
      <li><a href="scores.php"><i data-feather="book-open"></i> ผลการเรียน 📚</a></li>
      <li><a href="gpa.php"><i data-feather="bar-chart-2"></i> GPA / GPAX 📊</a></li>
      <li><a href="upload.php" class="active"><i data-feather="upload"></i> อัปโหลดผลการเรียน 📥</a></li>
    </ul>
  </aside>

  <main class="content">
    <header class="top-header">
      <h2>📥 อัปโหลดผลการเรียน</h2>
      <span>👋 <?php echo htmlspecialchars($full_name); ?></span>
    </header>

    <div class="upload-box">
      <form method="POST" enctype="multipart/form-data">
        <p>เลือกรายงานผลการเรียน (PDF หรือ Excel)</p>
        <input type="file" name="transcript" accept=".pdf,.xlsx,.xls" required>
        <button type="submit">อัปโหลดไฟล์</button>
      </form>

      <?php if ($message): ?><p class="success"><?= $message ?></p><?php endif; ?>
      <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>

      <?php if ($file): ?>
      <div class="file-info">
        <h4>📂 ไฟล์ล่าสุดของคุณ</h4>
        <p><b>ชื่อไฟล์:</b> <?= $file['file_name'] ?></p>
        <p><b>วันที่อัปโหลด:</b> <?= $file['upload_date'] ?></p>
        <p><b>สถานะ:</b>
          <?php
            if ($file['status'] === 'approved') echo "✅ อนุมัติแล้ว";
            elseif ($file['status'] === 'rejected') echo "❌ ถูกปฏิเสธ";
            else echo "⏳ รอตรวจสอบ";
          ?>
        </p>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>feather.replace();</script>
</body>
</html>
