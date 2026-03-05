<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$course_id = $_GET['id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }

$pdo = get_db_connection();
// Güvenlik: Bu kursun gerçekten bu takıma ait olup olmadığını kontrol et
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND team_db_id = ?");
$stmt->execute([$course_id, $_SESSION['team_db_id']]);
$course = $stmt->fetch();
if (!$course) { die("Hata: Bu kurs size ait değil veya bulunamadı."); }

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'approved'")->fetchAll(PDO::FETCH_ASSOC);
$page_title = "Kurs Bilgilerini Düzenle";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/create_course.css">
    </head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>

<div class="max-w-4xl mx-auto py-12 px-4">

    <h1 class="text-3xl font-bold text-gray-800 mb-8">1. Adım: Kurs Bilgilerini Düzenle</h1>

    <form id="course-form" action="save_course_step1.php" method="POST">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

        <div class="input-card">
            <label for="title">Kurs Adı</label>
            <input type="text" id="title" name="title" class="form-input" value="<?php echo htmlspecialchars($course['title']); ?>" required>
        </div>
        <div class="text-right mt-8">
            <button type="submit" class="btn text-lg">Değişiklikleri Kaydet ve 2. Adıma Geç</button>
        </div>
    </form>
</div>
<script>
    // ... Gerekli scriptler ...
</script>
</body>
</html>