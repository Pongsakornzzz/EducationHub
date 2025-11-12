<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// ✅ ดึงข้อมูลสถิติจากฐานข้อมูล
$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'] ?? 0;
$totalUploads  = $conn->query("SELECT COUNT(*) AS c FROM uploads")->fetch_assoc()['c'] ?? 0;
$approved      = $conn->query("SELECT COUNT(*) AS c FROM uploads WHERE status='approved'")->fetch_assoc()['c'] ?? 0;
$pending       = $conn->query("SELECT COUNT(*) AS c FROM uploads WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$rejected      = $conn->query("SELECT COUNT(*) AS c FROM uploads WHERE status='rejected'")->fetch_assoc()['c'] ?? 0;

// ✅ ดึงข้อมูลอัปโหลดรายเดือน
$chartQuery = $conn->query("
    SELECT DATE_FORMAT(upload_date, '%Y-%m') AS month, COUNT(*) AS uploads
    FROM uploads
    GROUP BY month
    ORDER BY month ASC
");
$chartLabels = [];
$chartData = [];
while ($row = $chartQuery->fetch_assoc()) {
    $chartLabels[] = $row['month'];
    $chartData[] = (int)$row['uploads'];
}

// ✅ ดึงไฟล์ล่าสุด
$recentUploads = $conn->query("
    SELECT u.file_name, u.upload_date, u.status, s.student_code, s.first_name, s.last_name
    FROM uploads u
    JOIN students s ON u.student_id = s.id
    ORDER BY u.upload_date DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Education Hub</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li><a href="index.php" class="active">🏠 Dashboard</a></li>
            <li><a href="manage_students.php">👨‍🎓 จัดการนักเรียน</a></li>
            <li><a href="manage_subjects.php">📚 จัดการรายวิชา</a></li>
            <li><a href="upload_review.php">📥 ตรวจสอบไฟล์อัปโหลด</a></li>
            <li><a href="reports.php">📊 รายงาน GPA / GPAX</a></li>
            <li><a href="profile.php">👤 โปรไฟล์ผู้ดูแล</a></li>
            <li><a href="../logout.php" class="logout">🚪 ออกจากระบบ</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
        <h1>📊 แดชบอร์ดผู้ดูแลระบบ</h1>
        <p>สวัสดี <b><?php echo htmlspecialchars($username); ?></b> 👋</p>

        <section class="dashboard-cards">
            <div class="card"><h3>👨‍🎓 นักเรียนทั้งหมด</h3><p class="card-value"><?= $totalStudents ?></p></div>
            <div class="card"><h3>📂 ไฟล์ทั้งหมด</h3><p class="card-value"><?= $totalUploads ?></p></div>
            <div class="card"><h3>✅ อนุมัติแล้ว</h3><p class="card-value"><?= $approved ?></p></div>
            <div class="card"><h3>⏳ รอตรวจสอบ</h3><p class="card-value"><?= $pending ?></p></div>
            <div class="card"><h3>❌ ปฏิเสธ</h3><p class="card-value"><?= $rejected ?></p></div>
        </section>

        <canvas id="uploadChart" height="120"></canvas>

        <section style="margin-top:25px;">
            <h2>📄 ไฟล์ที่อัปโหลดล่าสุด</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>รหัสนักเรียน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>ชื่อไฟล์</th>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentUploads->num_rows > 0): ?>
                        <?php while ($r = $recentUploads->fetch_assoc()): ?>
                            <tr>
                                <td><?= $r['student_code'] ?></td>
                                <td><?= $r['first_name'] . " " . $r['last_name'] ?></td>
                                <td><?= $r['file_name'] ?></td>
                                <td><?= $r['upload_date'] ?></td>
                                <td><?= $r['status'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">📭 ยังไม่มีไฟล์อัปโหลด</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<script>
const ctx = document.getElementById('uploadChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'จำนวนไฟล์อัปโหลดต่อเดือน',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: '#2563eb',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>
