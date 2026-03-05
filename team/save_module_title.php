<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { exit('Yetkisiz erişim'); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['course_id']) && !empty(trim($_POST['title']))) {
    $pdo = get_db_connection();
    $course_id = $_POST['course_id'];
    $title = trim($_POST['title']);
    // ... Security check ...

    $stmt = $pdo->prepare("INSERT INTO course_modules (course_id, title, status) VALUES (?, ?, 'draft')");
    $stmt->execute([$course_id, $title]);
    $new_module_id = $pdo->lastInsertId();

    header("Location: edit_module_content.php?id=" . $new_module_id);
    exit();
} else {
    header("Location: panel.php");
    exit();
}
?>
