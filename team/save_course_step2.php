<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$course_id = $_GET['id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }
// ... Kurs bilgilerini çekme ve güvenlik kontrolü ...
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>2. Adım: Görsel İçerikler</title>
    <link rel="stylesheet" href="../assets/css/manage_content.css">
    </head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">2. Adım: Görsel İçerikleri Yükleyin</h1>
        </div>
        <a href="panel.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Panele Geri Dön</a>
    </div>

    <form id="media-form" action="save_course_step2.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            </div>
        <div class="text-right">
            <button type="submit" class="btn text-lg">Kaydet ve 3. Adıma Geç</button>
        </div>
    </form>
</div>
<script>
    // Önceki yanıttaki çalışan Sürükle-Bırak ve Zorunluluk Kontrolü JavaScript kodları
</script>
</body>
</html>