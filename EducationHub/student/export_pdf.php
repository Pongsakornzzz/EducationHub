<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php'; // เรียกใช้ mPDF
include "../db_connect.php";

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// ดึงข้อมูลนักเรียน
$userQuery = $conn->query("SELECT student_code FROM users WHERE username='$username'");
$user = $userQuery->fetch_assoc();
$student_code = $user['student_code'];

$studentQuery = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code='$student_code'");
$student = $studentQuery->fetch_assoc();
$student_id = $student['id'];
$full_name = $student['first_name'] . " " . $student['last_name'];

// ดึงข้อมูลรายวิชา
$result = $conn->query("
    SELECT e.year, e.term_id, s.subject_code, s.subject_name, s.credits, e.score, e.letter_grade
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    WHERE e.student_id = '$student_id'
    ORDER BY e.year DESC, e.term_id DESC
");

// คำนวณ GPAX
$gpaxQuery = $conn->query("
    SELECT SUM(e.grade_point * s.credits) / SUM(s.credits) AS gpax
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    WHERE e.student_id = '$student_id'
");
$gpaxData = $gpaxQuery->fetch_assoc();
$gpax = $gpaxData['gpax'] ? round($gpaxData['gpax'], 2) : "-";

// เริ่มสร้าง PDF
$mpdf = new \Mpdf\Mpdf(['default_font' => 'sarabun']); // ฟอนต์ไทย
$html = "
<h2 style='text-align:center;'>รายงานผลการเรียน (Transcript)</h2>
<p><strong>ชื่อ: </strong> $full_name</p>
<p><strong>รหัสนักเรียน: </strong> $student_code</p>
<hr>

<table border='1' cellpadding='8' cellspacing='0' width='100%'>
<thead>
<tr style='background:#2563eb;color:white;'>
    <th>ปี</th>
    <th>ภาคเรียน</th>
    <th>รหัสวิชา</th>
    <th>ชื่อวิชา</th>
    <th>หน่วยกิต</th>
    <th>คะแนน</th>
    <th>เกรด</th>
</tr>
</thead>
<tbody>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= "<tr>
            <td>{$row['year']}</td>
            <td>{$row['term_id']}</td>
            <td>{$row['subject_code']}</td>
            <td>{$row['subject_name']}</td>
            <td>{$row['credits']}</td>
            <td>{$row['score']}</td>
            <td>{$row['letter_grade']}</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='7' align='center'>ไม่มีข้อมูลรายวิชา</td></tr>";
}

$html .= "</tbody></table>
<br><h3>📊 GPAX สะสม: <span style='color:#2563eb;'>$gpax</span></h3>
<p style='text-align:right;margin-top:20px;'>Education Hub System</p>
";

$mpdf->WriteHTML($html);
$mpdf->Output("Transcript_$student_code.pdf", "I"); // แสดงใน browser
?>
