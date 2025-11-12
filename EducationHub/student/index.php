<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

/* ===== ดึงข้อมูลผู้ใช้ / นักเรียน ===== */
$userRes = $conn->query("SELECT student_code FROM users WHERE username = '$username' LIMIT 1");
$userRow = $userRes ? $userRes->fetch_assoc() : null;
$student_code = $userRow['student_code'] ?? '';

$studentRes = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code = '$student_code' LIMIT 1");
$student = $studentRes ? $studentRes->fetch_assoc() : null;

$student_id = (int)($student['id'] ?? 0);
$first_name = $student['first_name'] ?? '';
$last_name  = $student['last_name'] ?? '';
$full_name  = trim($first_name . ' ' . $last_name);
$display_name = $first_name ? "{$first_name} ({$student_code})" : $username;
$initial   = $first_name !== '' ? strtoupper(mb_substr($first_name, 0, 1, 'UTF-8')) : 'U';

/* ===== ค่าเริ่มต้น ===== */
$gpax = '-';
$latestGpa = '-';
$countSubjects = 0;
$recentSubjectRes = false;

/* ===== ดึงสถิติ/รายวิชา ===== */
if ($student_id > 0) {
    $gpaxRes = $conn->query("
        SELECT SUM(e.grade_point * s.credits) / NULLIF(SUM(s.credits),0) AS gpax
        FROM enrollments e
        JOIN subjects s ON e.subject_id = s.id
        WHERE e.student_id = '$student_id'
    ");
    $gpaxRow = $gpaxRes ? $gpaxRes->fetch_assoc() : null;
    $gpax = isset($gpaxRow['gpax']) && $gpaxRow['gpax'] !== null ? number_format((float)$gpaxRow['gpax'], 2) : '-';

    $latestRes = $conn->query("
        SELECT SUM(e.grade_point * s.credits) / NULLIF(SUM(s.credits),0) AS gpa
        FROM enrollments e
        JOIN subjects s ON e.subject_id = s.id
        WHERE e.student_id = '$student_id'
        GROUP BY e.term_id
        ORDER BY e.term_id DESC
        LIMIT 1
    ");
    $latestRow = $latestRes ? $latestRes->fetch_assoc() : null;
    $latestGpa = isset($latestRow['gpa']) && $latestRow['gpa'] !== null ? number_format((float)$latestRow['gpa'], 2) : '-';

    $countRes = $conn->query("SELECT COUNT(*) AS total_subjects FROM enrollments WHERE student_id = '$student_id'");
    $countRow = $countRes ? $countRes->fetch_assoc() : null;
    $countSubjects = (int)($countRow['total_subjects'] ?? 0);

    $recentSubjectRes = $conn->query("
        SELECT s.subject_code, s.subject_name, s.credits, e.score, e.letter_grade
        FROM enrollments e
        JOIN subjects s ON e.subject_id = s.id
        WHERE e.student_id = '$student_id'
        ORDER BY e.id DESC
        LIMIT 5
    ");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แดชบอร์ดนักเรียน | Education Hub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script defer src="https://unpkg.com/feather-icons"></script>
</head>
<body>

<div class="app">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">EDUCATION HUB</div>
    <ul class="menu">
      <li><a href="index.php" class="active"><i data-feather="home"></i><span> หน้าหลัก 🏠</span></a></li>
      <li><a href="profile.php"><i data-feather="user"></i><span> ข้อมูลส่วนตัว 👤</span></a></li>
      <li><a href="scores.php"><i data-feather="book-open"></i><span> ผลการเรียน 📚</span></a></li>
      <li><a href="gpa.php"><i data-feather="bar-chart-2"></i><span> GPA / GPAX 📊</span></a></li>
      <li><a href="subjects.php"><i data-feather="layers"></i><span> เกรดเฉลี่ยรายวิชา 📘</span></a></li>
      <li><a href="upload.php"><i data-feather="upload"></i><span> อัปโหลดผลการเรียน 📥</span></a></li>
    </ul>
  </aside>

  <!-- Content -->
  <main class="content">
    <header class="top-header">
      <button id="btnToggleSidebar" class="hamburger">☰</button>
      <div class="user-info">
        <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
        <span class="user-name"><?= htmlspecialchars($display_name) ?></span>
      </div>
      <a href="../logout.php" class="logout-btn">ออกจากระบบ</a>
    </header>

    <section class="page-head">
      <h2>แดชบอร์ดนักเรียน</h2>
      <p>ภาพรวมผลการเรียนของคุณ</p>
    </section>

    <section class="dashboard-cards">
      <div class="card">
        <div class="card-title">📊 GPAX สะสม</div>
        <div class="card-value"><?= $gpax ?></div>
      </div>
      <div class="card">
        <div class="card-title">🎓 GPA เทอมล่าสุด</div>
        <div class="card-value"><?= $latestGpa ?></div>
      </div>
      <div class="card">
        <div class="card-title">📚 จำนวนวิชาทั้งหมด</div>
        <div class="card-value"><?= $countSubjects ?></div>
      </div>
    </section>

    <section class="card" style="margin-top:16px;">
      <div class="card-title">📘 รายวิชาล่าสุด</div>
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>รหัสวิชา</th>
              <th>ชื่อวิชา</th>
              <th>หน่วยกิต</th>
              <th>คะแนน</th>
              <th>เกรด</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentSubjectRes && $recentSubjectRes->num_rows > 0): ?>
              <?php while($row = $recentSubjectRes->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['subject_code'] ?></td>
                  <td><?= $row['subject_name'] ?></td>
                  <td><?= $row['credits'] ?></td>
                  <td><?= $row['score'] ?></td>
                  <td><?= $row['letter_grade'] ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5">📭 ยังไม่มีข้อมูลรายวิชา</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<script>
  feather.replace();

  const toggleBtn = document.getElementById("btnToggleSidebar");
  const sidebar = document.querySelector(".sidebar");
  const content = document.querySelector(".content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
    content.classList.toggle("expanded");
  });
</script>

</body>
</html>
