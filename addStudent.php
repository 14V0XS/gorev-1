<?php


session_start();
require_once 'config.php';
if (!isset($_POST['id'])) {
    echo 'invalid';
    exit;
}
require_once 'config.php';

$pdo = get_db_connection();
$course_id = (int)($_POST['id'] ?? 0);
if ($course_id < 1) {
    http_response_code(400);
    exit('Geçersiz kurs.');
}

// (Opsiyonel) Kurs var mı / yayında mı diye kontrol edebilirsin
//$check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND status = 'approved' LIMIT 1");
//$check->execute([$course_id]);
//if (!$check->fetch()) { http_response_code(404); exit('Kurs bulunamadı.'); }

$anonId = rv_get_or_set_anon_id();

// Idempotent kayıt (aynı kursa tekrar tıklarsa patlamasın)
$stmt = $pdo->prepare("
  INSERT INTO course_guest_enrollments (course_id, anon_id, created_at, last_seen_at)
  VALUES (?, ?, NOW(), NOW())
  ON DUPLICATE KEY UPDATE last_seen_at = VALUES(last_seen_at)
");
$stmt->execute([$course_id, $anonId]);

// Hızlı geçiş için kursa özel erişim cookie’si de bas (1 yıl)
$courseCookie = "access_course_{$course_id}";
setcookie($courseCookie, "1", time() + 3600 * 24 * 365, "/", "", false, true);



try {
    $db = get_db_connection();

    $check = $db->prepare("SELECT student FROM courses WHERE id = :id");
    $check->execute([':id' => $course_id]);
    $result = $check->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo 'course not found';
        exit;
    }

    $number = (int) $result['student'] + 1;

    $stmt = $db->prepare("UPDATE courses SET student = :student WHERE id = :id");
    $stmt->execute([
        ':student' => $number,
        ':id'      => $course_id,
    ]);

    echo 'success';
    echo $course_id; // gerekirse ID döndür
} catch (PDOException $e) {
    echo 'error: ' . $e->getMessage();
}
