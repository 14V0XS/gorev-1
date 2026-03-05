<?php
// File: modules/curriculum/reorder_modules.php
session_start();
if (!isset($_SESSION['team_logged_in'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'auth']); exit(); }
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

$course_id = isset($payload['course_id']) ? (int)$payload['course_id'] : 0;
$ids = $payload['module_ids'] ?? [];

if ($course_id <= 0 || !is_array($ids)) {
    echo json_encode(['ok'=>false, 'error'=>'param']); exit();
}

// ownership check
$stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND team_db_id = ?");
$stmt->execute([$course_id, $_SESSION['team_db_id']]);
if (!$stmt->fetch()) { echo json_encode(['ok'=>false,'error'=>'forbidden']); exit(); }

// validate module belongs to course
if (count($ids) > 0) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $check = $pdo->prepare("SELECT id FROM course_modules WHERE course_id = ? AND id IN ($in)");
    $check->execute(array_merge([$course_id], $ids));
    $validIds = $check->fetchAll(PDO::FETCH_COLUMN);

    // optional: ensure all posted IDs are valid
    if (count($validIds) !== count($ids)) {
        // filter to valid ids only
        $ids = array_map('intval', $validIds);
    }
}

try {
    $pdo->beginTransaction();
    $order = 0;
    foreach ($ids as $mid) {
        $u = $pdo->prepare("UPDATE course_modules SET sort_order = ? WHERE id = ? AND course_id = ?");
        $u->execute([$order++, (int)$mid, $course_id]);
    }
    $pdo->commit();
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
