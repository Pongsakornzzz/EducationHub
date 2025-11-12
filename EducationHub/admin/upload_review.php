<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ อัปเดตสถานะ (อนุมัติ / ปฏิเสธ)
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE uploads SET status='approved' WHERE id=$id");
} elseif (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE uploads SET status='rejected' WHERE id=$id");
}

// ✅ ดึงข้อมูลไฟล์ทั้งหมด
$uploads = $conn->query("
    SELECT u.*, s.student_code, s.first_name, s.last_name
    FROM uploads u
    JOIN students s ON u.student_id = s.id
    ORDER BY u.upload_date DESC
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ตรวจสอบไฟล์อัปโหลด | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    .status {
      padding: 5px 10px;
      border-radius: 6px;
      font-weight: 500;
    }
    .approved { background: #d1fae5; color: #065f46; }
    .pending { background: #fef3c7; color: #92400e; }
    .rejected { background: #fee2e2; color: #991b1b; }
    .btn-action {
      border: none;
      border-radius: 6px;
      padding: 6px 12px;
      cursor: pointer;
      color: white;
      margin: 0 2px;
      transition: 0.2s;
    }
    .btn-approve { background: #16a34a; }
    .btn-reject { background: #dc2626; }
    .btn-approve:hover { background: #15803d; }
    .btn-reject:hover { background: #b91c1c; }
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
      <li><a href="upload_review.php" class="active">📥 ตรวจสอบไฟล์</a></li>
      <li><a href="reports.php">📊 รายงาน</a></li>
      <li><a href="../logout.php" class="logout">🚪 ออกจากระบบ</a></li>
    </ul>
  </aside>

  <!-- Content -->
  <main class="content">
    <h1>📥 ตรวจสอบไฟล์อัปโหลด</h1>
    <p>ตรวจสอบไฟล์ที่นักเรียนส่งเข้ามา เพื่ออนุมัติหรือปฏิเสธ</p>

    <table class="table">
      <thead>
        <tr>
          <th>รหัสนักเรียน</th>
          <th>ชื่อ-นามสกุล</th>
          <th>ชื่อไฟล์</th>
          <th>วันที่อัปโหลด</th>
          <th>สถานะ</th>
          <th>การจัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($uploads->num_rows > 0): ?>
          <?php while ($row = $uploads->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['student_code']) ?></td>
              <td><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></td>
              <td><a href="../uploads/<?= htmlspecialchars($row['file_name']) ?>" target="_blank"><?= htmlspecialchars($row['file_name']) ?></a></td>
              <td><?= htmlspecialchars($row['upload_date']) ?></td>
              <td>
                <span class="status 
                  <?= $row['status'] == 'approved' ? 'approved' : ($row['status'] == 'rejected' ? 'rejected' : 'pending') ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td>
                <?php if ($row['status'] == 'pending'): ?>
                  <a href="?approve=<?= $row['id'] ?>" class="btn-action btn-approve">✅ อนุมัติ</a>
                  <a href="?reject=<?= $row['id'] ?>" class="btn-action btn-reject">❌ ปฏิเสธ</a>
                <?php else: ?>
                  <em>ดำเนินการแล้ว</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6">📭 ยังไม่มีไฟล์อัปโหลด</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>

<script>feather.replace();</script>
</body>
</html>
