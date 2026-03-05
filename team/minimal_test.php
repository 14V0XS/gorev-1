<?php
// Hataları göster
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Form gönderilmiş mi diye bak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h1>Test Sonucu</h1>';

    echo '<h2>$_FILES Verisi:</h2><pre>';
    print_r($_FILES);
    echo '</pre>';

    // Dosya var mı ve hatasız mı geldi?
    if (isset($_FILES['test_dosyasi']) && $_FILES['test_dosyasi']['error'] === UPLOAD_ERR_OK) {

        // ../uploads/ klasörüne kaydetmeyi dene
        $hedef_klasor = __DIR__ . '/../uploads/';
        $hedef_dosya = $hedef_klasor . basename($_FILES['test_dosyasi']['name']);

        echo "<p>Dosya şuraya taşınmaya çalışılıyor: " . htmlspecialchars($hedef_dosya) . "</p>";

        if (move_uploaded_file($_FILES['test_dosyasi']['tmp_name'], $hedef_dosya)) {
            echo '<h2 style="color:green;">BAŞARILI! Dosya yüklendi.</h2>';
        } else {
            echo '<h2 style="color:red;">HATA: Dosya `move_uploaded_file` ile taşınamadı. İzin veya yol sorunu devam ediyor olabilir.</h2>';
        }
    } elseif (isset($_FILES['test_dosyasi'])) {
         echo '<h2 style="color:red;">HATA: Dosya yüklenirken bir hata oluştu. Hata Kodu: ' . $_FILES['test_dosyasi']['error'] . '</h2>';
    } else {
        echo '<h2 style="color:red;">HATA: $_FILES dizisinde beklenen dosya bulunamadı.</h2>';
    }

    exit; // İşlem sonrası dur
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Minimal Yükleme Testi</title>
    <style>body { font-family: sans-serif; padding: 20px; }</style>
</head>
<body>
    <h1>İzole Dosya Yükleme Testi</h1>
    <p>Bu test, diğer tüm kodlardan bağımsız olarak sunucunun dosya yükleme yeteneğini kontrol eder.</p>

    <form action="minimal_test.php" method="post" enctype="multipart/form-data">
        <p>Lütfen küçük bir dosya seçin (örn: bir resim, en fazla 5 MB):</p>
        <input type="file" name="test_dosyasi">
        <br><br>
        <input type="submit" value="Yüklemeyi Test Et">
    </form>
</body>
</html>