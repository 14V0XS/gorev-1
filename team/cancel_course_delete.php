<?php
// cancel_course_delete.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

$out = ['ok' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Geçersiz yöntem.');
    }
    if (empty($_SESSION['team_logged_in'])) {
        throw new RuntimeException('Oturum bulunamadı.');
    }
    if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        throw new RuntimeException('CSRF doğrulanamadı.');
    }

    $teamDbId = (int)($_SESSION['team_db_id'] ?? 0);
    $courseId = (int)($_POST['course_id'] ?? 0);
    if ($teamDbId <= 0 || $courseId <= 0) {
        throw new RuntimeException('Eksik/Geçersiz parametre.');
    }

    $pdo = get_db_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kurs sahipliği
    $own = $pdo->prepare("SELECT 1 FROM courses WHERE id = ? AND team_db_id = ? LIMIT 1");
    $own->execute([$courseId, $teamDbId]);
    if (!$own->fetchColumn()) {
        throw new RuntimeException('Bu kurs size ait değil veya bulunamadı.');
    }

    // Bekleyen remove isteğini sil
    $del = $pdo->prepare("
        DELETE FROM team_support 
        WHERE team_id = ? AND content_id = ? AND type = 'remove' AND is_resolved = 0
        LIMIT 1
    ");
    $del->execute([$teamDbId, $courseId]);

    $out['ok'] = true;
    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(400);
    $out['error'] = $e->getMessage();
    echo json_encode($out);
}
