<?php

session_start();
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }

$course_id = $_POST['course_id'] ?? null;
if (!$course_id) { die("Geçersiz kurs ID'si."); }

$pdo = get_db_connection();

// Kapak fotoğrafını işle
if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/covers/'; // Güvenli bir dizin seçin
    $uploadFile = $uploadDir . basename($_FILES['cover_image']['name']);

    // Dosya türünü kontrol etme (isteğe bağlı)
    $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
    if (getimagesize($_FILES['cover_image']['tmp_name']) === false) {
        die("Geçersiz resim dosyası.");
    }
    // Benzersiz bir dosya adı oluşturma (isteğe bağlı)
    $filename = uniqid() . "." . $imageFileType;
    $uploadFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadFile)) {
        $cover_image_url = BASE_URL . '/uploads/covers/' . $filename;
        $stmt = $pdo->prepare("UPDATE courses SET cover_image_url = :url WHERE id = :id AND team_db_id = :team_id");
        $stmt->execute([':url' => $cover_image_url, ':id' => $course_id, ':team_id' => $_SESSION['team_db_id']]);
    } else {
        echo "Kapak fotoğrafı yüklenirken bir hata oluştu.";
    }
}

// Tanıtım videosunu işle
if (isset($_FILES['intro_video']) && $_FILES['intro_video']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/videos/'; // Güvenli bir dizin seçin
    $uploadFile = $uploadDir . basename($_FILES['intro_video']['name']);

    $videoFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
    $allowedTypes = ['mp4', 'webm', 'ogg'];
    if (!in_array($videoFileType, $allowedTypes)) {
        echo "Geçersiz video dosyası türü (yalnızca mp4, webm, ogg).";
    } else {
        $filename = uniqid() . "." . $videoFileType;
        $uploadFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['intro_video']['tmp_name'], $uploadFile)) {
            $intro_video_url = BASE_URL . '/uploads/videos/' . $filename;
            $stmt = $pdo->prepare("UPDATE courses SET intro_video_url = :url WHERE id = :id AND team_db_id = :team_id");
            $stmt->execute([':url' => $intro_video_url, ':id' => $course_id, ':team_id' => $_SESSION['team_db_id']]);
        } else {
            echo "Tanıtım videosu yüklenirken bir hata oluştu.";
        }
    }
}

// İşlem tamamlandıktan sonra bölüm yönetimi sayfasına yönlendir
header("Location: manage_curriculum.php?id=" . $course_id);
exit();
?>