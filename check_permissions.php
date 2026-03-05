<!DOCTYPE html>
<html lang="tr">
<head>
    <title>İzin Kontrolü</title>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">

    <style>
        body { font-family: sans-serif; padding: 20px; }
        .success { border-left: 5px solid green; padding: 10px; background-color: #f0fff0; margin-bottom: 10px; }
        .error { border-left: 5px solid red; padding: 10px; background-color: #fff0f0; margin-bottom: 10px; }
        code { background: #eee; padding: 2px 5px; border-radius: 3px; }
    </style>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-EDSVL8LRCY');
    </script>
</head>
<body>
    <h1>Sunucu İzin Kontrolü</h1>
    <?php
    // Projenin ana dizininde 'uploads' adında bir klasör arıyoruz.
    $upload_dir = __DIR__ . '/uploads';

    echo "<p>Kontrol edilen klasör yolu: <code>" . htmlspecialchars($upload_dir) . "</code></p>";

    // 1. 'uploads' klasörü var mı?
    if (is_dir($upload_dir)) {
        echo '<div class="success">BAŞARILI: "uploads" klasörü mevcut.</div>';

        // 2. 'uploads' klasörüne yazma izni var mı?
        if (is_writable($upload_dir)) {
            echo '<div class="success"><strong>TEST BAŞARILI:</strong> "uploads" klasörüne dosya yazılabilir. Sorun izinlerden kaynaklanmıyor olabilir.</div>';
        } else {
            echo '<div class="error"><strong>HATA:</strong> "uploads" klasörü mevcut ancak <strong>YAZILABİLİR DEĞİL!</strong> Dosya yükleme bu yüzden başarısız oluyor.</div>';
            echo '<p><strong>Çözüm:</strong> Hosting panelinizden veya bir FTP programı ile <code>uploads</code> klasörünün izinlerini (permissions) <code>755</code> veya <code>777</code> olarak değiştirin.</p>';
        }
    } else {
        echo '<div class="error"><strong>HATA:</strong> "uploads" klasörü bulunamadı!</div>';
        echo '<p><strong>Çözüm:</strong> Lütfen projenizin ana dizininde <code>uploads</code> adında bir klasör oluşturun.</p>';
    }
    ?>
</body>
</html>