<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ค้นหานักเรียน
$search = $_GET['search'] ?? '';

$sql = "
    SELECT s.student_code, s.first_name, s.last_name,
           ROUND(SUM(e.grade_point * sub.credits) / NULLIF(SUM(sub.credits),0), 2) AS gpax
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    JOIN subjects sub ON e.subject_id = sub.id
    WHERE s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR s.student_code LIKE '%$search%'
    GROUP BY s.id
    ORDER BY gpax DESC
";
$result = $conn->query($sql);

// แปลงข้อมูลสำหรับ Chart
$chartData = [];
while ($row = $result->fetch_assoc()) {
    $chartData[] = [
        "name" => $row['first_name'] . " " . $row['last_name'],
        "gpax" => $row['gpax'] ?? 0
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>📊 รายงาน GPA / GPAX | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container {
      width: 100%;
      max-width: 900px;
      background: #fff;
      border-radius: 12px;
      padding: 25px;
      margin-top: 20px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .btn {
      background: #2563eb;
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 15px;
    }
    .btn:hover {
      background: #1e4fcf;
    }
    .search-box {
      margin-bottom: 15px;
      display: flex;
      gap: 10px;
    }
    .search-box input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .table {
      margin-top: 15px;
      background: white;
      border-radius: 8px;
      overflow: hidden;
    }
  </style>
</head>
<body>

<div class="admin-container">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <h2>Education Hub</h2>
      <p class="role">Admin Panel</p>
    </div>
    <ul class="menu">
      <li><a href="index.php">🏠 Dashboard</a></li>
      <li><a href="manage_students.php">👨‍🎓 นักเรียน</a></li>
      <li><a href="manage_subjects.php">📚 รายวิชา</a></li>
      <li><a href="upload_review.php">📥 ตรวจสอบไฟล์</a></li>
      <li><a href="reports.php" class="active">📊 รายงาน</a></li>
      <li><a href="../logout.php" class="logout">🚪 ออกจากระบบ</a></li>
    </ul>
  </aside>

  <!-- Content -->
  <main class="content">
    <h1>📊 รายงาน GPA / GPAX</h1>
    <p>ดูภาพรวมผลการเรียนของนักเรียนทั้งหมดในระบบ</p>

    <!-- Search + Export -->
    <form class="search-box" method="get">
      <input type="text" name="search" placeholder="🔍 ค้นหานักเรียน (ชื่อ / รหัส)" value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn">ค้นหา</button>
      <button type="button" class="btn" onclick="exportCSV()">📤 ส่งออก CSV</button>
    </form>

    <!-- Chart -->
    <div class="chart-container">
      <canvas id="gpaxChart"></canvas>
    </div>

    <!-- Table -->
    <table class="table">
      <thead>
        <tr>
          <th>รหัสนักเรียน</th>
          <th>ชื่อ</th>
          <th>นามสกุล</th>
          <th>GPAX</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // ต้องรัน query อีกครั้งเพราะ fetch ไปหมดแล้วด้านบน
        $result = $conn->query($sql);
        if ($result->num_rows > 0):
          while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['student_code']) ?></td>
              <td><?= htmlspecialchars($row['first_name']) ?></td>
              <td><?= htmlspecialchars($row['last_name']) ?></td>
              <td><?= htmlspecialchars($row['gpax']) ?></td>
            </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4">📭 ไม่มีข้อมูลผลการเรียน</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  </main>
</div>

<script>
  // กราฟ GPAX
  const ctx = document.getElementById('gpaxChart');
  const data = <?= json_encode($chartData) ?>;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(d => d.name),
      datasets: [{
        label: 'GPAX',
        data: data.map(d => d.gpax),
        borderWidth: 1,
        backgroundColor: '#2563eb',
      }]
    },
    options: {
      scales: { y: { beginAtZero: true, max: 4.0 } },
      plugins: { legend: { display: false } }
    }
  });

  // Export CSV
  function exportCSV() {
    window.location.href = "export_csv.php";
  }
</script>
</body>
</html>
