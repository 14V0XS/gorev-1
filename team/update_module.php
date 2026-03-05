<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) {
    exit('Yetkisiz erişim');
}

$projectRoot = dirname(__DIR__); // ör: C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$pdo = get_db_connection();

/** Basit dosya yükleyici: uploads/course_{course_id}/ içine atar, göreli path döner */
function upload_file_simple(?array $file, int $course_id): ?string {
    if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $dirFs  = rtrim(dirname(__DIR__), '/\\') . '/uploads/course_' . $course_id . '/';
    $dirRel = 'uploads/course_' . $course_id . '/';

    if (!is_dir($dirFs)) {
        if (!mkdir($dirFs, 0777, true) && !is_dir($dirFs)) return null;
    }
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $basename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $safe     = $basename . '_' . uniqid() . ($ext ? '.' . $ext : '');
    if (!move_uploaded_file($file['tmp_name'], $dirFs . $safe)) return null;

    return $dirRel . $safe;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Temel alanlar
    $module_id = (int)($_POST['module_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $existing_id = isset($_POST['existing_id']) && $_POST['existing_id'] !== '' ? (int)$_POST['existing_id'] : null;

    if (!$module_id || !$course_id) exit('Eksik parametre');

    // Yetki: bu modül bu takıma mı ait?
    $stmt_check = $pdo->prepare("
        SELECT c.id
          FROM course_modules m
          JOIN courses c ON m.course_id = c.id
         WHERE m.id = ? AND c.team_db_id = ?
    ");
    $stmt_check->execute([$module_id, $_SESSION['team_db_id']]);
    if ($stmt_check->fetchColumn() === false) {
        exit("Bu işlemi yapma yetkiniz yok.");
    }

    // Post verileri
    $title     = trim($_POST['title'] ?? '');
    $text_body = $_POST['text_body'] ?? ''; // HTML kabul ediyorsan trim yapma

    // Eski path'ler
    $video_existing_path    = trim($_POST['video_existing_path'] ?? '');
    $document_existing_path = trim($_POST['document_existing_path'] ?? '');

    // (Opsiyonel temizleme checkbox'ları varsa)
    $video_clear    = !empty($_POST['video_clear']);
    $document_clear = !empty($_POST['document_clear']);

    // Dosyalar
    $video_path = null;
    $doc_path   = null;

    if (!empty($_FILES['video_file']['name'])) {
        $video_path = upload_file_simple($_FILES['video_file'], $course_id);
    } elseif (!$video_clear && $video_existing_path !== '') {
        $video_path = $video_existing_path; // yeni yükleme yoksa mevcut kalsın
    } else {
        $video_path = null; // temizle seçiliyse sıfırla
    }

    if (!empty($_FILES['document_file']['name'])) {
        $doc_path = upload_file_simple($_FILES['document_file'], $course_id);
    } elseif (!$document_clear && $document_existing_path !== '') {
        $doc_path = $document_existing_path;
    } else {
        $doc_path = null;
    }

    try {
        $pdo->beginTransaction();

        if ($existing_id) {
            // Güvenlik: gerçekten bu modüle ait mi?
            $chk = $pdo->prepare("SELECT id FROM module_contents WHERE id = ? AND module_id = ?");
            $chk->execute([$existing_id, $module_id]);

            if ($chk->fetchColumn()) {
                $upd = $pdo->prepare("
                    UPDATE module_contents
                       SET title = :title,
                           data = :data,          -- metin
                           data_file = :data_file,-- doküman yolu
                           data_vid  = :data_vid, -- video yolu
                           sort_order = 0
                     WHERE id = :id
                ");
                $upd->execute([
                    ':title'     => $title !== '' ? $title : null,
                    ':data'      => $text_body !== '' ? $text_body : null,
                    ':data_file' => $doc_path,
                    ':data_vid'  => $video_path,
                    ':id'        => $existing_id,
                ]);
            } else {
                // id tutmuyorsa INSERT'e düş
                $ins = $pdo->prepare("
                    INSERT INTO module_contents (module_id, title, data, data_file, data_vid, sort_order)
                    VALUES (:module_id, :title, :data, :data_file, :data_vid, 0)
                ");
                $ins->execute([
                    ':module_id' => $module_id,
                    ':title'     => $title !== '' ? $title : null,
                    ':data'      => $text_body !== '' ? $text_body : null,
                    ':data_file' => $doc_path,
                    ':data_vid'  => $video_path,
                ]);
                $existing_id = (int)$pdo->lastInsertId();
            }
        } else {
            // Zaten kayıt var mı? (tek satır kuralı)
            $q = $pdo->prepare("SELECT id FROM module_contents WHERE module_id = ? LIMIT 1");
            $q->execute([$module_id]);
            $found = $q->fetchColumn();

            if ($found) {
                $upd = $pdo->prepare("
                    UPDATE module_contents
                       SET title = :title,
                           data = :data,
                           data_file = :data_file,
                           data_vid  = :data_vid,
                           sort_order = 0
                     WHERE id = :id
                ");
                $upd->execute([
                    ':title'     => $title !== '' ? $title : null,
                    ':data'      => $text_body !== '' ? $text_body : null,
                    ':data_file' => $doc_path,
                    ':data_vid'  => $video_path,
                    ':id'        => $found,
                ]);
                $existing_id = (int)$found;
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO module_contents (module_id, title, data, data_file, data_vid, sort_order)
                    VALUES (:module_id, :title, :data, :data_file, :data_vid, 0)
                ");
                $ins->execute([
                    ':module_id' => $module_id,
                    ':title'     => $title !== '' ? $title : null,
                    ':data'      => $text_body !== '' ? $text_body : null,
                    ':data_file' => $doc_path,
                    ':data_vid'  => $video_path,
                ]);
                $existing_id = (int)$pdo->lastInsertId();
            }
        }

        // Modül durumunu güncellemek istiyorsan
        $stmt_update = $pdo->prepare("UPDATE course_modules SET status = 'pending' WHERE id = ?");
        $stmt_update->execute([$module_id]);

        $pdo->commit();

        header("Location: view_curriculum.php?id=" . $course_id . "&status=updated");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        exit("Veritabanı hatası: " . $e->getMessage());
    }
}
