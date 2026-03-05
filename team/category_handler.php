<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['team_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit();
}
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

$input = json_decode(file_get_contents('php://input'), true);
$categoryName = isset($input['name']) ? trim($input['name']) : '';

if (empty($categoryName)) {
    echo json_encode(['status' => 'error', 'message' => 'Kategori adı boş olamaz.']);
    exit();
}

try {
    $pdo = get_db_connection();

    // 1. Bu kategori adının var olup olmadığını ve durumunu kontrol et
    $stmt_check = $pdo->prepare("SELECT id, name, status FROM categories WHERE UPPER(name) = UPPER(?)");
    $stmt_check->execute([$categoryName]);
    $existingCategory = $stmt_check->fetch(PDO::FETCH_ASSOC);

    // 2. Eğer kategori varsa durumuna göre özel mesaj döndür
    if ($existingCategory) {
        if ($existingCategory['status'] === 'rejected') {
            // Durumu reddedilmişse
            echo json_encode(['status' => 'error', 'message' => 'Bu kategori daha önce reddedilmiştir. Lütfen başka bir kategori adı giriniz.']);
        } else {
            // Durumu 'approved' veya 'pending' ise
            echo json_encode(['status' => 'error', 'message' => 'Bu kategori zaten mevcut veya onay bekliyor.']);
        }
        exit();
    }

    // 3. Kategori yoksa, yeni öneriyi 'pending' durumuyla ekle
    $stmt_insert = $pdo->prepare("INSERT INTO categories (name, status) VALUES (?, 'pending')");
    $stmt_insert->execute([$categoryName]);
    
    $lastId = $pdo->lastInsertId();
    echo json_encode([
        'status' => 'success',
        'id' => $lastId,
        'name' => htmlspecialchars($categoryName)
    ]);

} catch (PDOException $e) {
    // error_log($e->getMessage()); 
    echo json_encode(['status' => 'error', 'message' => 'Beklenmedik bir veritabanı hatası oluştu.']);
}
?>