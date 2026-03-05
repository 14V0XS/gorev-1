<?php
// File: modules/curriculum/save_curriculum.php
session_start();

$projectRoot = $_SERVER['DOCUMENT_ROOT']; // .../projeadi
require_once('../config.php');

if (!isset($_SESSION['team_logged_in'])) {
    header('Location: ../team-login.php');
    exit();
}

$pdo = get_db_connection();

// ===== Helpers =====
function fail($msg, $code = 400) {
    http_response_code($code);
    die($msg);
}

function ensure_dir(string $path): void {
    if (is_dir($path)) return;
    if (!@mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException("mkdir_failed: $path");
    }
}

function sanitize_filename(string $name): string {
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $name = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $name);
    return trim($name, '._');
}

function detect_mime(string $tmpPath): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->file($tmpPath) ?: 'application/octet-stream';
}

function is_allowed_mime(string $mime, string $kind): bool {
    $video = [
        'video/mp4','video/quicktime','video/x-msvideo','video/x-matroska','video/webm','video/ogg'
    ];
    $docs  = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    if ($kind === 'video') return in_array($mime, $video, true) || str_starts_with($mime, 'video/');
    if ($kind === 'document') return in_array($mime, $docs, true) || $mime === 'application/octet-stream';
    return false;
}

/**
 * $_FILES çok boyutlu dizisinden şu path'e ulaşır:
 * modules[moduleKey][contents][contentKey][file]
 * $filesBag = $_FILES['modules'] üzerinden çekilir.
 */
function pick_nested_file(array $filesBag, string $moduleKey, string $contentKey): ?array {
    // PHP'nin $_FILES yapısında isimler ayrı ayrı tutulur.
    if (!isset($filesBag['name'][$moduleKey]['contents'][$contentKey]['file'])) {
        return null;
    }
    return [
        'name'     => $filesBag['name'][$moduleKey]['contents'][$contentKey]['file'] ?? null,
        'type'     => $filesBag['type'][$moduleKey]['contents'][$contentKey]['file'] ?? null,
        'tmp_name' => $filesBag['tmp_name'][$moduleKey]['contents'][$contentKey]['file'] ?? null,
        'error'    => $filesBag['error'][$moduleKey]['contents'][$contentKey]['file'] ?? UPLOAD_ERR_NO_FILE,
        'size'     => $filesBag['size'][$moduleKey]['contents'][$contentKey]['file'] ?? 0,
    ];
}

function move_uploaded_file_strict(array $file, string $destDir, string $projectRoot): string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('upload_error');
    }
    $tmp  = $file['tmp_name'];
    $name = sanitize_filename($file['name'] ?? ('file_' . bin2hex(random_bytes(6))));
    $ext  = pathinfo($name, PATHINFO_EXTENSION);

    ensure_dir($destDir);

    $base  = pathinfo($name, PATHINFO_FILENAME);
    $final = $base . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
    $destFs = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $final;

    if (!@move_uploaded_file($tmp, $destFs)) {
        throw new RuntimeException('move_failed');
    }
    // Proje köküne göre göreli yol
    $rel = ltrim(str_replace($projectRoot, '', $destFs), '/\\');
    return str_replace('\\', '/', $rel);
}

// ===== Validate =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Geçersiz istek yöntemi.');
$course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
if ($course_id <= 0) fail('course_id eksik.');

$modules = $_POST['modules'] ?? [];         // modules[new_key][title] + contents...
$filesBag = $_FILES['modules'] ?? null;     // dosyalar hiyerarşik

if (!is_array($modules) || empty($modules)) {
    fail('Eklenecek modül bulunamadı.');
}

// Kurs sahipliği doğrula
$own = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND team_db_id = ?");
$own->execute([$course_id, $_SESSION['team_db_id']]);
if ($own->fetchColumn() === false) {
    fail('Bu kurs üzerinde yetkiniz yok.', 403);
}

// Opsiyonel: “Sadece ilk kurulumda çalışsın” kuralını backend’de de enforce etmek istersen:
$chk = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
$chk->execute([$course_id]);
if ((int)$chk->fetchColumn() > 0) {
    // Zaten modül varsa, edit sayfasına yönlendir.
    header('Location: manage_curriculum.php?id=' . $course_id);
    exit();
}

try {
    $pdo->beginTransaction();

    foreach ($modules as $moduleKey => $moduleData) {
        $title = trim($moduleData['title'] ?? '');
        if ($title === '') {
            // boş başlıklı modül atla
            continue;
        }

        // 1) Modülü ekle
        $insMod = $pdo->prepare("INSERT INTO course_modules (course_id, title) VALUES (?, ?)");
        $insMod->execute([$course_id, $title]);
        $module_id = (int)$pdo->lastInsertId();

        // Modül upload hedef klasörü
        $destDir = $projectRoot . "/uploads/course_{$course_id}/module_{$module_id}";
        ensure_dir($destDir);

        // 2) İçerikleri ekle (varsa)
        $contents = $moduleData['contents'] ?? [];
        if (!is_array($contents) || empty($contents)) {
            continue; // içerik yoksa modül yine de oluşturulmuş olur
        }

        // Her içerik satırı
        foreach ($contents as $contentKey => $cVal) {
            $rawType = $cVal['type'] ?? null;   // 'text' | 'video' | 'document'
            if (!$rawType) continue;

            $sort     = isset($cVal['sort_order']) ? (int)$cVal['sort_order'] : 0;
            $para     = $cVal['paragraph'] ?? null;
            $existing = $cVal['existing_file'] ?? null;

            $dbType = ($rawType === 'document') ? 'doc' : $rawType;

            if ($dbType === 'text') {
                $clean = trim((string)$para);
                if ($clean === '') continue;
                $ins = $pdo->prepare("INSERT INTO module_contents (module_id, type, title, data, sort_order) VALUES (?, 'text', NULL, ?, ?)");
                $ins->execute([$module_id, $para, $sort]);
                continue;
            }

            // video / doc: dosya ya da existing_file gerekir
            $storedPath = null;

            // İlgili dosyayı $_FILES hiyerarşisinden çek
            $fileArr = $filesBag ? pick_nested_file($filesBag, $moduleKey, $contentKey) : null;

            if ($fileArr && $fileArr['error'] === UPLOAD_ERR_OK) {
                $mime = detect_mime($fileArr['tmp_name']);
                $kind = ($dbType === 'video') ? 'video' : 'document';
                if (!is_allowed_mime($mime, $kind)) {
                    throw new RuntimeException("Yasaklı dosya türü: $mime ($kind)");
                }
                $storedPath = move_uploaded_file_strict($fileArr, $destDir, $projectRoot);

            } elseif ($existing && trim($existing) !== '') {
                // Güvenlik: sadece bu modül klasörü altındaki göreli path'i kabul et
                $rel = ltrim($existing, '/\\');
                $prefix = "uploads/course_{$course_id}/module_{$module_id}/";
                if (str_starts_with($rel, $prefix)) {
                    $storedPath = str_replace('\\', '/', $rel);
                } else {
                    throw new RuntimeException('Geçersiz existing_file yolu.');
                }

            } else {
                // Ne upload ne existing -> bu içeriği atla
                continue;
            }

            $ins = $pdo->prepare("INSERT INTO module_contents (module_id, type, title, data, sort_order) VALUES (?, ?, NULL, ?, ?)");
            $ins->execute([$module_id, $dbType, $storedPath, $sort]);
        }
    }

    $pdo->commit();

    // Başarı → “edit” sayfasına dön (ilk kurulum tamamlandıktan sonra kuralın gereği)
    header('Location: manage_curriculum.php?id=' . $course_id . '&created=1' . '&mod=' . $module_id);
    exit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Kaydetme sırasında hata: " . htmlspecialchars($e->getMessage());
}
