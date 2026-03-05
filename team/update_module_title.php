<?php
// File: modules/curriculum/update_module_title.php
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['team_logged_in'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Oturum gerekli.']);
        exit;
    }

    $projectRoot = dirname(__DIR__, 1); // modules/curriculum → modules
    require_once '../config.php';
    $pdo = get_db_connection();

    $moduleId = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
    $newTitle = isset($_POST['new_title']) ? trim((string)$_POST['new_title']) : '';
    $csrf     = $_POST['csrf'] ?? '';

    if (!$moduleId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Geçersiz bölüm.']);
        exit;
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Güvenlik doğrulaması başarısız (CSRF).']);
        exit;
    }
    if ($newTitle === '' || mb_strlen($newTitle) > 200) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Başlık boş olamaz ve 200 karakteri aşamaz.']);
        exit;
    }

    // Yetki: Bu modül, oturumdaki takımın kursunda mı?
    $stmt = $pdo->prepare("
        UPDATE course_modules m
        JOIN courses c ON c.id = m.course_id
        SET m.title = ?, m.status = 'pending'
        WHERE m.id = ? AND c.team_db_id = ?
    ");
    $stmt->execute([$newTitle, $moduleId, $_SESSION['team_db_id']]);

    if ($stmt->rowCount() < 1) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Bu bölümü güncelleme yetkiniz yok veya bulunamadı.']);
        exit;
    }

    echo json_encode(['ok' => true, 'title' => $newTitle]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Sunucu hatası: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
