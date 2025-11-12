<?php
include "../db_connect.php";

$id = intval($_GET['id']);
if ($id > 0) {
    $conn->query("DELETE FROM students WHERE id=$id");
}
header("Location: manage_students.php");
exit();
?>
