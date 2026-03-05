<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$course_id = $_GET['id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }

$pdo = get_db_connection();
$stmt_course = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND team_db_id = ?");
$stmt_course->execute([$course_id, $_SESSION['team_db_id']]);
$course = $stmt_course->fetch();
if (!$course) { die("Hata: Bu kurs size ait değil veya bulunamadı."); }

// KURAL: Kursun zaten bir modülü varsa, düzenleme sayfasına yönlendir.
$stmt_check_modules = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
$stmt_check_modules->execute([$course_id]);
if ($stmt_check_modules->fetchColumn() > 0) {
    header('Location: edit_curriculum.php?id=' . $course_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>İlk Bölümü Oluştur - <?php echo htmlspecialchars($course['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <style>
        :root {
            --primary-color: #E5AE32; --primary-hover: #c4952b; --background-color: #f7f7f7;
            --card-background: #ffffff; --text-dark: #0f172a; --text-light: #64748b; --border-color: #e2e8f0;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background-color: var(--background-color); color: var(--text-dark); }
        .card { background-color: var(--card-background); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background-color: var(--primary-color); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 1rem; font-weight: 600; text-decoration: none; transition: background-color 0.2s; }
        .btn:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: #475569; }
        .hidden { display: none !important; }
        .form-input-fancy { width: 100%; padding: 0.75rem 1rem; border: 2px solid var(--border-color); border-radius: 0.5rem; font-size: 1.125rem; background-color: var(--background-color); transition: border-color 0.2s, box-shadow 0.2s; }
        .form-input-fancy:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(229, 174, 50, 0.3); outline: none; }
    </style>
</head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">3. Adım: Kurs İçeriğini Oluştur</h1>
            <p class="text-gray-600">Bu kurs için ilk bölümü oluşturun.</p>
        </div>
        <a href="panel.php" class="btn btn-secondary">Panele Geri Dön</a>
    </div>

    <form id="curriculum-form" action="save_curriculum.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div id="add-module-prompt" class="card text-center">
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Harika! Şimdi ilk bölümü ekleyelim.</h3>
            <p class="mb-4">Bu bölüm, kursunuzun başlangıç noktası olacak.</p>
            <div class="max-w-md mx-auto">
                <input type="text" id="new_module_title" name="modules[new_1][title]" class="form-input-fancy text-center" placeholder="İlk Bölümün Adı (Örn: Giriş)" required>
                 <div class="mt-6">
                    <p class="text-gray-500 text-sm">Bölüm adını girdikten sonra, içeriği (metin, video, döküman) bir sonraki adımda ekleyebileceksiniz.</p>
                    <button type="submit" class="btn mt-4"><i data-lucide="save"></i> Bölümü Kaydet ve İçerik Ekle</button>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>