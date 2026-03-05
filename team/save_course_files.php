<?php
declare(strict_types=1);
session_start();
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['team_logged_in']) || empty($_SESSION['team_db_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

$pdo = get_db_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'CSRF doğrulanamadı.']);
//     exit;
// }

function json_fail(string $msg, int $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function is_youtube_url(?string $u): bool {
    if (!$u) return false;
    return (bool)preg_match('~^https?://(?:www\.)?(?:youtube\.com|youtu\.be)/~i', $u);
}

function norm_public_url(string $path): string {
    // "uploads/..." ya da "/uploads/..." gelirse normalize et
    $path = ltrim($path, '/');
    return '/'.$path;
}
function ensure_course_ownership(PDO $pdo, int $courseId, int $teamId): array {
    $s = $pdo->prepare("SELECT id, cover_image_url, intro_video_url FROM courses WHERE id=? AND team_db_id=? LIMIT 1");
    $s->execute([$courseId, $teamId]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) json_fail('Kurs bulunamadı veya yetkiniz yok.', 403);
    return $row;
}
function safe_unlink_local(?string $publicUrl): void {
    if (!$publicUrl) return;

    // 1) Sadece yol kısmını al (query vs. temizle)
    $path = parse_url($publicUrl, PHP_URL_PATH) ?? $publicUrl;
    $path = ltrim($path, '/');                      // uploads/...

    // 2) Yalnızca uploads altında ise devam et
    if (!str_starts_with($path, 'uploads/')) return;

    // 3) /uploads kökünü güvenli şekilde çöz
    $uploadsBase = realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads');
    if ($uploadsBase === false) return;             // ortam hatası

    // 4) uploads/ sonrası kısmı al, platform bağımsız birleştir
    $rel = substr($path, strlen('uploads/'));       // covers/..., intros/...
    $rel = str_replace(['\\','/'], DIRECTORY_SEPARATOR, $rel);

    $abs = rtrim($uploadsBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;

    // 5) Dosya varsa sil
    if (is_file($abs)) {
        @unlink($abs);
        // debug istersen: error_log("UNLINK: $abs");
    }
}


// ---------- YOUTUBE KAYIT (intro_youtube) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['upload_type'])
    && $_POST['upload_type'] === 'intro_youtube') {

    $courseId = (int)($_POST['course_id'] ?? 0);
    $rawUrl   = trim((string)($_POST['youtube_url'] ?? ''));

    if ($courseId <= 0 || $rawUrl === '') json_fail('Eksik parametre.');

    // sahiplik
    $row = ensure_course_ownership($pdo, $courseId, (int)$_SESSION['team_db_id']);

    // YouTube ID çıkar (kısa/uzun/embed/shorts hepsi)
    $id = null;
    try {
        $u = new URL($rawUrl); // PHP'de yok; string regex:
    } catch (Throwable $e) { /* ignore */ }
    // pratik regex:
    if (preg_match('~(?:v=|\/)([0-9A-Za-z_-]{11})(?:[&?/]|$)~', $rawUrl, $m)) {
        $id = $m[1];
    }
    if (!$id) json_fail('Geçerli bir YouTube bağlantısı girin.');

    $normUrl = "https://www.youtube.com/watch?v=".$id;

    // MP4 varsa eskiyi silelim; sadece istersen yorumu kaldır
    // safe_unlink_local($row['intro_video_url']);

    $upd = $pdo->prepare("
        UPDATE courses
           SET intro_video_url = ?, status='pending', updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND team_db_id = ?
         LIMIT 1
    ");
    $upd->execute([$normUrl, $courseId, (int)$_SESSION['team_db_id']]);

    echo json_encode(['success' => true, 'message' => 'YouTube bağlantısı kaydedildi.', 'url' => $normUrl, 'is_cover' => false]);
    exit;
}

// ---------- DOSYA YÜKLEME (cover | intro) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_FILES['file'], $_POST['course_id'], $_POST['upload_type'])) {

    $courseId   = (int)$_POST['course_id'];
    $uploadType = (string)$_POST['upload_type']; // cover|intro

    if ($courseId <= 0) json_fail('Geçersiz kurs.');
    if (!in_array($uploadType, ['cover','intro'], true)) json_fail('Geçersiz upload türü.');

    // sahiplik
    $row = ensure_course_ownership($pdo, $courseId, (int)$_SESSION['team_db_id']);

    $file = $_FILES['file'];
    if (!empty($file['error'])) json_fail('Dosya yüklenemedi (error='.$file['error'].').');

    // MIME doğrulama
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

    $maxImage = 5 * 1024 * 1024;     // 5MB
    $maxVideo = 500 * 1024 * 1024;   // 500MB

    if ($uploadType === 'cover') {
        if (!in_array($mime, ['image/jpeg','image/png'], true)) json_fail('Sadece JPG/PNG yükleyin.');
        if ($file['size'] > $maxImage) json_fail('Görsel boyutu çok büyük (max 5MB).');
    } else { // intro
        if ($mime !== 'video/mp4') json_fail('Sadece MP4 kabul ediliyor.');
        if ($file['size'] > $maxVideo) json_fail('Video boyutu çok büyük (max 500MB).');
    }

    // Klasörler
    $relDir = ($uploadType === 'cover') ? 'uploads/covers/' : 'uploads/intros/';
    $absDir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir);
    if (!is_dir($absDir)) { @mkdir($absDir, 0775, true); }

    // Güvenli dosya adı
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $rand = bin2hex(random_bytes(8));
    $newName = "course_{$courseId}_{$uploadType}_{$rand}.{$ext}";
    $absTarget = $absDir . $newName;
    $publicUrl = $relDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $absTarget)) {
        json_fail('Dosya taşınamadı.');
    }

    // Eski dosyayı temizle (aynı tür için)
    if ($uploadType === 'cover' && !empty($row['cover_image_url'])) {
        safe_unlink_local($row['cover_image_url']);
    }
    if ($uploadType === 'intro' && !empty($row['intro_video_url'])) {
        $old = (string)$row['intro_video_url'];       // 'uploads/...' veya '/uploads/...'
        if (!is_youtube_url($old)) {
            // sadece yerel bir dosyaysa sil
            safe_unlink_local($old);
        }
    }


    // DB güncelle
    if ($uploadType === 'cover') {
        $sql = "UPDATE courses SET cover_image_url = ?, status='pending', updated_at=CURRENT_TIMESTAMP WHERE id = ? AND team_db_id = ? LIMIT 1";
        $params = [$publicUrl, $courseId, (int)$_SESSION['team_db_id']];
    } else {
        $sql = "UPDATE courses SET intro_video_url = ?, status='pending', updated_at=CURRENT_TIMESTAMP WHERE id = ? AND team_db_id = ? LIMIT 1";
        $params = [$publicUrl, $courseId, (int)$_SESSION['team_db_id']];
    }
    $upd = $pdo->prepare($sql);
    $upd->execute($params);

    echo json_encode([
        'success'  => true,
        'message'  => 'Dosya yüklendi.',
        'url'      => norm_public_url($publicUrl),
        'is_cover' => ($uploadType === 'cover')
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
