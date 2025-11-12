<?php
session_start();
include "db_connect.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT * FROM users WHERE username='$username' AND password=MD5('$password')";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] == "admin") {
            header("Location: admin/");
        } else {
            header("Location: student/");
        }
        exit();
    } else {
        $error = "❌ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | EDUCATION HUB</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-header">
    <h1>EDUCATION HUB</h1>
</div>

<div class="login-container">
    <div class="login-box">

        <h2 class="login-title">เข้าสู่ระบบ</h2>

        <form method="POST">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>

            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="รหัสผ่าน" required>
                <span class="toggle-password" onclick="togglePassword()">👁</span>
            </div>

            <label class="remember-me">
                <input type="checkbox" name="remember"> จำฉันไว้ในระบบ
            </label>

            <button type="submit">เข้าสู่ระบบ</button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>


        <div class="login-links">
            <a href="#">ลืมรหัสผ่าน?</a> | <a href="register.php">สมัครสมาชิก</a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById("password");
    pw.type = pw.type === "password" ? "text" : "password";
}
</script>

<script>
// Random snow generator
for (let i = 0; i < 25; i++) {
  let snow = document.createElement("div");
  snow.className = "snow";
  snow.style.left = Math.random() * 100 + "vw";
  snow.style.animationDuration = 3 + Math.random() * 3 + "s";
  snow.style.animationDelay = Math.random() * 5 + "s";
  document.body.appendChild(snow);
}
</script>

<script>
  // Loading Button Control
  const form = document.querySelector("form");
  const loginBtn = document.querySelector("button[type='submit']");
  
  form.addEventListener("submit", () => {
    loginBtn.disabled = true;
    loginBtn.textContent = "กำลังเข้าสู่ระบบ...";
    loginBtn.style.opacity = "0.7";
  });
</script>

</body>
</html>

