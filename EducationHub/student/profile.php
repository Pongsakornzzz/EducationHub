<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

/* ===== ดึงข้อมูลผู้ใช้ / นักเรียน ===== */
$userStmt = $conn->prepare("SELECT student_code FROM users WHERE username = ? LIMIT 1");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userRow = $userStmt->get_result()->fetch_assoc();
$student_code = $userRow['student_code'] ?? '';

$studentStmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM students WHERE student_code = ? LIMIT 1");
$studentStmt->bind_param("s", $student_code);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();

$student_id = (int)($student['id'] ?? 0);
$first_name = $student['first_name'] ?? '';
$last_name  = $student['last_name'] ?? '';
$email      = $student['email'] ?? '';
$phone      = $student['phone'] ?? '';

$initial   = $first_name !== '' ? strtoupper(mb_substr($first_name, 0, 1, 'UTF-8')) : 'U';
$display_name = $first_name ? "{$first_name} ({$student_code})" : $username;

/* ===== helper รูปโปรไฟล์ (ไม่พึ่งคอลัมน์ฐานข้อมูล) ===== */
function profile_image_path($student_code) {
    $base = dirname(__DIR__) . "/uploads/profile/";
    $web  = "../uploads/profile/";
    foreach (['jpg','jpeg','png','webp'] as $ext) {
        $f = $base . $student_code . "." . $ext;
        if (is_file($f)) return $web . $student_code . "." . $ext;
    }
    return null;
}
$avatarUrl = profile_image_path($student_code) ?? "../assets/img/default-avatar.png";

/* ===== แจ้งสถานะ ===== */
$success = "";
$error   = "";

/* ===== อัปเดตรายละเอียด (ชื่อ/นามสกุล/อีเมล/โทร) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $new_first = trim($_POST['first_name'] ?? '');
    $new_last  = trim($_POST['last_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');

    if ($student_id <= 0) {
        $error = "ไม่พบข้อมูลนักเรียน";
    } else {
        $upd = $conn->prepare("UPDATE students SET first_name=?, last_name=?, email=?, phone=? WHERE id=? LIMIT 1");
        $upd->bind_param("ssssi", $new_first, $new_last, $new_email, $new_phone, $student_id);
        if ($upd->execute()) {
            $success = "บันทึกข้อมูลเรียบร้อยแล้ว ✅";
            // รีเฟรชค่าที่แสดง
            $first_name = $new_first; 
            $last_name  = $new_last;
            $email      = $new_email;
            $phone      = $new_phone;
            $initial    = $first_name !== '' ? strtoupper(mb_substr($first_name, 0, 1, 'UTF-8')) : 'U';
            $display_name = $first_name ? "{$first_name} ({$student_code})" : $username;
        } else {
            $error = "บันทึกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง";
        }
    }
}

/* ===== อัปโหลดรูปโปรไฟล์ (≤ 2MB; jpg/png/webp) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $error = "อัปโหลดรูปไม่สำเร็จ";
    } else {
        $file  = $_FILES['avatar'];
        if ($file['size'] > 2 * 1024 * 1024) {
            $error = "ไฟล์ใหญ่เกิน 2MB";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allow = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($allow[$mime])) {
                $error = "อนุญาตเฉพาะไฟล์ JPG/PNG/WebP";
            } else {
                $ext = $allow[$mime];
                $dir = dirname(__DIR__) . "/Uploads/profile/";
                if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                // ลบไฟล์เก่า (ต่างสกุล)
                foreach (['jpg','jpeg','png','webp'] as $x) {
                    $old = $dir . $student_code . "." . $x;
                    if (is_file($old)) @unlink($old);
                }
                $dest = $dir . $student_code . "." . $ext;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $success  = "อัปโหลดรูปโปรไฟล์เรียบร้อย ✅";
                    $avatarUrl = "../uploads/profile/" . $student_code . "." . $ext . "?v=" . time();
                } else {
                    $error = "ไม่สามารถบันทึกรูปได้";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>โปรไฟล์นักเรียน | Education Hub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script defer src="https://unpkg.com/feather-icons"></script>

  <style>
    /* เสริมเฉพาะหน้าโปรไฟล์ (ไม่กระทบของเดิม) */
    .profile-wrap { max-width: 760px; margin: 0 auto; }
    .profile-header {
      display:flex; align-items:center; justify-content:space-between;
      margin-bottom:14px;
    }
    .profile-meta { display:flex; align-items:center; gap:12px; }
    .avatar-circle {
      width:64px; height:64px; border-radius:50%;
      background:#2f6ad8; color:#fff; display:flex; align-items:center; justify-content:center;
      font-weight:700; font-size:24px;
    }
    .avatar-img {
      width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #e6eefc;
    }
    .muted { color:#6b7280; font-size:14px; }
    .card .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    .card label { display:block; font-size:14px; color:#64748b; margin-bottom:6px; }
    .card input[disabled] { background:#f8fafc; color:#334155; }
    @media (max-width: 640px){ .card .row{ grid-template-columns:1fr; } }
    .btn-inline { display:flex; gap:10px; flex-wrap:wrap; }
    .btn-ghost {
      background:#fff; border:1px solid #e5e7eb; color:#1f2937;
      padding:8px 12px; border-radius:8px; text-decoration:none;
    }
    .hint { font-size:12px; color:#6b7280; margin-top:6px; }
    .status { margin:10px 0 0; }
  </style>
</head>
<body>

<div class="app">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">EDUCATION HUB</div>
    <ul class="menu">
      <li><a href="index.php"><i data-feather="home"></i><span> หน้าหลัก 🏠</span></a></li>
      <li><a href="profile.php" class="active"><i data-feather="user"></i><span> ข้อมูลส่วนตัว 👤</span></a></li>
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

    <div class="profile-wrap">
      <!-- หัวการ์ด -->
      <div class="profile-header">
        <div class="profile-meta">
          <?php if ($avatarUrl): ?>
            <img class="avatar-img" src="<?= htmlspecialchars($avatarUrl) ?>" alt="avatar">
          <?php else: ?>
            <div class="avatar-circle"><?= htmlspecialchars($initial) ?></div>
          <?php endif; ?>
          <div>
            <h2 style="margin:0 0 2px;">ข้อมูลส่วนตัว</h2>
            <div class="muted"><?= htmlspecialchars($first_name ?: $username) ?> • รหัสนักเรียน: <?= htmlspecialchars($student_code) ?></div>
          </div>
        </div>
        <div class="btn-inline">
          <a class="btn-ghost" href="change_password.php">🔒 เปลี่ยนรหัสผ่าน</a>
        </div>
      </div>

      <!-- การ์ดโปรไฟล์ -->
      <section class="card">
        <!-- ฟอร์มอัปโหลดรูป -->
        <form method="post" enctype="multipart/form-data" style="margin-bottom:16px;">
          <input type="hidden" name="action" value="upload_avatar">
          <label>รูปโปรไฟล์</label>
          <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
          <div class="hint">รองรับ JPG/PNG/WebP — ขนาดไม่เกิน 2MB</div>
          <button type="submit" class="btn" style="margin-top:10px; width:auto;">อัปโหลดรูป</button>
        </form>

        <!-- ฟอร์มข้อมูลส่วนตัว -->
        <form method="post" id="profileForm">
          <input type="hidden" name="action" value="update_profile">

          <div class="row">
            <div>
              <label>รหัสนักเรียน</label>
              <input type="text" value="<?= htmlspecialchars($student_code) ?>" disabled>
            </div>
            <div>
              <label>ชื่อผู้ใช้ (Username)</label>
              <input type="text" value="<?= htmlspecialchars($username) ?>" disabled>
            </div>
            <div>
              <label>ชื่อจริง</label>
              <input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>" disabled required>
            </div>
            <div>
              <label>นามสกุล</label>
              <input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>" disabled required>
            </div>
            <div>
              <label>อีเมล</label>
              <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" disabled>
            </div>
            <div>
              <label>เบอร์โทร</label>
              <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" disabled>
            </div>
          </div>

          <div class="btn-inline" style="margin-top:12px;">
            <button type="button" id="btnEdit" class="btn" style="width:auto;">✏️ แก้ไขข้อมูล</button>
            <button type="submit" id="btnSave" class="btn" style="width:auto; display:none;">💾 บันทึก</button>
            <button type="button" id="btnCancel" class="btn-ghost" style="display:none;">ยกเลิก</button>
          </div>

          <?php if ($success): ?>
            <div class="success status"><?= $success ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="error status"><?= $error ?></div>
          <?php endif; ?>
        </form>
      </section>
    </div>
  </main>
</div>

<script>
  feather.replace();

  // Toggle Sidebar
  const toggleBtn = document.getElementById("btnToggleSidebar");
  const sidebar = document.querySelector(".sidebar");
  const content = document.querySelector(".content");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
      content.classList.toggle("expanded");
    });
  }

  // โหมดแก้ไขโปรไฟล์ (E2)
  const btnEdit = document.getElementById('btnEdit');
  const btnSave = document.getElementById('btnSave');
  const btnCancel = document.getElementById('btnCancel');
  const form = document.getElementById('profileForm');
  const inputs = form.querySelectorAll('input[name="first_name"], input[name="last_name"], input[name="email"], input[name="phone"]');
  const original = {};

  function setEditable(on) {
    inputs.forEach(i => i.disabled = !on);
    btnSave.style.display = on ? 'inline-block' : 'none';
    btnCancel.style.display = on ? 'inline-block' : 'none';
    btnEdit.style.display = on ? 'none' : 'inline-block';
  }

  btnEdit.addEventListener('click', () => {
    inputs.forEach(i => original[i.name] = i.value);
    setEditable(true);
  });

  btnCancel.addEventListener('click', () => {
    inputs.forEach(i => i.value = original[i.name] ?? i.value);
    setEditable(false);
  });

  // ปิดโหมดแก้ไขหลังบันทึกสำเร็จ (ให้ PHP รีเฟรชข้อความแล้ว)
  <?php if ($success && isset($_POST['action']) && $_POST['action']==='update_profile'): ?>
    setEditable(false);
  <?php endif; ?>
</script>

</body>
</html>
