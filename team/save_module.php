<?php
// File: modules/curriculum/save_curriculum.php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }

$projectRoot = $_SERVER['DOCUMENT_ROOT']; // /home/rookieve/public_html
require_once('../config.php');
$pdo = get_db_connection();

// ---- Helpers ----
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

function payload_to_correct_index(?string $payload): int
{
    if (!$payload) return 0;               // 0 = seçilmemiş
    $parts = explode('&', $payload);
    foreach ($parts as $i => $p) {
        if (str_ends_with($p, '=TRUE')) {  // örn: "45=FALSE&35=TRUE"
            return $i + 1;                 // 1..5
        }
    }
    return 0;
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
    // proje köküne göre göreli path
    $rel = ltrim(str_replace($projectRoot, '', $destFs), '/\\');
    return str_replace('\\', '/', $rel);
}

/** NEW: YouTube ID ayrıştırıcı (URL veya çıplak ID kabul eder) */
function parse_youtube_id(?string $input): ?string {
    if (!$input) return null;
    $input = trim($input);

    // çıplak ID (11–20 arası güvenli aralık)
    if (preg_match('/^[A-Za-z0-9_-]{10,20}$/', $input)) {
        return $input;
    }

    // URL gibi gelirse
    $parts = @parse_url($input);
    if (!$parts || empty($parts['host'])) return null;

    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    $query= [];
    if (!empty($parts['query'])) parse_str($parts['query'], $query);

    // youtu.be/<id>
    if (strpos($host, 'youtu.be') !== false) {
        $segments = array_values(array_filter(explode('/', $path)));
        return $segments[0] ?? null;
    }

    if (strpos($host, 'youtube.com') !== false) {
        // /watch?v=<id>
        if (!empty($query['v'])) return $query['v'];

        // /embed/<id>
        if (preg_match('~/embed/([^/?#]+)~', $path, $m)) return $m[1];

        // /shorts/<id>
        if (preg_match('~/shorts/([^/?#]+)~', $path, $m)) return $m[1];
    }

    return null;
}

// ---- Validate input ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Geçersiz istek yöntemi.');
$course_id = $_POST['course_id'] ?? null;
if (!$course_id) fail('course_id eksik');

$modules = $_POST['modules'] ?? [];
$files   = $_FILES['modules'] ?? null;
if (empty($modules) || !is_array($modules)) header('location: panel.php');

// ---- Course ownership check ----
$own = $pdo->prepare("SELECT id, team_db_id FROM courses WHERE id = ?");
$own->execute([$course_id]);
$course = $own->fetch(PDO::FETCH_ASSOC);
if (!$course || (int)$course['team_db_id'] !== (int)$_SESSION['team_db_id']) {
    fail('Bu kurs üzerinde işlem yetkiniz yok.', 403);
}

try {
    $pdo->beginTransaction();

    foreach ($modules as $mKey => $mVal) {
        $module_title = trim($mVal['title'] ?? '');
        if ($module_title === '') { continue; }

        // course_modules → insert
        $stmtInsertModule = $pdo->prepare("INSERT INTO course_modules (course_id, title) VALUES (?, ?)");
        $stmtInsertModule->execute([$course_id, $module_title]);
        $module_id = (int)$pdo->lastInsertId();

        // upload hedef dizin
        $destDir = $projectRoot . "/uploads/course_{$course_id}/module_{$module_id}";
        ensure_dir($destDir);

        // içerikler
        $contents = $mVal['contents'] ?? [];
        if (!is_array($contents)) $contents = [];

        foreach ($contents as $cKey => $cVal) {
            $rawType   = $cVal['type'] ?? null; // 'text' | 'video' | 'document' | 'youtube'
            $sort      = isset($cVal['sort_order']) ? (int)$cVal['sort_order'] : 0;
            $paragraph = $cVal['paragraph'] ?? null;      // text için
            $existing  = $cVal['existing_file'] ?? null;  // video/document için

            if (!$rawType) continue;

            /** NEW: DB tip eşlemesi */
            switch ($rawType) {
                case 'document': $dbType = 'doc'; break;
                case 'youtube':  $dbType = 'youtube';  break;
                default:         $dbType = $rawType;
            }

            $dataToStore = null;


            if ($rawType === 'question') {
                // formdan gelen blok
                $q = $cVal['question'] ?? [];

                $qText = trim((string)($q['text'] ?? ''));
                $qExp = trim((string)($q['explanation'] ?? ''));

                $choices = array_values(array_filter(array_map(
                    fn($x) => trim((string)$x),
                    (array)($q['choices'] ?? [])
                )));




                if ($qText === '' || count($choices) < 2) {
                    // eksik soru ise kaydetme, sonraki içeriğe geç
                    continue;
                }

                // doğru şık: payload'tan 1..5 çıkar
                $payload = (string)($q['answers_payload'] ?? '');
                $correct = payload_to_correct_index($payload); // 1..5, yoksa 0

                // 5 kolona pad’le
                $answers = array_pad($choices, 5, null);

                // 1) questions’a yaz
                $stmtQ = $pdo->prepare("
        INSERT INTO questions
            (module_id, text, answer1, answer2, answer3, answer4, answer5, correct_answer, explanation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
                $stmtQ->execute([
                    $module_id,
                    $qText,
                    $answers[0], $answers[1], $answers[2], $answers[3], $answers[4],
                    $correct,
                    $qExp
                ]);
                $questionId = (int)$pdo->lastInsertId();

                // 2) module_content'e bağla (type=quiz, content=questionId)
                $stmtMC = $pdo->prepare("
    INSERT INTO module_contents (module_id, type, title, data, sort_order)
    VALUES (?, 'quiz', NULL, ?, ?)
");
                $stmtMC->execute([$module_id, (string)$questionId, $sort]);

                continue; // bu içerik bitti
            }


            elseif ($rawType === 'text') {
                $clean = trim((string)$paragraph);
                if ($clean === '') continue;          // boş text atla
                $dataToStore = $paragraph;            // HTML içerik
            }
            elseif ($rawType === 'youtube') {
                /** NEW: YouTube URL/ID'yi al, doğrula ve kanonikleştir */
                $ytRaw = trim((string)($cVal['youtube_url'] ?? ''));
                if ($ytRaw === '') continue;          // boş yt alanı → atla
                $ytId = parse_youtube_id($ytRaw);
                if (!$ytId) {
                    // İstersen atlamak yerine hata da atabilirsin:
                    // throw new RuntimeException("Geçersiz YouTube bağlantısı: $ytRaw");
                    continue;
                }
                $dataToStore = "https://youtu.be/" . $ytId; // kanonik
            }
            else {
                // *** $_FILES INDEXLEMESİ ***
                // modules[mKey][contents][cKey][file]
                $fileArr = null;
                if ($files
                    && isset($files['name'][$mKey]['contents'][$cKey]['file'])
                ) {
                    $fileArr = [
                        'name'     => $files['name'][$mKey]['contents'][$cKey]['file'] ?? null,
                        'type'     => $files['type'][$mKey]['contents'][$cKey]['file'] ?? null,
                        'tmp_name' => $files['tmp_name'][$mKey]['contents'][$cKey]['file'] ?? null,
                        'error'    => $files['error'][$mKey]['contents'][$cKey]['file'] ?? UPLOAD_ERR_NO_FILE,
                        'size'     => $files['size'][$mKey]['contents'][$cKey]['file'] ?? 0,
                    ];
                }

                if ($fileArr && $fileArr['error'] === UPLOAD_ERR_OK) {
                    $mime = detect_mime($fileArr['tmp_name']);
                    $kind = ($rawType === 'video') ? 'video' : 'document';
                    if (!is_allowed_mime($mime, $kind)) {
                        throw new RuntimeException("Yasaklı dosya türü: {$mime} ({$kind})");
                    }
                    $relPath = move_uploaded_file_strict($fileArr, $destDir, $projectRoot);
                    $dataToStore = $relPath; // örn: uploads/course_1/module_10/abc.mp4
                } else {
                    // yeni dosya yoksa, eski path varsa onu koru
                    if ($existing && trim($existing) !== '') {
                        $dataToStore = trim($existing);
                    } else {
                        // dosya da yoksa, bu içerik boş—atla
                        continue;
                    }
                }
            }

            $stmtC = $pdo->prepare("
                INSERT INTO module_contents (module_id, type, title, data, sort_order)
                VALUES (?, ?, NULL, ?, ?)
            ");
            $stmtC->execute([$module_id, $dbType, $dataToStore, $sort]);
        }
    }

    $pdo->commit();
    header('Location: view_curriculum.php?id=' . (int)$course_id);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Video/Dosya/YouTube kaydı sırasında hata: " . htmlspecialchars($e->getMessage());
}
