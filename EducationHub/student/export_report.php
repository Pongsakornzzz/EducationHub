<?php
require_once '../vendor/autoload.php';
include "../db_connect.php";
session_start();

use Dompdf\Dompdf;

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];

// ดึงข้อมูลผู้ใช้
$userRes = $conn->query("SELECT student_code FROM users WHERE username='$username'");
$user = $userRes->fetch_assoc();
$student_code = $user['student_code'];

$stuRes = $conn->query("SELECT id, first_name, last_name FROM students WHERE student_code='$student_code'");
$student = $stuRes->fetch_assoc();
$student_id = $student['id'];
$full_name = $student['first_name'] . " " . $student['last_name'];

// รับค่าปี/เทอมจาก GET
$year = $_GET['year'] ?? '';
$term = $_GET['term'] ?? '';

// เงื่อนไขกรอง
$where = "WHERE e.student_id = '$student_id'";
if ($year) $where .= " AND ay.year_label = '$year'";
if ($term) $where .= " AND t.term_name = '$term'";

// Query ดึงข้อมูล
$sql = "
    SELECT 
        ay.year_label AS academic_year,
        t.term_name,
        s.subject_code,
        s.subject_name,
        s.credits,
        e.score,
        e.letter_grade
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    JOIN terms t ON e.term_id = t.id
    JOIN academic_years ay ON t.academic_year_id = ay.id
    $where
    ORDER BY ay.year_label DESC, t.term_name ASC
";
$result = $conn->query($sql);

// สร้าง HTML สำหรับ PDF
$html = '
<h2 style="text-align:center; font-family:DejaVu Sans;">รายงานผลการเรียน</h2>
<p><strong>ชื่อ:</strong> ' . htmlspecialchars($full_name) . ' (' . htmlspecialchars($student_code) . ')</p>
<p><strong>ปีการศึกษา:</strong> ' . ($year ?: 'ทั้งหมด') . ' &nbsp;&nbsp; 
<strong>ภาคเรียน:</strong> ' . ($term ?: 'ทั้งหมด') . '</p>
<table border="1" cellspacing="0" cellpadding="6" width="100%" style="border-collapse:collapse; font-family:DejaVu Sans; font-size:12px;">
<thead>
<tr style="background-color:#2563eb; color:white;">
<th>ปีการศึกษา</th>
<th>ภาคเรียน</th>
<th>รหัสวิชา</th>
<th>ชื่อวิชา</th>
<th>หน่วยกิต</th>
<th>คะแนน</th>
<th>เกรด</th>
</tr>
</thead>
<tbody>
';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['academic_year']) . '</td>
            <td>' . htmlspecialchars($row['term_name']) . '</td>
            <td>' . htmlspecialchars($row['subject_code']) . '</td>
            <td>' . htmlspecialchars($row['subject_name']) . '</td>
            <td>' . htmlspecialchars($row['credits']) . '</td>
            <td>' . htmlspecialchars($row['score']) . '</td>
            <td>' . htmlspecialchars($row['letter_grade']) . '</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="7" style="text-align:center;">ไม่มีข้อมูลผลการเรียน</td></tr>';
}

$html .= '</tbody></table>';

// ✅ คำนวณ GPA เฉพาะช่วงที่เลือก
$gpaQuery = $conn->query("
    SELECT 
        ROUND(SUM(e.grade_point * s.credits) / SUM(s.credits), 2) AS gpa
    FROM enrollments e
    JOIN subjects s ON e.subject_id = s.id
    JOIN terms t ON e.term_id = t.id
    JOIN academic_years ay ON t.academic_year_id = ay.id
    $where
");
$gpaRow = $gpaQuery->fetch_assoc();
$gpa = $gpaRow['gpa'] ?? '-';

$html .= '<p style="margin-top:12px;"><strong>🎓 เกรดเฉลี่ย (GPA):</strong> ' . $gpa . '</p>';

// สร้าง PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ชื่อไฟล์ PDF
$filename = "Report_" . $student_code . "_" . ($year ?: 'All') . "_" . ($term ?: 'All') . ".pdf";

// ส่งออกให้ดาวน์โหลด
$dompdf->stream($filename, ["Attachment" => true]);
exit;
?>
