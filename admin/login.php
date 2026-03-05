<?php
session_start();
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');

// Hata mesajı için boş bir değişken tanımla
$error_message = null;

// Form gönderilmişse (kullanıcı "Giriş Yap" butonuna bastıysa)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $pdo = get_db_connection();

    $username = $_POST['username'];
    $password = $_POST['password'];




    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // Kullanıcı bulunduysa VE şifre doğruysa
    if ($admin && password_verify($password, $admin['password_hash'])) {
        // GİRİŞ BAŞARILI
        $_SESSION['admin_logged_in'] = true;

        $_SESSION['admin_username'] = $admin['username'];
        header("Location: panel.php"); // Ana panele yönlendir
        exit();
    } else {
        // GİRİŞ BAŞARISIZ
        $error_message = "Kullanıcı adı veya şifre yanlış.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli Girişi - FRC Rookieverse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif; background-color: #f7f7f7; }
        @font-face { font-family: "Sakana"; src: url("../assets/fonts/Sakana.ttf") format("truetype"); }
        .rookieverse { font-family: "Sakana", sans-serif !important; font-weight: bold; font-size: 2.5rem; color: #E5AE32; text-decoration: none; }
        .admin-subtitle { font-family: "Sakana", sans-serif !important; font-size: 1rem; color: #E5AE32; position: absolute; bottom: 0; right: 0; }
        .logo-container { position: relative; display: inline-block; padding-bottom: 1rem; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
<div class="w-full max-w-md p-4">
    <div class="flex justify-center mb-8">
        <div class="logo-container">
            <a href="../index.php"><span class="rookieverse">FRC ROOKIEVERSE</span></a>
            <span class="admin-subtitle">admin</span>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg p-8">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Yönetim Paneli Girişi</h1>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <strong class="font-bold">Hatalı giriş.</strong>
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="font-medium text-gray-700">Kullanıcı Adı</label>
                <input id="username" name="username" type="text" required class="mt-2 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:border-[#E5AE32] focus:ring-2 focus:ring-[#E5AE32]/50">
            </div>
            <div>
                <label for="password" class="font-medium text-gray-700">Şifre</label>
                <input id="password" name="password" type="password" required class="mt-2 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:border-[#E5AE32] focus:ring-2 focus:ring-[#E5AE32]/50">
            </div>
            <div>
                <button type="submit" class="w-full bg-[#E5AE32] hover:bg-[#c4952b] text-white font-bold py-3 text-lg rounded-lg transition-all duration-300">Giriş Yap</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>