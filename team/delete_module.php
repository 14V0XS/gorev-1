<?php
// File: modules/curriculum/delete_module.php
session_start();
if (!isset($_SESSION['team_logged_in'])) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'auth']); exit(); }
header('Content-Type: application/json');

$projectRoot = dirname(__DIR__); // .../modules
$projectRoot = dirname($projectRoot); // proje kökü
require_once('../config.php');
$pdo = get_db_connection();

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

$csrf = $payload['csrf'] ?? '';
if (!$csrf || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo json_encode(['ok'=>false, 'error'=>'csrf']); exit();
}

$module_id = isset($payload['module_id']) ? (int)$payload['module_id'] : 0;
if ($module_id <= 0) { echo json_encode(['ok'=>false,'error'=>'param']); exit(); }

// fetch ownership via course
$info = $pdo->prepare("
    SELECT m.id, m.course_id, c.team_db_id
    FROM course_modules m
    JOIN courses c ON c.id = m.course_id
    WHERE m.id = ?
");
$info->execute([$module_id]);
$row = $info->fetch(PDO::FETCH_ASSOC);
if (!$row || (int)$row['team_db_id'] !== (int)$_SESSION['team_db_id']) {
    echo json_encode(['ok'=>false,'error'=>'forbidden']); exit();
}

$course_id = (int)$row['course_id'];

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) rrmdir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}

try {
    $pdo->beginTransaction();

    // delete module_contents + optionally files
    // güvenli dizin: uploads/course_{course_id}/module_{module_id}
    $uploadDir = $projectRoot . "/uploads/course_{$course_id}/module_{$module_id}";
    // satırları sil
    $delC = $pdo->prepare("DELETE FROM module_contents WHERE module_id = ?");
    $delC->execute([$module_id]);

    // modülü sil
    $delM = $pdo->prepare("DELETE FROM course_modules WHERE id = ?");
    $delM->execute([$module_id]);

    $pdo->commit();

    // dosyaları sonradan (transaction dışında) temizle
    if (is_dir($uploadDir)) rrmdir($uploadDir);

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
