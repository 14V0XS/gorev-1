<?php
/** team_course_questions_printable.php
 *  Server-side render + print friendly
 */

session_start();
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';
if (!isset($_SESSION['team_logged_in'])) {header('location: ../team-login.php');}

$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
$pdo = get_db_connection();
$count = $pdo->prepare("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread
    FROM notifications WHERE team_id = ?");
$count->execute([$_SESSION['team_db_id']]);
list($totalRows, $unreadTotal) = $count->fetch(PDO::FETCH_NUM);

$pdo        = get_db_connection();
$teamDbId   = $_SESSION['team_db_id'] ?? null;
$teamNumber = $_SESSION['team_number'] ?? null;
if (!$teamDbId) { die('Takım kimliği bulunamadı.'); }

/** CSRF */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$CSRF = $_SESSION['csrf'];

/** Kurs listesi */
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE team_db_id = ? ORDER BY id DESC");
$stmt->execute([$teamDbId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/** ---- Actions (POST) ---- */
$flash = null;

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $flash = ['type'=>'error','msg'=>'CSRF doğrulaması başarısız.'];
    } else try {
        $action = $_POST['action'];

        if ($action === 'toggle_visibility') {
            $qid = (int)($_POST['question_id'] ?? 0);
            $q = $pdo->prepare("SELECT q.is_approved
                                FROM course_questions q
                                JOIN courses c ON c.id=q.course_id
                                WHERE q.id=? AND c.team_db_id=? LIMIT 1");
            $q->execute([$qid, $teamDbId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if (!$row) { $flash = ['type'=>'error','msg'=>'Soru bulunamadı.']; }
            else {
                $new = $row['is_approved'] ? 0 : 1;
                $u = $pdo->prepare("UPDATE course_questions SET is_approved=? WHERE id=?");
                $u->execute([$new, $qid]);
                $flash = ['type'=>'ok','msg'=> $new? 'Soru yayına alındı.':'Soru gizlendi.'];
            }
            header("Location: list_questions.php");
        }
        if ($action === 'delete_reply') {
            $rid = (int)($_POST['reply_id'] ?? 0);
            if ($rid <= 0) {
                $flash = ['type'=>'error','msg'=>'Geçersiz yanıt.'];
                header("Location: list_questions.php");
                exit;
            }

            // Bu yanıt gerçekten bu takımın kursuna ait mi kontrol et
            $chk = $pdo->prepare("
        SELECT 1
        FROM course_question_replies r
        JOIN course_questions q ON q.id = r.question_id
        JOIN courses c ON c.id = q.course_id
        WHERE r.id = ? AND c.team_db_id = ?
        LIMIT 1
    ");
            $chk->execute([$rid, $teamDbId]);

            if (!$chk->fetchColumn()) {
                $flash = ['type'=>'error','msg'=>'Yetkisiz işlem veya kayıt bulunamadı.'];
                header("Location: list_questions.php");
                exit;
            }

            // Sil
            $del = $pdo->prepare("DELETE FROM course_question_replies WHERE id = ? LIMIT 1");
            $del->execute([$rid]);

            $flash = ['type'=>'ok','msg'=>'Yanıt silindi.'];
            header("Location: list_questions.php");
            exit;
        }

        if ($action === 'add_reply') {
            $qid  = (int)($_POST['question_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            $name = trim($_POST['responder_name'] ?? 'Eğitmen');
            if ($body===''){ $flash = ['type'=>'error','msg'=>'Boş yanıt gönderilemez.']; header("Location: list_questions.php"); }

            $chk = $pdo->prepare("SELECT 1
                                  FROM course_questions q
                                  JOIN courses c ON c.id=q.course_id
                                  WHERE q.id=? AND c.team_db_id=? LIMIT 1");
            $chk->execute([$qid, $teamDbId]);
            if (!$chk->fetchColumn()) { $flash = ['type'=>'error','msg'=>'Yetkisiz işlem.']; header("Location: list_questions.php");}

            $ins = $pdo->prepare("INSERT INTO course_question_replies (question_id, team_db_id, responder_name, body)
                                  VALUES (?,?,?,?)");
            $ins->execute([$qid, $teamDbId, ($name ?: 'Eğitmen'), $body]);
            $flash = ['type'=>'ok','msg'=>'Yanıt eklendi.'];
            header("Location: list_questions.php");

        }

        $flash = ['type'=>'error','msg'=>'Bilinmeyen işlem.'];
    } catch(Exception $e){
        $flash = ['type'=>'error','msg'=>'Sunucu hatası.'];
    }
}


/** ---- Filters (GET) ---- */
// "All" varsayılan; yoksa tüm kursları listele
$selectedCourse = $_GET['course'] ?? 'all';
$isAll = ($selectedCourse === 'all' || $selectedCourse === '');
$defaultCourseId = $isAll ? null : (int)$selectedCourse;

$status  = $_GET['status'] ?? 'all'; // all|visible|hidden|unanswered|answered
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(5, (int)($_GET['per_page'] ?? 10)));
$offset  = ($page-1)*$perPage;

/** Kurs sahiplik kontrolü (sadece tek kurs seçiliyken) */
if (!$isAll) {
    $own = $pdo->prepare("SELECT 1 FROM courses WHERE id=? AND team_db_id=?");
    $own->execute([$defaultCourseId, $teamDbId]);
    if (!$own->fetchColumn()) { die("Bu kurs size ait değil."); }
}

/** ---- Query build ---- */
$courseJoin = "JOIN courses c ON c.id = q.course_id";
$replyJoin  = "LEFT JOIN (
    SELECT question_id, COUNT(*) rcnt
    FROM course_question_replies
    GROUP BY question_id
) r ON r.question_id = q.id";

$where  = "c.team_db_id = :tid AND c.is_deleted = 0";
$params = [':tid'=>$teamDbId];

if (!$isAll) { $where .= " AND q.course_id = :cid"; $params[':cid'] = $defaultCourseId; }
if ($status==='visible')    { $where .= " AND q.is_approved=1"; }
if ($status==='hidden')     { $where .= " AND q.is_approved=0"; }
if ($status==='answered')   { $where .= " AND COALESCE(r.rcnt,0) > 0"; }
if ($status==='unanswered') { $where .= " AND COALESCE(r.rcnt,0) = 0"; }
if ($search!=='')           { $where .= " AND q.body LIKE :s"; $params[':s'] = "%$search%"; }

/** Count */
$countSql = "SELECT COUNT(*) FROM course_questions q $courseJoin $replyJoin WHERE $where";
$cst = $pdo->prepare($countSql); $cst->execute($params); $total = (int)$cst->fetchColumn();
$pages = max(1, (int)ceil($total/$perPage));
if ($page > $pages) { $page = $pages; $offset = ($page-1)*$perPage; }

/** Page items */
$sql = "SELECT q.id, q.body, q.is_approved,
               DATE_FORMAT(q.created_at,'%d.%m.%Y %H:%i') created_at,
               COALESCE(r.rcnt,0) reply_count
        FROM course_questions q
        $courseJoin
        $replyJoin
        WHERE $where
        ORDER BY q.id DESC
        LIMIT $perPage OFFSET $offset";
$st = $pdo->prepare($sql); $st->execute($params);
$items = $st->fetchAll(PDO::FETCH_ASSOC);

/** Replies prefetch (hepsini önden yükle; sadece gizli göstereceğiz) */
$repliesByQ = [];
if (!empty($items)) {
    $ids = array_map(fn($x)=>(int)$x['id'], $items);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $r = $pdo->prepare("
    SELECT id, question_id, responder_name, body,
           DATE_FORMAT(created_at,'%d.%m.%Y %H:%i') created_at
    FROM course_question_replies
    WHERE question_id IN ($in)
    ORDER BY id ASC
");
    $r->execute($ids);
    foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $qid = (int)$row['question_id'];
        $repliesByQ[$qid][] = $row;
    }
}

/** Helper */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
function qs($over=[]) {
    $base = $_GET; foreach ($over as $k=>$v) { $base[$k]=$v; }
    return '?'.http_build_query($base);
}

require_once 'team_header.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Soru Yönetimi (Yazdırılabilir) - Takım #<?php echo h($teamNumber); ?></title>
    <style>
        .container{max-width:1160px;margin:0 auto;padding:16px;}
        .filters{display:grid;grid-template-columns:1fr 1fr 2fr auto;gap:12px;align-items:end}
        .filters label{font-size:12px;color:#555}
        .filters input,.filters select{padding:8px;border:1px solid #ddd;border-radius:8px}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer}
        .btn-primary{background:#111;color:#fff;border-color:#111}
        .btn-outline{background:#fff}
        .badge{font-size:12px;padding:4px 8px;border-radius:999px;font-weight:600}
        .badge-green{background:#E6F4EA;color:#18794E}
        .badge-yellow{background:#FEF7CD;color:#936B00}
        .badge-blue{background:#E7F0FE;color:#174EA6}
        .badge-gray{background:#F2F4F7;color:#344054}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px;border-bottom:1px solid #eee;vertical-align:top}
        th{background:#fafafa;text-align:left}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .print-hint{font-size:12px;color:#666}
        @media print{
            .no-print{display:none !important}
            body{background:#fff}
            th{background:#fff;border-bottom:1px solid #000}
            td{border-bottom:1px solid #ccc}
            .container{max-width:none;padding:0}
        }
        /* --- Buton ve ikon normalize --- */
        .btn{
            display:inline-flex; align-items:center; gap:.5rem;
            font-size:14px; line-height:1.25;
            color:#111; background:#fff; border:1px solid #ddd; border-radius:8px;
            padding:8px 12px; white-space:nowrap;
        }
        .btn.btn-primary{ background:#111; color:#fff; border-color:#111; }
        .btn.btn-outline{ background:#fff; color:#111; border-color:#ddd; }
        .btn i, .btn svg{ position:static !important; display:inline-block; vertical-align:middle; }
        .btn *{ font-size:inherit; line-height:inherit; color:inherit; }
        .btn:empty::before{ content: none; }
        td .btn{ margin:2px 0; }
        .menu-label-with-badge{display:inline-flex;align-items:center;gap:8px}
        .menu-badge{
            background:#ef4444;color:#fff;border-radius:9999px;
            font-size:11px;line-height:1;padding:4px 6px;min-height: 15px;
            display:inline-flex;align-items:center;justify-content:center;
            font-weight:600;
        }
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
        /* ===== LAYOUT (panel/profil ile aynı) ===== */
        :root{ --sidebar-w: 280px; }

        html, body { height: 100%; }
        .main-wrapper{
            display:grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: 100vh;
        }

        /* Hamburger: küçükte görünür, büyükte gizlenir */
        #sidebarToggle{ display:inline-flex; }
        @media (min-width:1024px){ #sidebarToggle{ display:none; } }

        /* Büyük ekran: sidebar sabit */
        @media (min-width:1024px){
            .sidebar{
                position: sticky; top:0; height:100vh;
                transform: translateX(0) !important; transition:none;
                box-shadow: inset -1px 0 0 rgba(0,0,0,.05);
            }
            .sidebar-overlay{ display:none !important; }
        }

        /* Küçük ekran: çekme menü */
        @media (max-width:1023px){
            .main-wrapper{ grid-template-columns: 1fr; }

            .sidebar{
                position: fixed; inset: 0 auto 0 0;
                width: var(--sidebar-w); max-width:85vw;
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

        /* Üst bar ve boşluklar */
        .top-bar{
            display:flex; align-items:center; justify-content:space-between;
            padding:12px 16px; border-bottom:1px solid #eee;
        }
        .main-content{ padding:16px; }
        @media (min-width:1024px){ .main-content{ padding:24px; } }

        /* Tipografi (okunaklı) */
        html{ font-size:16px; }
        @media (min-width:1280px){ html{ font-size:17px; } }
        body{ font-size:1rem; line-height:1.6; }

        /* Sidebar linkleri & butonlar */
        .sidebar-nav a{ display:flex; align-items:center; gap:10px; font-size:1rem; padding:12px 14px; }
        .sidebar-nav i.lucide{ width:20px; height:20px; flex:0 0 20px; }
        .btn{ font-size:.98rem; padding:10px 14px; border-radius:10px; min-height:38px; }
        .btn.btn-sm{ font-size:.95rem; padding:8px 12px; min-height:34px; }
        .btn i.lucide{ width:18px; height:18px; }
        .notification-button .lucide{ width:22px; height:22px; }

        /* Tablo: yatay kaydırma + mobilde kartlaşma */
        .table-wrap{
            width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;
            border:1px solid #e5e7eb; border-radius:10px;
        }
        table{ width:100%; border-collapse:collapse; }
        thead th{
            text-align:left; font-weight:700; font-size:.95rem; color:#374151;
            border-bottom:1px solid #e5e7eb; padding:12px 14px; background:#fafafa;
        }
        tbody td{ border-bottom:1px solid #f1f5f9; padding:12px 14px; vertical-align:top; }

        /* 640px altı: kart görünümü */
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
        }

        /* Yazdırma: sidebar’ı gizle, içerik tam genişlik */
        @media print{
            .no-print, .sidebar, .top-bar, .sidebar-overlay{ display:none !important; }
            .main-wrapper{ display:block; }
            .main-content{ padding:0 !important; }
            .container{ max-width:none; padding:0; }
            thead th{ background:#fff; border-bottom:1px solid #000; }
            tbody td{ border-bottom:1px solid #ccc; }
        }

    </style>
</head>
<body>
<div class="main-wrapper">

<aside class="sidebar no-print">
    <?php
    if (!isset($_SESSION['admin_panel_view'])):
        ?>
        <a class="flex items-center space-x-2" href="<?php echo BASE_URL; ?>">
            <span class="rookieverse">FRC ROOKIEVERSE</span>
        </a>
    <?php endif;?>

    <div class="sidebar-profile">
        <h2>Hoş Geldin,</h2>
        <p>Takım #<?php echo h($teamNumber); ?></p>
    </div>
    <nav class="sidebar-nav">
        <a href="panel.php"><i data-lucide="layout-dashboard"></i> Panelim</a>
        <a href="create_course.php"><i data-lucide="plus-square"></i> Yeni Kurs Oluştur</a>
        <a href="profile.php"><i data-lucide="settings"></i> Profilimi Düzenle</a>
        <a href="notifications.php"><i data-lucide="bell"></i> Bildirimler<?php if ($unreadTotal > 0): ?><span class="menu-badge"><?php echo $unreadTotal; ?></span><?php endif; ?></a>
        <a class="active" href="<?php echo h($_SERVER['PHP_SELF']); ?>"><i data-lucide="message-square"></i> Soru Yönetimi</a>
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
                aria-label="Menüyü aç/kapat" type="button" style="background:#fff">
            <i data-lucide="menu"></i>
        </button>
        <div class="font-bold">Takım #<?php echo $_SESSION['team_number']; ?> Paneli</div>
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
    <div class="container">


        <div class="topbar">

            <div>
                <h1 class="text-xl font-bold">Soru Yönetimi – Takım #<?php echo h($teamNumber); ?></h1>
                <div class="print-hint">Yazdır: Sağ üstteki “Yazdır” butonunu kullan veya tarayıcı menüsünden Yazdır.</div>
            </div>
            <div class="no-print">
                <button class="btn btn-primary" onclick="window.print()"><i data-lucide="printer"></i> Yazdır</button>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="no-print" style="margin-bottom:12px;padding:10px;border-radius:8px;<?php echo $flash['type']==='ok'?'background:#E6F4EA;color:#18794E':'background:#FEE4E2;color:#B42318'; ?>">
                <?php echo h($flash['msg']); ?>
            </div>
        <?php endif; ?>

        <form class="filters no-print" method="get" action="<?php echo h($_SERVER['PHP_SELF']); ?>">
            <div>
                <label>Kurs</label>
                <select name="course">
                    <option value="all" <?= $isAll ? 'selected' : '' ?>>All</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?= (!$isAll && $defaultCourseId===(int)$c['id'])?'selected':''; ?>>
                            <?php echo h($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Durum</label>
                <select name="status">
                    <?php
                    $opts = ['all'=>'Tümü','visible'=>'Yayında','hidden'=>'Gizli','unanswered'=>'Yanıtsız','answered'=>'Yanıtlanmış'];
                    foreach($opts as $k=>$v){
                        echo '<option value="'.h($k).'" '.($status===$k?'selected':'').'>'.h($v).'</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label>Ara</label>
                <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Metne göre ara...">
            </div>
            <div style="display:flex; gap:8px;">
                <button class="btn btn-outline" type="submit"><i data-lucide="search"></i> Uygula</button>
            </div>
        </form>

        <div style="margin:12px 0;">Toplam <strong><?php echo $total; ?></strong> soru • Sayfa <?php echo $page; ?>/<?php echo $pages; ?></div>

        <div class="overflow-x-auto">
            <table>
                <thead>
                <tr>
                    <th style="width:140px;">Tarih</th>
                    <th>Soru</th>
                    <th style="width:120px;">Durum</th>
                    <th style="width:140px;">Yanıt</th>
                    <th class="no-print" style="width:240px;">İşlemler</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5">Bu filtrelerde kayıt yok.</td></tr>
                <?php else: foreach ($items as $q): ?>
                    <?php
                    $badge = $q['is_approved'] ? '<span class="badge badge-green">Yayında</span>' : '<span class="badge badge-yellow">Gizli</span>';
                    $rBadge = ((int)$q['reply_count']>0) ? '<span class="badge badge-blue">'.(int)$q['reply_count'].' yanıt</span>' : '<span class="badge badge-gray">Yanıt yok</span>';
                    $qid = (int)$q['id'];
                    $rows = $repliesByQ[$qid] ?? [];
                    ?>
                    <tr>
                        <td><?php echo h($q['created_at']); ?></td>
                        <td>
                            <?php echo nl2br(h($q['body'])); ?>
                            <div class="no-print" style="margin-top:6px;">
                                <button style="color:#e5ae32;border:none;padding:8px 0;" type="button" class="btn btn-outline btn-toggle-replies" data-qid="<?php echo $qid; ?>">
                                    <i data-lucide="chevron-down"></i> Yanıtları gör
                                </button>
                            </div>
                            <div class="replies" id="replies-<?php echo $qid; ?>" style="display:none;margin-top:6px;">
                                <?php if (empty($rows)): ?>
                                    <div style="color:#666;">Henüz yanıt yok.</div>
                                <?php
                                    else:
                                    foreach ($rows as $r): ?>
                                    <div style="margin-top:8px;display: flex;justify-content: space-between;flex-direction: row;padding:8px;border:1px solid #eee;border-radius:8px;">

                                        <div style="font-size:12px;color:#667085;display:flex;flex-direction: column;justify-content:space-between;gap:5px; width: 100%">
                                            <span><?php echo h($r['created_at']); ?> • <?php echo h($r['responder_name'] ?: 'Eğitmen'); ?></span>

                                            <div style="text-align: left" ><?php echo nl2br(h($r['body'])); ?></div>

                                        </div>
                                        <form class="no-print" method="post" action="<?php echo h($_SERVER['PHP_SELF'] . qs()); ?>" onsubmit="return confirm('Bu yanıtı silmek istediğine emin misin? Bu işlem geri alınamaz.');" style="margin:0;">
                                            <input type="hidden" name="csrf" value="<?php echo h($CSRF); ?>">
                                            <input type="hidden" name="action" value="delete_reply">
                                            <input type="hidden" name="reply_id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" class="btn btn-outline" title="Yanıtı sil">
                                                <i data-lucide="trash-2"></i> Sil
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; endif;?>

                            </div>
                        </td>
                        <td><?php echo $badge; ?></td>
                        <td><?php echo $rBadge; ?></td>
                        <td class="no-print">
                            <!-- Toggle visibility -->
                            <form method="post" action="<?php echo h($_SERVER['PHP_SELF'] . qs()); ?>" style="display:inline;">
                                <input type="hidden" name="csrf" value="<?php echo h($CSRF); ?>">
                                <input type="hidden" name="action" value="toggle_visibility">
                                <input type="hidden" name="question_id" value="<?php echo (int)$q['id']; ?>">
                                <button class="btn btn-outline" type="submit">
                                    <?php if ($q['is_approved']): ?>
                                        <i data-lucide="eye-off"></i> Gizle
                                    <?php else: ?>
                                        <i data-lucide="eye"></i> Göster
                                    <?php endif; ?>
                                </button>
                            </form>

                            <!-- Add reply (inline small form) -->
                            <details style="display:contents;">
                                <summary class="btn btn-outline" style="list-style:none;"><i data-lucide="reply"></i> Yanıtla</summary>
                                <div style="margin-top:8px;border:1px solid #eee;border-radius:8px;padding:8px;">
                                    <form method="post" action="<?php echo h($_SERVER['PHP_SELF'] . qs()); ?>">
                                        <input type="hidden" name="csrf" value="<?php echo h($CSRF); ?>">
                                        <input type="hidden" name="action" value="add_reply">
                                        <input type="hidden" name="question_id" value="<?php echo (int)$q['id']; ?>">
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                                            <input name="responder_name" placeholder="İmza (örn. Koç Mert)" style="flex:1;min-width:160px;padding:8px;border:1px solid #ddd;border-radius:6px;">
                                        </div>
                                        <div>
                                            <textarea name="body" rows="3" placeholder="Yanıtınızı yazın..." style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea>
                                        </div>
                                        <div style="margin-top:6px;text-align:right;">
                                            <button class="btn btn-primary" type="submit"><i data-lucide="send"></i> Gönder</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
            <div>Toplam <?php echo $total; ?> soru • Sayfa <?php echo $page; ?>/<?php echo $pages; ?></div>
            <div style="display:flex;gap:8px;">
                <?php if ($page>1): ?>
                    <a class="btn btn-outline" href="<?php echo qs(['page'=>$page-1]); ?>"><i data-lucide="chevron-left"></i> Önceki</a>
                <?php else: ?>
                    <button class="btn btn-outline" disabled>Önceki</button>
                <?php endif; ?>
                <?php if ($page<$pages): ?>
                    <a class="btn btn-outline" href="<?php echo qs(['page'=>$page+1]); ?>">Sonraki <i data-lucide="chevron-right"></i></a>
                <?php else: ?>
                    <button class="btn btn-outline" disabled>Sonraki</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</div>

<div class="sidebar-overlay"></div>


<?php require_once '../admin/admin_footer.php'; ?>
<script>
    // Lucide tek sefer yükle (çift yüklemeye karşı basit kilit)
    (function loadLucide(){
        if (window.__lucide_loading__) return; window.__lucide_loading__ = true;
        function ready(fn){ document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn(); }
        function init(){ try{ window.lucide?.createIcons?.(); }catch(e){} }
        const s=document.createElement('script');
        s.src='https://cdn.jsdelivr.net/npm/lucide@latest'; s.defer=true;
        s.onload=()=>ready(init);
        s.onerror=()=>{ const b=document.createElement('script'); b.src='https://unpkg.com/lucide@latest'; b.defer=true; b.onload=()=>ready(init); document.head.appendChild(b); };
        document.head.appendChild(s);
    })();
</script>

<script>
    // Basit yardımcılar
    const $  = (sel, root=document) => root.querySelector(sel);
    const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

    // Satır içinde: "Yanıtları gör/gizle"
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-toggle-replies');
        if (!btn) return;

        const qid = btn.getAttribute('data-qid');
        const box = document.getElementById('replies-' + qid);
        if (!box) return;

        const visible = box.style.display !== 'none';
        box.style.display = visible ? 'none' : 'block';
        btn.innerHTML = visible
            ? '<i data-lucide="chevron-down"></i> Yanıtları gör'
            : '<i data-lucide="chevrons-up"></i> Yanıtları gizle';

        try { window.lucide?.createIcons?.(); } catch(e) {}
    });

    // Toplu aç/kapat
    function openAllReplies() {
        $$('.replies').forEach(box => { box.style.display = 'block'; });
        $$('.btn-toggle-replies').forEach(btn => {
            btn.innerHTML = '<i data-lucide="chevrons-up"></i> Yanıtları gizle';
        });
        try { window.lucide?.createIcons?.(); } catch(e) {}
    }
    function closeAllReplies() {
        $$('.replies').forEach(box => { box.style.display = 'none'; });
        $$('.btn-toggle-replies').forEach(btn => {
            btn.innerHTML = '<i data-lucide="chevron-down"></i> Yanıtları gör';
        });
        try { window.lucide?.createIcons?.(); } catch(e) {}
    }

    // Yazdırmadan önce hepsini aç, sonra geri kapat
    let printRestore = [];
    window.addEventListener('beforeprint', () => {
        printRestore = [];
        $$('.replies').forEach(box => {
            const wasHidden = (box.style.display === 'none' || getComputedStyle(box).display === 'none');
            printRestore.push([box, wasHidden]);
            box.style.display = 'block';
        });
    });
    window.addEventListener('afterprint', () => {
        for (const [box, wasHidden] of printRestore) {
            if (wasHidden) box.style.display = 'none';
        }
        printRestore = [];
    });

    // Toolbar’ı ekle ve butonları bağla
    document.addEventListener('DOMContentLoaded', () => {
        try { window.lucide?.createIcons?.(); } catch(e) {}

        const table = $('table');
        const container = $('.container');
        if (!table || !container) return;

        const bar = document.createElement('div');
        bar.className='no-print';
        bar.style.cssText='display:flex;gap:8px;align-items:center;justify-content:flex-end;margin:8px 0 12px;';
        bar.innerHTML = `
      <button type="button" id="btn-open-all"  class="btn btn-outline">
        <i data-lucide="chevrons-down"></i> Hepsini Aç
      </button>
      <button type="button" id="btn-close-all" class="btn btn-outline">
        <i data-lucide="chevrons-up"></i> Hepsini Kapat
      </button>
      <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#555;margin-left:6px;">
        <input id="cb-open-before-print" type="checkbox" checked> Yazdırmadan önce tüm yanıtları aç
      </label>
    `;
        const tblWrap = table.closest('.overflow-x-auto') || table.parentElement || container;
        (container||document.body).insertBefore(bar, tblWrap);

        $('#btn-open-all') ?.addEventListener('click', (e)=>{ e.preventDefault(); openAllReplies(); });
        $('#btn-close-all')?.addEventListener('click', (e)=>{ e.preventDefault(); closeAllReplies(); });

        try { window.lucide?.createIcons?.(); } catch(e) {}
    });
    document.querySelector('#notif-button').addEventListener('click', ()=>{
        window.location.href = 'notifications.php';
    })
    document.addEventListener('DOMContentLoaded', () => {
        try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}

        // Sidebar aktif link (opsiyonel)
        (function(){
            var here = '<?php echo basename($_SERVER["PHP_SELF"]); ?>';
            document.querySelectorAll('.sidebar-nav a').forEach(a=>{
                if((a.getAttribute('href')||'').endsWith(here)) a.classList.add('active');
            });
        })();

        // Bildirim butonu (tutarlılık)
        const notifBtn = document.getElementById('notif-button');
        if (notifBtn) notifBtn.addEventListener('click', ()=>{ window.location.href='notifications.php'; });

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
            if (icon){ icon.setAttribute('data-lucide','x'); try{ lucide.createIcons(); }catch(_){} }
        };
        const close = () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            const icon = toggle.querySelector('i.lucide');
            if (icon){ icon.setAttribute('data-lucide','menu'); try{ lucide.createIcons(); }catch(_){} }
        };

        toggle.addEventListener('click', ()=>{ sidebar.classList.contains('open') ? close() : open(); });
        overlay.addEventListener('click', close);
        document.addEventListener('keydown', e=>{ if(e.key==='Escape' && sidebar.classList.contains('open')) close(); });
        sidebar.querySelectorAll('a').forEach(a=>{
            a.addEventListener('click', ()=>{ if (window.matchMedia('(max-width:1023px)').matches) close(); });
        });
    });

</script>
</body>
</html>
