<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ ค้นหานักเรียน
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM students 
        WHERE student_code LIKE '%$search%' 
        OR first_name LIKE '%$search%' 
        OR last_name LIKE '%$search%'
        ORDER BY id DESC";
$students = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการนักเรียน | Education Hub</title>
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

    /* Modal */
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
      <li><a href="manage_students.php" class="active">👨‍🎓 จัดการนักเรียน</a></li>
      <li><a href="manage_subjects.php">📚 รายวิชา</a></li>
      <li><a href="upload_review.php">📥 ตรวจสอบไฟล์</a></li>
      <li><a href="reports.php">📊 รายงาน</a></li>
      <li><a href="../logout.php" class="logout">🚪 ออกจากระบบ</a></li>
    </ul>
  </aside>

  <!-- Content -->
  <main class="content">
    <h1>👨‍🎓 จัดการนักเรียน</h1>

    <form class="search-box" method="get">
      <input type="text" name="search" placeholder="🔍 ค้นหานักเรียน (ชื่อ / รหัสนักเรียน)" value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn">ค้นหา</button>
    </form>

    <table class="table">
      <thead>
        <tr>
          <th>รหัสนักเรียน</th>
          <th>ชื่อ</th>
          <th>นามสกุล</th>
          <th>การจัดการ</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($students->num_rows > 0): ?>
          <?php while ($row = $students->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['student_code']) ?></td>
              <td><?= htmlspecialchars($row['first_name']) ?></td>
              <td><?= htmlspecialchars($row['last_name']) ?></td>
              <td>
                <button class="btn" onclick="openEdit('<?= $row['id'] ?>','<?= $row['first_name'] ?>','<?= $row['last_name'] ?>')">✏️ แก้ไข</button>
                <button class="btn-danger" onclick="deleteStudent(<?= $row['id'] ?>)">🗑️ ลบ</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4">📭 ไม่พบนักเรียน</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>

<!-- Modal -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <h3>แก้ไขข้อมูลนักเรียน</h3>
    <form method="post" id="editForm" action="update_student.php">
      <input type="hidden" name="id" id="editId">
      <input type="text" name="first_name" id="editFirst" placeholder="ชื่อ" required>
      <input type="text" name="last_name" id="editLast" placeholder="นามสกุล" required>
      <button type="submit" class="btn">บันทึก</button>
      <button type="button" class="btn-danger" onclick="closeEdit()">ยกเลิก</button>
    </form>
  </div>
</div>

<script>
function openEdit(id, first, last) {
  document.getElementById('editModal').style.display = 'flex';
  document.getElementById('editId').value = id;
  document.getElementById('editFirst').value = first;
  document.getElementById('editLast').value = last;
}
function closeEdit() {
  document.getElementById('editModal').style.display = 'none';
}
function deleteStudent(id) {
  if (confirm("คุณแน่ใจหรือไม่ว่าต้องการลบนักเรียนคนนี้?")) {
    window.location.href = "delete_student.php?id=" + id;
  }
}
</script>
</body>
</html>
