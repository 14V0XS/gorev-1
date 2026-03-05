<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) {
    exit('Yetkisiz erişim');
}
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$pdo = get_db_connection();

/**
 * PHP'nin karmaşık $_FILES dizisini daha mantıklı bir yapıya dönüştürür.
 * @param array $files_arr PHP'nin orijinal $_FILES['anahtar'] dizisi.
 * @return array Yeniden düzenlenmiş dosya dizisi.
 */
function reorganize_files_array($files_arr) {
    $reorganized = [];
    if (empty($files_arr) || !isset($files_arr['name'])) return [];

    // Bu yapı, name="contents[KEY1][KEY2]" şeklindeki form elemanları için çalışır
    foreach ($files_arr['name'] as $key1 => $val1) {
        foreach ($val1 as $key2 => $val2) {
            $reorganized[$key1][$key2] = [
                'name' => $files_arr['name'][$key1][$key2],
                'type' => $files_arr['type'][$key1][$key2],
                'tmp_name' => $files_arr['tmp_name'][$key1][$key2],
                'error' => $files_arr['error'][$key1][$key2],
                'size' => $files_arr['size'][$key1][$key2],
            ];
        }
    }
    return $reorganized;
}

/**
 * İçerik dosyasını (video/döküman) sunucuya yükler.
 * @param array $file_data Yüklenecek dosyaya ait bilgiler ('name', 'tmp_name', vs.).
 * @param int $course_id Kurs ID'si, dosyaları klasörlemek için kullanılır.
 * @return string|null Başarılıysa yüklenen dosyanın yolu, başarısızsa null.
 */
function upload_content_file($file_data, $course_id) {
    if ($file_data['error'] !== UPLOAD_ERR_OK) {
        return null; // Yüklemede hata var
    }

    // Bu betik (save_module_content.php) 'team' klasöründe olduğu için,
    // bir üst dizine ('../') çıkarak 'uploads' klasörüne erişiyoruz.
    $upload_dir = '../uploads/course_' . $course_id . '/';

    // Hedef klasör yoksa oluştur (yazma izniyle)
    if (!is_dir($upload_dir)) {
        // 'true' parametresi iç içe klasörlerin oluşturulmasına izin verir.
        mkdir($upload_dir, 0777, true);
    }

    // Güvenli ve benzersiz bir dosya adı oluştur
    $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
    $safe_filename = uniqid('content_', true) . '.' . $file_extension;
    $destination = $upload_dir . $safe_filename;

    if (move_uploaded_file($file_data['tmp_name'], $destination)) {
        // Veritabanına kaydederken başındaki '../' olmadan, kök dizine göreceli yolu kaydediyoruz.
        // Bu, BASE_URL ile birleştirildiğinde doğru yolu verir.
        return 'uploads/course_' . $course_id . '/' . $safe_filename;
    }

    return null; // Dosya taşıma hatası
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['module_id'])) {
    $module_id = $_POST['module_id'];
    $course_id = $_POST['course_id'];
    
    // Güvenlik kontrolü
    $stmt_check = $pdo->prepare("SELECT c.id FROM course_modules m JOIN courses c ON m.course_id = c.id WHERE m.id = ? AND c.team_db_id = ?");
    $stmt_check->execute([$module_id, $_SESSION['team_db_id']]);
    if ($stmt_check->fetchColumn() === false) {
        die("Bu işlemi yapma yetkiniz yok.");
    }
    
    $reorganized_files = isset($_FILES['contents']) ? reorganize_files_array($_FILES['contents']) : [];

    try {
        $pdo->beginTransaction();

        // Bu bölümün eski tüm içeriğini sil
        $stmt_delete = $pdo->prepare("DELETE FROM module_contents WHERE module_id = ?");
        $stmt_delete->execute([$module_id]);

        // Formdan gelen yeni/güncellenmiş içerikleri sırasıyla ekle
        if (isset($_POST['contents']) && is_array($_POST['contents'])) {
            $sort_order = 0;
            foreach ($_POST['contents'] as $key => $content_data) {
                $content_type = $content_data['type'];
                $title = $content_data['title'] ?? null;
                $data = '';

                if ($content_type === 'video' || $content_type === 'document') {
                    // YENİ bir dosya yüklendiyse, işle
                    if (isset($reorganized_files[$key]['file']) && $reorganized_files[$key]['file']['error'] == 0) {
                        $data = upload_content_file($reorganized_files[$key]['file'], $course_id);
                    } else {
                        // Yeni dosya yoksa, ESKİ dosya yolunu koru
                        $data = $content_data['existing_file'] ?? null;
                    }
                } else { // 'text'
                    $data = $content_data['paragraph'] ?? null;
                }
                
                // Sadece geçerli bir veri varsa veritabanına ekle
                if ($data !== null && $data !== '') {
                    $stmt_content = $pdo->prepare("INSERT INTO module_contents (module_id, content_type, title, data, sort_order, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                    $stmt_content->execute([$module_id, $content_type, $title, $data, $sort_order]);
                    $sort_order++;
                }
            }
        }
        
        // Bölümün durumunu 'pending' olarak güncelle
        $stmt_update = $pdo->prepare("UPDATE course_modules SET status = 'pending' WHERE id = ?");
        $stmt_update->execute([$module_id]);
        
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Veritabanı Hatası: " . $e->getMessage());
    }

    header("Location: view_curriculum.php?id=" . $course_id . "&status=saved");
    exit();
}
?>