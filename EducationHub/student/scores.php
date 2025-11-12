<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// 🧭 ดึง student_id
$userQuery = $conn->query("SELECT student_code FROM users WHERE username='$username'");
$user = $userQuery->fetch_assoc();
$student_code = $user['student_code'];

$studentQuery = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code='$student_code'");
$student = $studentQuery->fetch_assoc();
$student_id = $student['id'];
$full_name = $student['first_name'] . " " . $student['last_name'];

// 📅 รับตัวกรองเทอม / ปี
$term_filter = $_GET['term'] ?? '';
$year_filter = $_GET['year'] ?? '';

// 🧩 ดึงปี/เทอมทั้งหมด (จาก enrollment ที่มีจริง)
$term_query = "
    SELECT DISTINCT 
        ay.year_label AS year_label,
        t.term_name
    FROM enrollments e
    JOIN terms t ON e.term_id = t.id
    JOIN academic_years ay ON t.academic_year_id = ay.id
    WHERE e.student_id = '$student_id'
    ORDER BY ay.year_label DESC, t.term_name ASC
";
$terms = $conn->query($term_query);

// 🧮 Query รายวิชา
$sql = "
    SELECT 
        ay.year_label AS academic_year,
        t.term_name,
        s.subject_code,
        s.subject_name,
        s.credits,
        e.score,
        e.letter_grade
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    JOIN terms t ON e.term_id = t.id
    JOIN academic_years ay ON t.academic_year_id = ay.id
    WHERE e.student_id = '$student_id'
";

// ✅ เพิ่มตัวกรองแบบถูกต้อง
if ($year_filter) $sql .= " AND ay.year_label = '$year_filter'";
if ($term_filter) $sql .= " AND t.term_name = '$term_filter'";

$sql .= " ORDER BY ay.year_label DESC, t.term_name ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ผลการเรียน | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 15px;
      align-items: center;
    }
    select, input[type="text"] {
      padding: 8px 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-family: 'Kanit', sans-serif;
    }
    .filter-bar button {
      background-color: #2563eb;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: 0.3s;
    }
    .filter-bar button:hover { background-color: #1e4ed8; }
    .btn-outline {
      background: none;
      border: 1px solid #2563eb;
      color: #2563eb;
      padding: 7px 14px;
      border-radius: 6px;
      text-decoration: none;
      transition: 0.3s;
    }
    .btn-outline:hover {
      background-color: #2563eb;
      color: white;
    }
  </style>
</head>
<body>

<div class="app">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">EDUCATION HUB</div>
    <ul class="menu">
      <li><a href="index.php"><i data-feather="home"></i> หน้าหลัก 🏠</a></li>
      <li><a href="profile.php"><i data-feather="user"></i> ข้อมูลส่วนตัว 👤</a></li>
      <li><a href="scores.php" class="active"><i data-feather="book-open"></i> ผลการเรียน 📚</a></li>
      <li><a href="gpa.php"><i data-feather="bar-chart-2"></i> GPA / GPAX 📊</a></li>
      <li><a href="subjects.php"><i data-feather="layers"></i> เกรดเฉลี่ยรายวิชา 📘</a></li>
      <li><a href="upload.php"><i data-feather="upload"></i> อัปโหลดผลการเรียน 📥</a></li>
    </ul>
  </aside>

  <!-- Main -->
  <main class="content">
    <header class="top-header">
      <h2>📚 ผลการเรียน</h2>
      <span>👋 <?= htmlspecialchars($full_name) ?></span>
    </header>

    <!-- Filter -->
    <div class="filter-bar">
      <form method="GET">
        <select name="year">
          <option value="">-- เลือกปีการศึกษา --</option>
          <?php while($row = $terms->fetch_assoc()): ?>
            <option value="<?= $row['year_label'] ?>" <?= $year_filter == $row['year_label'] ? 'selected' : '' ?>>
              <?= $row['year_label'] ?>
            </option>
          <?php endwhile; ?>
        </select>
        <select name="term">
          <option value="">-- ภาคเรียน --</option>
          <option value="1" <?= $term_filter == '1' ? 'selected' : '' ?>>ภาคเรียนที่ 1</option>
          <option value="2" <?= $term_filter == '2' ? 'selected' : '' ?>>ภาคเรียนที่ 2</option>
          <option value="Summer" <?= $term_filter == 'Summer' ? 'selected' : '' ?>>ภาคฤดูร้อน</option>
        </select>
        <button type="submit">กรองข้อมูล</button>
        <a href="scores.php" class="btn-outline">ล้างตัวกรอง</a>
      </form>
    </div>

    <!-- Table -->
    <table class="table">
      <thead>
        <tr>
          <th>ปี</th>
          <th>ภาคเรียน</th>
          <th>รหัสวิชา</th>
          <th>ชื่อวิชา</th>
          <th>หน่วยกิต</th>
          <th>คะแนน</th>
          <th>เกรด</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['academic_year']) ?></td>
              <td><?= htmlspecialchars($row['term_name']) ?></td>
              <td><?= htmlspecialchars($row['subject_code']) ?></td>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td><?= htmlspecialchars($row['credits']) ?></td>
              <td><?= htmlspecialchars($row['score']) ?></td>
              <td><?= htmlspecialchars($row['letter_grade']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7">📭 ไม่มีข้อมูลผลการเรียน</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>

<script>feather.replace();</script>
</body>
</html>
