<?php
// DİKKAT: Yeni şifrenizi bu satıra yazın
$yeniSifre = 'Sifrem123'; // Tırnak işaretlerinin arasına istediğiniz yeni şifreyi girin.

echo "<h1>PHP Şifre Hash Oluşturucu</h1>";

// Şifreyi PHP'nin standart ve güvenli BCRYPT algoritması ile hash'liyoruz
$hashed_password = password_hash($yeniSifre, PASSWORD_DEFAULT);

// Sonucu ekrana yazdırıyoruz ki kopyalayabilelim
echo "<p><strong>Belirlediğiniz Yeni Şifre:</strong> " . htmlspecialchars($yeniSifre) . "</p>";
echo "<p><strong>Veritabanına Kaydedilecek Hash Kodu:</strong></p>";

// Kopyalaması kolay olması için bir metin kutusunda gösteriyoruz
echo '<textarea rows="4" cols="80" readonly style="font-family: monospace; font-size: 14px; padding: 10px;">' . htmlspecialchars($hashed_password) . '</textarea>';

echo "<hr><p>Yukarıdaki metin kutusunda yer alan ve <strong>\$2y\$</strong> ile başlayan kodun tamamını kopyalayın.</p>";
?>