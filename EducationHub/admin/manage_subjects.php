<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM subjects 
        WHERE subject_code LIKE '%$search%' 
        OR subject_name LIKE '%$search%'
        ORDER BY id DESC";
$subjects = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการรายวิชา | Education Hub</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
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
    .btn {
      background: #2563eb;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 16px;
      cursor: pointer;
      font-size: 15px;
    }
    .btn:hover { background: #1e4fcf; }
    .btn-danger {
      background: #ef4444;
      color: white;
    }
    .btn-danger:hover { background: #d32f2f; }

    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      z-index: 999;
    }
    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 10px;
      width: 400px;
      text-align: center;
      animation: fadeIn 0.3s;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
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
      <li><a href="manage_subjects.php" class="active">📚 รายวิชา</a></li>
      <li><a href="upload_review.php">📥 ตรวจสอบไฟล์</a></li>
      <li><a href="reports.php">📊 รายงาน</a></li>
      <li><a href="../logout.php" class="logout">🚪 ออกจากระบบ</a></li>
    </ul>
  </aside>

  <!-- Content -->
  <main class="content">
    <h1>📚 จัดการรายวิชา</h1>

    <form class="search-box" method="get">
      <input type="text" name="search" placeholder="🔍 ค้นหารายวิชา (ชื่อ / รหัสวิชา)" value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn">ค้นหา</button>
      <button type="button" class="btn" onclick="openAdd()">➕ เพิ่มรายวิชา</button>
    </form>

    <table class="table">
      <thead>
        <tr>
          <th>รหัสวิชา</th>
          <th>ชื่อวิชา</th>
          <th>หน่วยกิต</th>
          <th>การจัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($subjects->num_rows > 0): ?>
          <?php while ($row = $subjects->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['subject_code']) ?></td>
              <td><?= htmlspecialchars($row['subject_name']) ?></td>
              <td><?= htmlspecialchars($row['credits']) ?></td>
              <td>
                <button class="btn" onclick="openEdit('<?= $row['id'] ?>','<?= $row['subject_code'] ?>','<?= $row['subject_name'] ?>','<?= $row['credits'] ?>')">✏️ แก้ไข</button>
                <button class="btn-danger" onclick="deleteSubject(<?= $row['id'] ?>)">🗑️ ลบ</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4">📭 ไม่พบรายวิชา</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>

<!-- Modal: เพิ่ม/แก้ไข -->
<div class="modal" id="subjectModal">
  <div class="modal-content">
    <h3 id="modalTitle">เพิ่มรายวิชา</h3>
    <form method="post" id="subjectForm" action="save_subject.php">
      <input type="hidden" name="id" id="subId">
      <input type="text" name="subject_code" id="subCode" placeholder="รหัสวิชา" required>
      <input type="text" name="subject_name" id="subName" placeholder="ชื่อวิชา" required>
      <input type="number" name="credits" id="subCredit" placeholder="หน่วยกิต" required>
      <button type="submit" class="btn">บันทึก</button>
      <button type="button" class="btn-danger" onclick="closeModal()">ยกเลิก</button>
    </form>
  </div>
</div>

<script>
function openAdd() {
  document.getElementById('modalTitle').innerText = "➕ เพิ่มรายวิชา";
  document.getElementById('subId').value = "";
  document.getElementById('subCode').value = "";
  document.getElementById('subName').value = "";
  document.getElementById('subCredit').value = "";
  document.getElementById('subjectModal').style.display = "flex";
}
function openEdit(id, code, name, credit) {
  document.getElementById('modalTitle').innerText = "✏️ แก้ไขรายวิชา";
  document.getElementById('subId').value = id;
  document.getElementById('subCode').value = code;
  document.getElementById('subName').value = name;
  document.getElementById('subCredit').value = credit;
  document.getElementById('subjectModal').style.display = "flex";
}
function closeModal() {
  document.getElementById('subjectModal').style.display = "none";
}
function deleteSubject(id) {
  if (confirm("ต้องการลบรายวิชานี้หรือไม่?")) {
    window.location.href = "delete_subject.php?id=" + id;
  }
}
</script>
</body>
</html>
