<?php
session_start();

// เคลียร์ session ทั้งหมด
session_unset();
session_destroy();

// กลับไปหน้า login
header("Location: login.php");
exit();
?>
