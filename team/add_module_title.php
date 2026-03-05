<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$course_id = $_GET['course_id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Yeni Bölüm Ekle - Başlık</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/manage_curriculum.css">
</head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>
<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Yeni Bölüm Ekle (1/2)</h1>
    <form action="save_module_title.php" method="POST">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="card">
            <label for="module_title" class="font-bold text-lg">Bölüm Başlığı</label>
            <p class="text-sm text-gray-500 mb-4">Kursunuz için yeni bir bölüm başlığı girin. İçeriğini bir sonraki adımda ekleyeceksiniz.</p>
            <input type="text" id="module_title" name="title" class="form-input p-3 border" required>
        </div>
        <div class="flex justify-between items-center mt-8">
            <a href="view_curriculum.php?id=<?php echo $course_id; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">İptal</a>
            <button type="submit" class="btn text-lg">İleri: İçerik Ekle <i data-lucide="arrow-right" class="ml-2"></i></button>
        </div>
    </form>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
