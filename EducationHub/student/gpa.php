<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

$userRes = $conn->query("SELECT student_code FROM users WHERE username='$username'");
$user = $userRes->fetch_assoc();
$student_code = $user['student_code'];

$stuRes = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code='$student_code'");
$student = $stuRes->fetch_assoc();
$student_id = $student['id'];
$full_name = $student['first_name'] . " " . $student['last_name'];

// ดึง GPA ต่อเทอม
$gpaResult = $conn->query("
    SELECT 
        ay.year_label AS academic_year,
        t.term_name,
        ROUND(SUM(e.grade_point * s.credits) / SUM(s.credits), 2) AS gpa
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    JOIN terms t ON e.term_id = t.id
    JOIN academic_years ay ON t.academic_year_id = ay.id
    WHERE e.student_id = '$student_id'
    GROUP BY ay.year_label, t.term_name
    ORDER BY ay.year_label DESC, t.term_name ASC
");

// ดึง GPAX (เฉลี่ยสะสม)
$gpaxResult = $conn->query("
    SELECT 
        ROUND(SUM(e.grade_point * s.credits) / SUM(s.credits), 2) AS gpax
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    WHERE e.student_id = '$student_id'
");
$gpax = $gpaxResult->fetch_assoc()['gpax'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>GPA / GPAX | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">
  <aside class="sidebar">
    <div class="brand">EDUCATION HUB</div>
    <ul class="menu">
      <li><a href="index.php">🏠 หน้าหลัก</a></li>
      <li><a href="scores.php">📚 ผลการเรียน</a></li>
      <li><a href="gpa.php" class="active">📊 GPA / GPAX</a></li>
      <li><a href="subjects.php">📘 รายวิชา</a></li>
    </ul>
  </aside>

  <main class="content">
    <h2>📊 เกรดเฉลี่ย GPA / GPAX</h2>
    <p>👩‍🎓 <?php echo htmlspecialchars($full_name); ?></p>

    <table class="table">
      <thead>
        <tr>
          <th>ปีการศึกษา</th>
          <th>ภาคเรียน</th>
          <th>เกรดเฉลี่ย (GPA)</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($gpaResult->num_rows > 0): ?>
          <?php while($row = $gpaResult->fetch_assoc()): ?>
          <tr>
            <td><?= $row['academic_year'] ?></td>
            <td><?= $row['term_name'] ?></td>
            <td><?= $row['gpa'] ?></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="3">ไม่มีข้อมูลเกรดเฉลี่ย</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <h3>🎓 GPAX (เฉลี่ยสะสม): <?= $gpax ?></h3>
  </main>
</div>
</body>
</html>
