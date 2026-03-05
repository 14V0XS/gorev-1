<?php
// Bu dosya formdan gelen verileri işler.
// Örneğin, verileri veritabanına kaydedebilir veya bir e-posta gönderebilir.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Güvenlik için gelen verileri temizle
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = filter_var(trim($_POST['subject']), FILTER_SANITIZE_STRING);
    $message = filter_var(trim($_POST['message']), FILTER_SANITIZE_STRING);
    
    // Basit bir kontrol
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        
        // ---- BURADA E-POSTA GÖNDERME VEYA VERİTABANINA KAYDETME İŞLEMİ YAPILIR ----
        // Şimdilik sadece başarılı olduğuna dair bir yönlendirme yapıyoruz.
        
        // Başarılı olursa, kullanıcıyı ?status=success parametresiyle geri yönlendir.
        header("Location: contact.php?status=success");
        exit();
    }
}

// Bir hata olursa, kullanıcıyı ?status=error parametresiyle geri yönlendir.
header("Location: contact.php?status=error");
exit();
?>