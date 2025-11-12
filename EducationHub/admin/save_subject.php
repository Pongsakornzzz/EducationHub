<?php
include "../db_connect.php";

$id = $_POST['id'] ?? '';
$code = trim($_POST['subject_code']);
$name = trim($_POST['subject_name']);
$credits = (int)$_POST['credits'];

if ($id) {
    $sql = "UPDATE subjects SET subject_code='$code', subject_name='$name', credits=$credits WHERE id=$id";
} else {
    $sql = "INSERT INTO subjects (subject_code, subject_name, credits) VALUES ('$code','$name',$credits)";
}
$conn->query($sql);
header("Location: manage_subjects.php");
exit();
?>
