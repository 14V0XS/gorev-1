<?php
declare(strict_types=1);
session_start();

$projectRoot = dirname(__DIR__);               // <-- PROJE KÖKÜ (doğru)
require_once $projectRoot . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function jresp(bool $ok, array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode($ok ? ['success'=>true] + $data : ['success'=>false] + $data);
    exit;
}

try {
    if (empty($_SESSION['team_logged_in']) || empty($_SESSION['team_db_id'])) {
        jresp(false, ['message'=>'Unauthorized'], 401);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jresp(false, ['message'=>'Method Not Allowed'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $courseId = (int)($input['id'] ?? 0);
    $type     = (string)($input['type'] ?? '');     // 'cover' | 'intro'

    if ($courseId <= 0 || !in_array($type, ['cover','intro'], true)) {
        jresp(false, ['message'=>'Geçersiz parametreler'], 422);
    }

    // Kapak kaldırmayı tamamen engelliyorsan:
    if ($type === 'cover') {
        jresp(false, ['message'=>'Kapak kaldırılamaz. Lütfen yeni bir kapak yükleyin.'], 400);
    }

    $pdo = get_db_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sahiplik + mevcut değerler
    $s = $pdo->prepare('SELECT cover_image_url, intro_video_url FROM courses WHERE id = ? AND team_db_id = ? LIMIT 1');
    $s->execute([$courseId, (int)$_SESSION['team_db_id']]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        jresp(false, ['message'=>'Course not found or not owned'], 403);
    }

    // Yardımcılar
    $isYoutube = static function (?string $u): bool {
        if (!$u) return false;
        return (bool)preg_match('~^https?://(?:www\.)?(?:youtube\.com|youtu\.be)/~i', $u);
    };
    $safeUnlink = static function (?string $dbPath) use ($projectRoot): void {
        if (!$dbPath) return;
        // DB'de 'uploads/...' ya da '/uploads/...' gelebilir => normalize
        $rel = ltrim($dbPath, '/');                          // uploads/...
        $abs = realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
        $uploadsRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads');

        // Sadece proje içindeki /uploads altında ise sil
        if ($abs && $uploadsRoot && str_starts_with($abs, $uploadsRoot)) {
            @unlink($abs);
        }
    };

    if ($type === 'intro') {
        $val = $row['intro_video_url'] ?? null;

        if ($val) {
            if ($isYoutube($val)) {
                // YT ise dosya yok; sadece DB'yi boşalt
                $u = $pdo->prepare('UPDATE courses SET intro_video_url = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND team_db_id = ? LIMIT 1');
                $u->execute([$courseId, (int)$_SESSION['team_db_id']]);
            } else {
                // Yerel dosya: güvenli sil + DB boşalt
                $safeUnlink($val);
                $u = $pdo->prepare('UPDATE courses SET intro_video_url = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND team_db_id = ? LIMIT 1');
                $u->execute([$courseId, (int)$_SESSION['team_db_id']]);
            }
        } // val boşsa yapacak bir şey yok

        jresp(true, ['message'=>'Tanıtım videosu kaldırıldı.']);
    }

    // cover'a zaten en başta 400 dönüyoruz; buraya düşmez.
    jresp(false, ['message'=>'Unsupported type'], 400);

} catch (Throwable $e) {
    jresp(false, ['message'=>'Server error', 'error'=>$e->getMessage()], 500);
}
