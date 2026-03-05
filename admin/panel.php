<?php 
$page_title = "Dashboard";
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);

$pdo = get_db_connection();
$total_teams = $pdo->query("SELECT count(*) FROM teams")->fetchColumn();
$course_count = $pdo->query("SELECT count(*) FROM courses WHERE status='approved'")->fetchColumn();


require_once 'admin_header.php';
require_once($projectRoot . '/admin/admin_sidebar.php');
$notification_count = $pending_approvals;

?>

    <main class="main-content">



        <div class="content-area">
            <div class="page-header">
                <h1>Hoş geldin, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
            </div>

            <div class="stats-grid mb-8">
                <div class="stat-card card"><div class="value"><?php echo $total_teams; ?></div><div class="label">Toplam Takım Sayısı</div></div>
                <div class="stat-card card"><div class="value" style="color: red;"><?php echo $pending_approvals; ?></div><div class="label">Bekleyen Onay İşlemi</div></div>
                <div class="stat-card card"><div class="value"><?=$course_count?></div><div class="label">Kurs Sayısı</div></div>
            </div>

            <div class="card">
                <h2>Son Aktiviteler</h2>
                <p class="text-gray-500 mt-4">Burada son işlemlerin bir listesi görünecek.</p>
            </div>
        </div>
    </main>

<?php require_once 'admin_footer.php'; ?>