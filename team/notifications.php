<?php

session_start();

$projectRoot = dirname(__DIR__);

require_once $projectRoot . '/config.php';

$pdo = get_db_connection();

$count = $pdo->prepare("SELECT 

    COUNT(*) AS total,

    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread

    FROM notifications WHERE team_id = ?");

$count->execute([$_SESSION['team_db_id']]);

list($totalRows, $unreadTotal) = $count->fetch(PDO::FETCH_NUM);require_once($projectRoot . '/config.php');



if (!isset($_SESSION['team_logged_in'])) {

    header('Location: ../team-login.php');

    exit();

}



$pdo = get_db_connection();

$teamId = (int)$_SESSION['team_db_id'];

$page_title = "Bildirimler";



// --- İşlemler (okundu yap / hepsini okundu yap) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['mark_all_read'])) {

        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE team_id = ?");

        $stmt->execute([$teamId]);

        header('Location: notifications.php');

        exit();

    }



    if (isset($_POST['toggle_read'], $_POST['nid'])) {

        $nid = (int)$_POST['nid'];

        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 - is_read WHERE id = ? AND team_id = ?");

        $stmt->execute([$nid, $teamId]);

        header('Location: notifications.php');

        exit();

    }

}



// --- Sayfalama ---

$perPage = 20;

$page = max(1, (int)($_GET['p'] ?? 1));

$offset = ($page - 1) * $perPage;



// --- Bildirimleri çek ---

$stmt = $pdo->prepare("

    SELECT id, content_id, type, `action`, is_read, notified_at

    FROM notifications

    WHERE team_id = :fuh

    ORDER BY notified_at DESC

");

$stmt->bindValue(':fuh', $teamId, PDO::PARAM_INT);

$stmt->execute();

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);



// --- Toplam bildirim ve okunmamış sayısı ---

$count = $pdo->prepare("SELECT 

    COUNT(*) AS total,

    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread

    FROM notifications WHERE team_id = ?");

$count->execute([$teamId]);

list($totalRows, $unreadTotal) = $count->fetch(PDO::FETCH_NUM);



$totalPages = max(1, ceil(($totalRows ?: 0) / $perPage));

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <title><?php echo $page_title ?? 'Admin Paneli'; ?> - FRC Rookieverse</title>

    <link rel="stylesheet" href="<?=BASE_URL?>/assets/css/panel2.css">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>@font-face { font-family: "Sakana"; src: url("../assets/fonts/Sakana.ttf") format("truetype"); }</style>

</head>

<body>

<div class="panel-layout">

<script>

    try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}

    (function(){

        var here = 'notifications.php';

        document.querySelectorAll('.sidebar-nav a').forEach(a=>{

            if(a.getAttribute('href') === here){ a.classList.add('active'); }

        });

    })();

</script>



<style>

    .badge {

        display:inline-block;

        padding:0.25rem 0.5rem;

        border-radius:9999px;

        font-size:0.75rem;

        font-weight:600;

    }

    .badge-approve { background:#dcfce7; color:#166534; }

    .badge-reject { background:#fee2e2; color:#991b1b; }

    .row-unread { background:#fffdf2; }

    .note-time { font-size:.8rem; color:#666; }





    .notification-button {

        position: relative;

        background: none;

        border: none;

        color: black;

        cursor: pointer;

        padding: 0;

    }



    .notification-button .lucide {

        color: black; /* İkon rengini siyah yap */

        width: 24px;

        height: 24px;

        margin-right: 15px;

    }

    .notification-badge {

        position: absolute;

        top: -5px;

        right: 8px;

        background-color: #ffc107;

        color: black;

        font-size: 10px;

        font-weight: bold;

        border-radius: 50%;

        width: 18px;

        height: 18px;

        display: flex;

        align-items: center;

        justify-content: center;

        border: 1px solid white;

    }

    /* ====== LAYOUT (panel/profil ile tutarlı) ====== */

    :root{ --sidebar-w: 280px; }



    html, body { height: 100%; }

    .main-wrapper{

        display:grid;

        grid-template-columns: var(--sidebar-w) 1fr;

        min-height: 100vh;

    }



    /* Hamburger: küçük ekranda görünür, büyükte gizle */

    #sidebarToggle { display: inline-flex; background:#fff; }

    @media (min-width:1024px){ #sidebarToggle{ display:none; } }



    /* Büyük ekran: sidebar sabit */

    @media (min-width:1024px){

        .sidebar{

            position: sticky; top: 0; height: 100vh;

            transform: translateX(0) !important; transition: none;

            box-shadow: inset -1px 0 0 rgba(0,0,0,.05);

        }

        .sidebar-overlay{ display:none !important; }

    }



    /* Küçük ekran: çekme menü */

    @media (max-width:1023px){

        .main-wrapper{ grid-template-columns: 1fr; }

        .sidebar{

            position: fixed; inset: 0 auto 0 0;

            width: 50vw;

            background:#fff; z-index:50; border-right:1px solid #e5e7eb;

            transform: translateX(-100%); transition: transform .25s ease;

            overflow-y:auto;

        }

        .sidebar.open{ transform: translateX(0); }



        .sidebar-overlay{

            position: fixed; inset:0; background:rgba(0,0,0,.35);

            backdrop-filter: blur(1px); z-index:40; opacity:0;

            pointer-events:none; transition:opacity .2s ease;

        }

        .sidebar-overlay.show{ opacity:1; pointer-events:auto; }



        .top-bar{ position: sticky; top:0; z-index:30; background:#fff; }

    }



    /* Üst bar ve genel boşluklar */

    .top-bar{

        display:flex; align-items:center; justify-content:space-between;

        padding:12px 16px; border-bottom:1px solid #eee;

    }

    .main-content{ padding:16px; }

    @media (min-width:1024px){ .main-content{ padding:24px; } }



    /* ====== OKUNAKLILIK ====== */

    html { font-size: 16px; }

    @media (min-width:1280px){ html{ font-size:17px; } }

    body { font-size:1rem; line-height:1.6; }

    .sidebar-nav a{ display:flex; align-items:center; gap:10px; font-size:1rem; padding:12px 14px; }

    .sidebar-nav i.lucide{ width:20px; height:20px; flex:0 0 20px; }

    .btn{ font-size:.98rem; padding:10px 14px; border-radius:10px; min-height:38px; }

    .btn.btn-sm{ font-size:.95rem; padding:8px 12px; min-height:34px; }

    .btn i.lucide{ width:18px; height:18px; }

    .notification-button .lucide{ width:22px; height:22px; }



    /* ====== TABLO (yatay kaydırma + mobil kart) ====== */

    .table-wrap{ width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;

        border:1px solid #e5e7eb; border-radius:10px; }

    table{ width:100%; border-collapse: collapse; }

    thead th{

        text-align:left; font-weight:700; font-size:.95rem; color:#374151;

        border-bottom:1px solid #e5e7eb; padding:12px 14px;

    }

    tbody td{ border-bottom:1px solid #f1f5f9; padding:12px 14px; vertical-align:middle; }



    /* 640px altı: kart formatı */

    @media (max-width:640px){

        table.responsive-stack thead{ display:none; }

        table.responsive-stack, table.responsive-stack tbody,

        table.responsive-stack tr, table.responsive-stack td{

            display:block; width:100%;

        }

        table.responsive-stack tr{ border-bottom:1px solid #e5e7eb; padding:8px 10px; }

        table.responsive-stack td{ padding:8px 10px; }

        table.responsive-stack td::before{

            content: attr(data-label);

            display:block; font-size:11px; font-weight:600; color:#6b7280;

            margin-bottom:4px; text-transform:uppercase; letter-spacing:.02em;

        }

        /* İşlem butonları daha rahat hizalansın */

        .actions-cell > *{ display:inline-flex; margin:4px 6px 0 0; }

    }



    /* Satır vurguları ve rozetler (mevcut stillerle uyumlu) */

    .row-unread{ background:#fffdf2; }

    .badge{ display:inline-block; padding:.25rem .5rem; border-radius:9999px; font-size:.75rem; font-weight:600; }

    .badge-approve{ background:#dcfce7; color:#166534; }

    .badge-reject{ background:#fee2e2; color:#991b1b; }

    .note-time{ font-size:.85rem; color:#666; }



</style>



<div class="main-wrapper">



<aside class="sidebar">

    <?php

    if (!isset($_SESSION['admin_panel_view'])):

        ?>

        <a class="flex items-center space-x-2" href="<?php echo BASE_URL; ?>">

            <span class="rookieverse">FRC ROOKIEVERSE</span>

        </a>

    <?php endif;?>



    <div class="sidebar-profile">

        <h2>Hoş Geldin,</h2>

        <p>Takım #<?php echo htmlspecialchars($_SESSION['team_number']); ?></p>

    </div>

    <nav class="sidebar-nav">

        <a href="panel.php"><i data-lucide="layout-dashboard"></i> Panelim</a>

        <a href="create_course.php"><i data-lucide="plus-square"></i> Yeni Kurs Oluştur</a>

        <a href="profile.php"><i data-lucide="settings"></i> Profilimi Düzenle</a>

        <a href="notifications.php" class="active"><i data-lucide="bell"></i> Bildirimler</a>

        <a href="list_questions.php" ><i data-lucide="message-square"></i> Soru Yönetimi</a>

        <a href="support_requests.php"><i data-lucide="life-buoy"></i> Destek</a>



        <?php

        if (!isset($_SESSION['admin_panel_view'])):

            ?>

            <a href="logout.php" class="logout-link"><i data-lucide="log-out"></i> Güvenli Çıkış</a>

        <?php endif;?>

    </nav>

</aside>



<main style="margin-left: 0px; padding: 0px" class="main-content">

    <div class="top-bar">

        <button id="sidebarToggle"

                class="inline-flex items-center justify-center w-10 h-10 rounded-md border"

                aria-label="Menüyü aç/kapat" type="button">

            <i data-lucide="menu"></i>

        </button>

        <div class="font-bold">Bildirimler</div>

        <div class="actions">

            <button id="notif-button" class="notification-button">

                <i data-lucide="bell"></i>

                <?php if ($unreadTotal > 0): ?>

                    <div class="notification-badge"><?= htmlspecialchars($unreadTotal) ?></div>

                <?php endif; ?>

            </button>



            <?php

            if (isset($_SESSION['admin_panel_view'])):

                ?>

                <a href="../admin/accessTeamPanel.php?exit=1" class="btn btn-sm"><i data-lucide="arrow-left"></i>Admin Paneline Dön</a>

            <?php endif;?>

        </div>

    </div>



    <div class="content-area">

        <div class="card mb-4 flex justify-between items-center">

            <h2>Toplam <?php echo (int)$totalRows; ?> bildirim</h2>

            <?php if ($unreadTotal > 0): ?>

                <form method="post">

                    <button name="mark_all_read" value="1" class="btn btn-sm"><i data-lucide="check"></i> Tümünü okundu yap</button>

                </form>

            <?php endif; ?>

        </div>



        <div class="card">

            <?php if (!$notifications): ?>

                <p>Henüz bir bildirimin yok.</p>

            <?php else: ?>

                <div class="table-wrap">

                    <table class="responsive-stack">

                        <thead>

                        <tr>

                            <th>Tür</th>

                            <th>Başlık</th>

                            <th>Durum</th>

                            <th>Tarih</th>

                            <th>İşlemler</th>

                        </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($notifications as $n):
                        $type = (string)($n['type'] ?? '');
                        $gotoUrl = '#';
                        $title   = '—';
                        $details = null;

                        if ($type === 'module') {
                            $details = getModule((int)$n['content_id']);
                            if (is_array($details)) {
                                $title  = isset($details['title']) ? (string)$details['title'] : '—';
                                // id yoksa content_id'ye geri düş
                                $mid    = isset($details['id']) ? (int)$details['id'] : (int)$n['content_id'];
                                $gotoUrl = 'edit_module_content.php?id=' . $mid;
                            } else {
                                // beklenmeyen dönüş → yine de bir fallback ver
                                $gotoUrl = 'edit_module_content.php?id=' . (int)$n['content_id'];
                            }
                        } elseif ($type === 'course') {
                            $details = getCourseDetailsById((int)$n['content_id']);
                            if (is_array($details)) {
                                $title  = isset($details['title']) ? (string)$details['title'] : '—';
                                $cid    = isset($details['id']) ? (int)$details['id'] : (int)$n['content_id'];
                                $gotoUrl = 'editCourse.php?id=' . $cid;
                            } else {
                                $gotoUrl = 'editCourse.php?id=' . (int)$n['content_id'];
                            }
                        } else {
                            continue;
                        }

                        $rowClass   = $n['is_read'] ? '' : 'row-unread';
                        $badgeClass = $n['action'] === 'approve' ? 'badge-approve' : 'badge-reject';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td data-label="Tür"><?php echo htmlspecialchars($type); ?></td>
                            <td data-label="Başlık"><?php echo htmlspecialchars($title); ?></td>
                            <td data-label="Durum"><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($n['action']); ?></span></td>
                            <td data-label="Tarih" class="note-time">
                                <?php echo $n['notified_at'] ? date('d.m.Y H:i', strtotime($n['notified_at'])) : '—'; ?>
                            </td>
                            <td data-label="İşlemler" class="actions-cell">
                                <a class="btn btn-sm" href="<?= $gotoUrl; ?>">
                                    <i data-lucide="link"></i> <?php echo ($type==='course'?'Kursa Git':'Modüle Git'); ?>
                                </a>
                                <form method="post" class="inline">
                                    <input type="hidden" name="nid" value="<?php echo (int)$n['id']; ?>">
                                    <button class="btn btn-sm" name="toggle_read" value="1">
                                        <?php echo $n['is_read'] ? 'Okunmadı yap' : 'Okundu yap'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>



                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>



    </div>

</main>

</div>

<div class="sidebar-overlay"></div>

<script>

        document.addEventListener('DOMContentLoaded', () => {

            try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}



            // Sidebar active link (opsiyonel: zaten var gibi)

            (function(){

                const here = 'notifications.php';

                document.querySelectorAll('.sidebar-nav a').forEach(a=>{

                    if ((a.getAttribute('href')||'').endsWith(here)) a.classList.add('active');

                });

            })();



            // Bildirim ikonu -> notifications’a git (aynı sayfa ama davranış tutarlı kalsın)

            const notifBtn = document.getElementById('notif-button');

            if (notifBtn) notifBtn.addEventListener('click', () => { window.location.href = 'notifications.php'; });



            // Sidebar toggle

            const sidebar = document.querySelector('.sidebar');

            const overlay = document.querySelector('.sidebar-overlay');

            const toggle  = document.getElementById('sidebarToggle');

            if (!sidebar || !overlay || !toggle) return;



            const open = () => {

                sidebar.classList.add('open');

                overlay.classList.add('show');

                document.body.style.overflow = 'hidden';

                const icon = toggle.querySelector('i.lucide');

                if (icon){ icon.setAttribute('data-lucide', 'x'); try { lucide.createIcons(); } catch(_){} }

            };

            const close = () => {

                sidebar.classList.remove('open');

                overlay.classList.remove('show');

                document.body.style.overflow = '';

                const icon = toggle.querySelector('i.lucide');

                if (icon){ icon.setAttribute('data-lucide', 'menu'); try { lucide.createIcons(); } catch(_){} }

            };



            toggle.addEventListener('click', () => {

                if (sidebar.classList.contains('open')) close(); else open();

            });

            overlay.addEventListener('click', close);

            document.addEventListener('keydown', e => { if (e.key === 'Escape' && sidebar.classList.contains('open')) close(); });

            sidebar.querySelectorAll('a').forEach(a=>{

                a.addEventListener('click', ()=>{ if (window.matchMedia('(max-width:1023px)').matches) close(); });

            });

        });

</script>



<?php require_once '../admin/admin_footer.php'; ?>

