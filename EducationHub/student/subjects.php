<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// ดึง student_id จาก users
$userQuery = $conn->query("SELECT student_code FROM users WHERE username='$username'");
$user = $userQuery->fetch_assoc();
$student_code = $user['student_code'];

$studentQuery = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code='$student_code'");
$student = $studentQuery->fetch_assoc();
$student_id = $student['id'];
$full_name = $student['first_name'] . " " . $student['last_name'];

// ดึงข้อมูลรายวิชาทั้งหมดของนักเรียน
$sql = "
    SELECT 
        s.subject_code,
        s.subject_name,
        s.credits,
        ROUND(AVG(e.score),2) AS avg_score,
        ROUND(AVG(e.grade_point),2) AS avg_point,
        CASE 
            WHEN AVG(e.grade_point) >= 4.00 THEN 'A'
            WHEN AVG(e.grade_point) >= 3.50 THEN 'B+'
            WHEN AVG(e.grade_point) >= 3.00 THEN 'B'
            WHEN AVG(e.grade_point) >= 2.50 THEN 'C+'
            WHEN AVG(e.grade_point) >= 2.00 THEN 'C'
            WHEN AVG(e.grade_point) >= 1.50 THEN 'D+'
            WHEN AVG(e.grade_point) >= 1.00 THEN 'D'
            ELSE 'F'
        END AS letter_grade
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    WHERE e.student_id = '$student_id'
    GROUP BY s.subject_code, s.subject_name, s.credits
    ORDER BY s.subject_code ASC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>📘 เกรดเฉลี่ยรายวิชา | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Kanit', sans-serif; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      text-align: center;
    }
    th {
      background-color: #2563eb;
      color: white;
    }
    tr:hover {
      background-color: #f1f5f9;
    }
  </style>
</head>
<body>

<div class="app">
  <aside class="sidebar">
    <div class="brand">EDUCATION HUB</div>
    <ul class="menu">
      <li><a href="index.php">🏠 หน้าหลัก</a></li>
      <li><a href="scores.php">📚 ผลการเรียน</a></li>
      <li><a href="gpa.php">📊 GPA / GPAX</a></li>
      <li><a href="subjects.php" class="active">📘 เกรดเฉลี่ยรายวิชา</a></li>
      <li><a href="upload.php">📤 อัปโหลดผลการเรียน</a></li>
    </ul>
  </aside>

  <main class="content">
    <h2>📘 เกรดเฉลี่ยรายวิชา</h2>
    <p>👩‍🎓 นักเรียน: <?= htmlspecialchars($full_name) ?></p>

    <table>
      <thead>
        <tr>
          <th>รหัสวิชา</th>
          <th>ชื่อวิชา</th>
          <th>หน่วยกิต</th>
          <th>คะแนนเฉลี่ย</th>
          <th>เกรดเฉลี่ย</th>
          <th>เกรด</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['subject_code'] ?></td>
              <td><?= $row['subject_name'] ?></td>
              <td><?= $row['credits'] ?></td>
              <td><?= $row['avg_score'] ?></td>
              <td><?= $row['avg_point'] ?></td>
              <td><?= $row['letter_grade'] ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6">📭 ไม่มีข้อมูลรายวิชา</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>
</body>
</html>
