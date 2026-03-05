<?php
// --- HATA AYIKLAMA ---
// Bu iki satır, "500 Internal Server Error" yerine gerçek hatayı ekrana yazar.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// --- GÜVENLİK KONTROLÜ ---
if (!isset($_SESSION['team_logged_in']) || !isset($_SESSION['team_db_id'])) { 
    die("KRİTİK HATA: Oturum bilgisi (Session) bulunamadı! Lütfen güvenlik için tekrar giriş yapmayı deneyin.");
}

// config.php dosyasını dahil et
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

// Formun doğru metotla gönderildiğini ve gerekli ID'nin geldiğini kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['course_id'])) {
    
    $pdo = get_db_connection(); // Veritabanı bağlantısını kur
    $course_id = $_POST['course_id'];
    $team_db_id = $_SESSION['team_db_id'];

    try {
        // Güvenlik: Bu kursun gerçekten bu takıma ait olup olmadığını son bir kez daha kontrol et
        $stmt_check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND team_db_id = ?");
        $stmt_check->execute([$course_id, $team_db_id]);
        if ($stmt_check->fetchColumn() === false) {
            die("Hata: Bu kurs üzerinde işlem yapma yetkiniz yok.");
        }

        // Veritabanı işlemlerini bir "transaction" içinde yap.
        // Bu sayede, işlemlerden herhangi biri başarısız olursa, hepsi geri alınır.
        $pdo->beginTransaction();

        // 1. Ana kursun durumunu 'pending' (onay bekliyor) olarak güncelle.
        $stmt_course = $pdo->prepare("UPDATE courses SET status = 'pending' WHERE id = ?");
        $stmt_course->execute([$course_id]);

        // 2. Bu kursa ait tüm bölümlerin (course_modules) durumunu da 'pending' olarak güncelle.
        $stmt_modules = $pdo->prepare("UPDATE course_modules SET status = 'pending' WHERE course_id = ?");
        $stmt_modules->execute([$course_id]);
        
        // 3. (İleride eklenecek) Bu kursa ait tüm içeriklerin (module_contents) durumunu da 'pending' yap.

        // Tüm işlemler başarılı olduysa, değişiklikleri onayla.
        $pdo->commit();

        // Her şey başarılıysa, takımı bir başarı mesajıyla paneline yönlendir
        header("Location: panel.php?status=course_submitted_for_review");
        exit();

    } catch (PDOException $e) {
        // Eğer herhangi bir adımda hata olursa, tüm işlemleri geri al.
        $pdo->rollBack();
        // Hatayı ekrana detaylı bir şekilde yazdır.
        die("Veritabanı Hatası: " . $e->getMessage());
    }
} else {
    // Geçersiz istek
    die("Hata: Bu sayfaya doğrudan erişilemez veya eksik bilgi gönderildi.");
}
?>