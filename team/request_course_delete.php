<?php
// request_course_delete.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

$response = ['ok' => false];

try {
    // ---- Temel kontroller ----
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Geçersiz istek yöntemi.');
    }
    if (empty($_SESSION['team_logged_in'])) {
        throw new RuntimeException('Oturum bulunamadı.');
    }
    if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        throw new RuntimeException('CSRF doğrulanamadı.');
    }

    $teamDbId  = (int)($_SESSION['team_db_id'] ?? 0);   // sahiplik için DB’deki team ID
    $courseId  = (int)($_POST['course_id'] ?? 0);
    if ($teamDbId <= 0 || $courseId <= 0) {
        throw new RuntimeException('Eksik/Geçersiz parametre.');
    }

    $pdo = get_db_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ---- Kurs gerçekten bu takıma mı ait? ----
    $check = $pdo->prepare("SELECT 1 FROM courses WHERE id = ? AND team_db_id = ? LIMIT 1");
    $check->execute([$courseId, $teamDbId]);
    if (!$check->fetchColumn()) {
        throw new RuntimeException('Bu kurs size ait değil veya bulunamadı.');
    }

    // ---- Talepler tablosu ----
    // EKRAN GÖRÜNTÜSÜ ŞEMASINA GÖRE SÜTUNLAR:
    // id, team_id, content_id, type ENUM('support','remove'), is_resolved TINYINT, created_at TIMESTAMP
    // Tablo adını ihtiyacına göre değiştir:
    $TABLE = 'team_support';

    // ---- Aynı kurs için bekleyen (is_resolved=0) remove talebi var mı? ----
    $dup = $pdo->prepare("SELECT id FROM {$TABLE} WHERE team_id = ? AND content_id = ? AND type = 'remove' AND is_resolved = 0 LIMIT 1");
    $dup->execute([$teamDbId, $courseId]);
    if ($dup->fetchColumn()) {
        // Zaten bekleyen talep var → OK dön, fakat bilgi ekleyelim
        $response['ok'] = true;
        $response['already_exists'] = true;
        $response['message'] = 'Zaten bekleyen bir silme talebiniz var.';
        echo json_encode($response);
        exit;
    }

    // ---- Talebi ekle ----
    $ins = $pdo->prepare("INSERT INTO {$TABLE} (team_id, content_id, type, is_resolved) VALUES (?, ?, 'remove', 0)");
    $ins->execute([$teamDbId, $courseId]);

    $response['ok'] = true;
    $response['request_id'] = (int)$pdo->lastInsertId();
    $response['message'] = 'Silme isteği oluşturuldu.';
    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    // Hata mesajını istersen kullanıcıya göstermeyebilirsin; şimdilik kısa bir bilgi dönüyorum
    http_response_code(400);
    $response['error'] = $e->getMessage();
    echo json_encode($response);
    exit;
}
