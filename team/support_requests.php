<?php
/** team/support_requests.php
 *  Takım -> Admin destek talebi gönderme ve geçmişi görüntüleme
 *  Şema: team_support(id, team_id, content_id, message, type['support','support_response','remove'], is_resolved, created_at, archived)
 */

declare(strict_types=1);
session_start();

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

if (!isset($_SESSION['team_logged_in'])) { header('location: ../team-login.php'); exit; }

$pdo        = get_db_connection();
$teamDbId   = $_SESSION['team_db_id'] ?? null;
$teamNumber = $_SESSION['team_number'] ?? null;
if (!$teamDbId) { die('Takım kimliği bulunamadı.'); }

/** Bildirim sayacı (senin koddakiyle aynı) */
$count = $pdo->prepare("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread
    FROM notifications WHERE team_id = ?
");
$count->execute([$teamDbId]);
list($totalRows, $unreadTotal) = $count->fetch(PDO::FETCH_NUM);

/** CSRF */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$CSRF = $_SESSION['csrf'];

$flash = null;

/** ---- Actions (POST) ---- */
/** ---- Actions (POST) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $flash = ['type'=>'error','msg'=>'CSRF doğrulaması başarısız.'];
    } else try {
        $action = $_POST['action'] ?? '';

        if ($action === 'send_support') {
            $msg = trim($_POST['message'] ?? '');
            if ($msg === '') {
                $flash = ['type'=>'error','msg'=>'Mesaj boş olamaz.'];
                header("Location: support_requests.php"); exit;
            }
            if (mb_strlen($msg) > 250) {
                $msg = mb_substr($msg, 0, 250, 'UTF-8');
            }

            // Yeni kök talep: type='support', content_id NULL
            $ins = $pdo->prepare("
        INSERT INTO team_support (team_id, content_id, message, type, is_resolved, archived)
        VALUES (?, NULL, ?, 'support', 0, 0)
    ");
            $ins->execute([$teamDbId, $msg]);

            $flash = ['type'=>'ok','msg'=>'Talebin gönderildi.'];
            header("Location: support_requests.php"); exit;
        }


        if ($action === 'send_followup') {
            $rootId  = (int)($_POST['root_id'] ?? 0);     // kök support id
            $replyTo = (int)($_POST['reply_to'] ?? 0);    // yanıt verilen admin cevabının id'si (support_response)
            $msg     = trim($_POST['message'] ?? '');

            if ($rootId<=0 || $replyTo<=0 || $msg==='') {
                $flash = ['type'=>'error','msg'=>'Eksik alan.']; header("Location: support_requests.php"); exit;
            }
            if (mb_strlen($msg) > 250) { $msg = mb_substr($msg, 0, 250); }

            // root gerçekten bu takımın support kaydı mı (content_id IS NULL)?
            $chkRoot = $pdo->prepare("SELECT 1 FROM team_support WHERE id=? AND team_id=? AND type='support' AND content_id IS NULL AND archived=0 LIMIT 1");
            $chkRoot->execute([$rootId, $teamDbId]);
            if (!$chkRoot->fetchColumn()) {
                $flash = ['type'=>'error','msg'=>'Yetkisiz işlem veya kök kayıt bulunamadı.'];
                header("Location: support_requests.php"); exit;
            }

            // replyTo gerçekten bu root'a bağlı admin cevabı mı?
            $chkResp = $pdo->prepare("SELECT 1 FROM team_support WHERE id=? AND type='support_response' AND content_id=? AND archived=0 LIMIT 1");
            $chkResp->execute([$replyTo, $rootId]);
            if (!$chkResp->fetchColumn()) {
                $flash = ['type'=>'error','msg'=>'Yanıtlanacak admin cevabı bulunamadı.'];
                header("Location: support_requests.php"); exit;
            }

            // Takım follow-up kaydı: type='support', content_id = reply_to (admin cevabının id’si)
            $ins = $pdo->prepare("
                INSERT INTO team_support (team_id, content_id, message, type, is_resolved, archived)
                VALUES (?, ?, ?, 'support', 0, 0)
            ");
            $ins->execute([$teamDbId, $replyTo, $msg]);

            $ins = $pdo->prepare("
                UPDATE team_support SET is_resolved = 0 WHERE id = ?
            ");
            $ins->execute([$rootId]);

            $flash = ['type'=>'ok','msg'=>'Yanıtın gönderildi.'];
            header("Location: support_requests.php"); exit;
        }

        if ($action === 'delete_followup') {
            $fid = (int)($_POST['followup_id'] ?? 0);
            if ($fid <= 0) {
                $flash = ['type'=>'error','msg'=>'Geçersiz istek.'];
                header("Location: support_requests.php"); exit;
            }

            // Bu follow-up gerçekten bu takımın mı? (type='support' ve KÖK OLMAMALI: content_id NOT NULL)
            $chk = $pdo->prepare("
        SELECT 1
        FROM team_support
        WHERE id=? AND team_id=? AND type='support' AND content_id IS NOT NULL AND archived=0
        LIMIT 1
    ");
            $chk->execute([$fid, $teamDbId]);
            if (!$chk->fetchColumn()) {
                $flash = ['type'=>'error','msg'=>'Yetkisiz işlem veya kayıt bulunamadı.'];
                header("Location: support_requests.php"); exit;
            }

            // Soft delete (arşivle) — kalıcı silmek istersen DELETE yapabilirsin
            $pdo->prepare("UPDATE team_support SET archived=1 WHERE id=?")->execute([$fid]);

            $flash = ['type'=>'ok','msg'=>'Yanıt silindi.'];
            header("Location: support_requests.php"); exit;
        }


        if ($action === 'delete_request') {
            $rid = (int)($_POST['req_id'] ?? 0);
            if ($rid <= 0) {
                $flash = ['type'=>'error','msg'=>'Geçersiz istek.'];
                header("Location: support_requests.php"); exit;
            }

            // Kök talep mi ve gerçekten bu takıma mı ait?
            // Not: content_id IS NULL → sadece kök support satırını hedefleriz.
            $chk = $pdo->prepare("
        SELECT 1
        FROM team_support
        WHERE id = ? AND team_id = ? AND type='support' AND content_id IS NULL AND archived = 0
        LIMIT 1
    ");
            $chk->execute([$rid, $teamDbId]);
            if (!$chk->fetchColumn()) {
                $flash = ['type'=>'error','msg'=>'Yetkisiz işlem veya kayıt bulunamadı.'];
                header("Location: support_requests.php"); exit;
            }

            try {
                $pdo->beginTransaction();

                // 1) Kök talebi arşivle
                $pdo->prepare("UPDATE team_support SET archived=1 WHERE id=?")->execute([$rid]);

                // 2) Bu köke bağlı admin cevaplarını (support_response) topla
                $respStmt = $pdo->prepare("
            SELECT id FROM team_support
            WHERE type='support_response' AND content_id=? AND archived=0
        ");
                $respStmt->execute([$rid]);
                $respIds = $respStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                if (!empty($respIds)) {
                    // 2a) Admin cevaplarını arşivle
                    $in = implode(',', array_fill(0, count($respIds), '?'));
                    $pdo->prepare("UPDATE team_support SET archived=1 WHERE id IN ($in)")
                        ->execute(array_map('intval', $respIds));

                    // 3) Admin cevaplarına yazılmış takım follow-up'larını arşivle
                    $pdo->prepare("UPDATE team_support SET archived=1 WHERE type='support' AND content_id IN ($in)")
                        ->execute(array_map('intval', $respIds));
                }

                $pdo->commit();
                $flash = ['type'=>'ok','msg'=>'Talep silindi.'];
            } catch (Throwable $e) {
                $pdo->rollBack();
                $flash = ['type'=>'error','msg'=>'Sunucu hatası.'];
            }

            header("Location: support_requests.php"); exit;
        }


        $flash = ['type'=>'error','msg'=>'Bilinmeyen işlem.'];
    } catch (Throwable $e) {
        $flash = ['type'=>'error','msg'=>'Sunucu hatası.'];
    }
}


/** ---- Data ---- */
/* Tüm destek talepleri (bu takıma ait) + varsa admin cevapları */
$reqs = $pdo->prepare("
    SELECT id, message, is_resolved, created_at
    FROM team_support
    WHERE team_id = ? AND type='support' AND content_id IS NULL AND archived=0
    ORDER BY created_at DESC, id DESC
");
$reqs->execute([$teamDbId]);
$requests = $reqs->fetchAll(PDO::FETCH_ASSOC);

$reqs->execute([$teamDbId]);
$requests = $reqs->fetchAll(PDO::FETCH_ASSOC);

$responsesByReq = [];
$followupsByResp = [];

if ($requests) {
    $rootIds = array_map(fn($r)=>(int)$r['id'], $requests);
    $inRoot  = implode(',', array_fill(0, count($rootIds), '?'));

    // 1) Admin cevapları (root’a bağlı)
    $rs  = $pdo->prepare("
        SELECT id, content_id AS req_id, message, created_at
        FROM team_support
        WHERE type='support_response' AND archived=0 AND content_id IN ($inRoot)
        ORDER BY created_at ASC, id ASC
    ");
    $rs->execute($rootIds);
    $adminRows = $rs->fetchAll(PDO::FETCH_ASSOC);

    // index: req_id -> [admin cevapları...]
    foreach ($adminRows as $row) {
        $responsesByReq[(int)$row['req_id']][] = $row;
    }

    // 2) Takım follow-up’ları (admin cevabının altına)
    // 2) Takım follow-up’ları (admin cevabının altına)
    if ($adminRows) {
        $respIds = array_map(fn($x)=>(int)$x['id'], $adminRows);
        $inResp  = implode(',', array_fill(0, count($respIds), '?'));

        $fu = $pdo->prepare("
        SELECT id, content_id AS reply_to, message, created_at
        FROM team_support
        WHERE type='support' AND archived=0 AND content_id IN ($inResp)
        ORDER BY created_at ASC, reply_to ASC
    ");
        $fu->execute($respIds);
        while ($row = $fu->fetch(PDO::FETCH_ASSOC)) {
            $followupsByResp[(int)$row['reply_to']][] = $row; // reply_to = admin cevabının id'si
        }
    }

}


/** Helpers */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

require_once 'team_header.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Destek Talepleri - Takım #<?= h($teamNumber) ?></title>
    <style>
        .container{max-width:1160px;margin:0 auto;padding:16px;}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer}
        .btn-primary{background:#111;color:#fff;border-color:#111}
        .btn-outline{background:#fff;color:#111;border-color:#ddd}
        .btn-danger{background:#EF4444;border-color:#EF4444;color:#fff}
        .badge{font-size:12px;padding:4px 8px;border-radius:999px;font-weight:600}
        .badge-green{background:#E6F4EA;color:#18794E}
        .badge-yellow{background:#FEF7CD;color:#936B00}
        .card{background:#fff;border:1px solid #eee;border-radius:12px;padding:12px}
        .timeline{display:flex;flex-direction:column;gap:10px}
        .answer{border-left:3px solid #10B981;background:#F0FDFA;border-radius:10px;padding:8px 10px}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .menu-badge{background:#ef4444;color:#fff;border-radius:9999px;font-size:11px;line-height:1;padding:4px 6px;display:inline-flex;align-items:center;justify-content:center;font-weight:600}
        .notification-button{position:relative;background:none;border:none;color:black;cursor:pointer;padding:0}
        .notification-button .lucide{color:black;width:24px;height:24px;margin-right:15px}
        .notification-badge{position:absolute;top:-5px;right:8px;background:#ffc107;color:black;font-size:10px;font-weight:bold;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;border:1px solid #fff}
        @media print{ .no-print{display:none !important} body{background:#fff} }
        /* ===== LAYOUT: panel/profil ile aynı çekme menü iskeleti ===== */
        :root{ --sidebar-w: 280px; }

        html, body { height: 100%; }
        .main-wrapper{
            display:grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            min-height: 100vh;
        }

        /* Hamburger buton: küçük ekranda görünür */
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

        /* Üst bar ve boşluklar */
        .top-bar{
            display:flex; align-items:center; justify-content:space-between;
            padding:12px 16px; border-bottom:1px solid #eee;
        }
        .main-content{ padding:16px; }
        @media (min-width:1024px){ .main-content{ padding:24px; } }

        /* Tipografi */
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

        /* Follow-up formunu mobilde dikey yığ */
        .followup-form{
            display:flex; gap:8px; align-items:flex-start; justify-content:space-between;
        }
        .followup-form textarea{ flex:1 1 auto; min-width:220px; }
        @media (max-width:640px){
            .followup-form{ flex-direction:column; }
            .followup-form textarea{ width:100% !important; }
            .followup-form .btn{ align-self:flex-end; }
        }

        /* Yazdırma */
        @media print{
            .no-print, .sidebar, .top-bar, .sidebar-overlay{ display:none !important; }
            .main-wrapper{ display:block; }
            .main-content{ padding:0 !important; }
            .container{ max-width:none; padding:0; }
        }

    </style>
</head>
<body>

<div class="main-wrapper">

<aside class="sidebar no-print">
    <?php if (!isset($_SESSION['admin_panel_view'])): ?>
        <a class="flex items-center space-x-2" href="<?= BASE_URL ?>">
            <span class="rookieverse">FRC ROOKIEVERSE</span>
        </a>
    <?php endif; ?>

    <div class="sidebar-profile">
        <h2>Hoş Geldin,</h2>
        <p>Takım #<?= h($teamNumber) ?></p>
    </div>
    <nav class="sidebar-nav">
        <a href="panel.php"><i data-lucide="layout-dashboard"></i> Panelim</a>
        <a href="create_course.php"><i data-lucide="plus-square"></i> Yeni Kurs Oluştur</a>
        <a href="profile.php"><i data-lucide="settings"></i> Profilimi Düzenle</a>
        <a href="notifications.php"><i data-lucide="bell"></i> Bildirimler<?php if ($unreadTotal>0): ?><span class="menu-badge"><?= $unreadTotal ?></span><?php endif; ?></a>
        <a href="list_questions.php" ><i data-lucide="message-square"></i> Soru Yönetimi</a>
        <a class="active" href="support_requests.php"><i data-lucide="life-buoy"></i> Destek</a>
        <?php if (!isset($_SESSION['admin_panel_view'])): ?>
            <a href="logout.php" class="logout-link"><i data-lucide="log-out"></i> Güvenli Çıkış</a>
        <?php endif; ?>
    </nav>
</aside>

<main style="margin-left: 0px; padding: 0px" class="main-content">
    <div class="top-bar">
        <button id="sidebarToggle"
                class="inline-flex items-center justify-center w-10 h-10 rounded-md border"
                aria-label="Menüyü aç/kapat" type="button" style="background:#fff">
            <i data-lucide="menu"></i>
        </button>
        <div class="font-bold">Takım #<?= h($teamNumber) ?> Paneli</div>
        <div class="actions">
            <button id="notif-button" class="notification-button">
                <i data-lucide="bell"></i>
                <?php if ($unreadTotal > 0): ?><div class="notification-badge"><?= h($unreadTotal) ?></div><?php endif; ?>
            </button>
            <?php if (isset($_SESSION['admin_panel_view'])): ?>
                <a href="../admin/accessTeamPanel.php?exit=1" class="btn btn-outline"><i data-lucide="arrow-left"></i> Admin Paneline Dön</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="topbar">
            <div>
                <h1 class="text-xl font-bold">Destek Talepleri – Takım #<?= h($teamNumber) ?></h1>
                <div class="print-hint">Adminlerden destek talep edin</div>
            </div>
            <div class="no-print">
                <button class="btn btn-primary" onclick="window.print()"><i data-lucide="printer"></i> Yazdır</button>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="no-print" style="margin-bottom:12px;padding:10px;border-radius:8px;<?= $flash['type']==='ok'?'background:#E6F4EA;color:#18794E':'background:#FEE4E2;color:#B42318' ?>">
                <?= h($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Yeni talep -->
        <div class="card no-print" style="margin-bottom:14px;">
            <form method="post" class="space-y-2">
                <input type="hidden" name="csrf" value="<?= h($CSRF) ?>">
                <input type="hidden" name="action" value="send_support">
                <label for="msg" style="font-size:12px;color:#555;font-weight:600;">Mesajınız</label>
                <textarea id="msg" name="message" rows="3" maxlength="250" placeholder="Net ve açıklayıcı şekilde talebinizi yazın."
                          style="width:100%;padding:10px;border:1px solid #ddd;border-radius:10px"></textarea>
                <div style="text-align:right;">
                    <button type="submit" class="btn btn-primary"><i data-lucide="send"></i> Gönder</button>
                </div>
            </form>
        </div>

        <!-- Geçmiş talepler -->
        <h2 class="text-lg font-bold" style="margin:10px 0;">Önceki Talepler</h2>

        <?php if (empty($requests)): ?>
            <div class="card">Henüz bir talebiniz yok.</div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($requests as $r): ?>
                    <div class="card">
                        <div style="display:flex;gap:10px;align-items:flex-start;">
                            <i data-lucide="life-buoy" class="w-5 h-5 text-gray-500" style="margin-top:2px;"></i>
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;gap:8px;">
                                    <div class="font-semibold">Talep #<?= (int)$r['id'] ?></div>
                                    <div style="font-size:12px;color:#667085;"><?= h(date('d.m.Y H:i', strtotime($r['created_at']))) ?></div>
                                </div>

                                <div style="margin-top:6px;"><?= nl2br(h($r['message'])) ?></div>

                                <div style="margin-top:8px;">
                                    <?php if ((int)$r['is_resolved'] === 1): ?>
                                        <span class="badge badge-green">Çözüldü</span>
                                    <?php else: ?>
                                        <span class="badge badge-yellow">Henüz çözülmedi</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Admin cevap(lar)ı -->
                                <?php if (!empty($responsesByReq[(int)$r['id']])): ?>
                                    <?php foreach ($responsesByReq[(int)$r['id']] as $ans): ?>
                                        <div class="answer" style="margin-top:10px;">
                                            <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                                                <div>
                                                    <div style="font-size:12px;color:#667085;margin-bottom:4px;">
                                                        <strong>Admin cevabı</strong> · <?= h(date('d.m.Y H:i', strtotime($ans['created_at']))) ?> · #<?= (int)$ans['id'] ?>
                                                    </div>
                                                    <div><?= nl2br(h($ans['message'])) ?></div>
                                                </div>
                                            </div>

                                            <!-- Bu admin cevabına takım follow-up'ları -->

                                        </div>
                                        <!-- Bu admin cevabına takım follow-up'ları -->
                                        <?php if (!empty($followupsByResp[(int)$ans['id']])): ?>
                                            <?php foreach ($followupsByResp[(int)$ans['id']] as $fu): ?>
                                                <div style="margin-top:10px;padding:8px 10px;border-left:3px solid #eab308;background:#fffbeb;border-radius:10px;">
                                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                                                        <div>
                                                            <div style="font-size:12px;color:#a16207;margin-bottom:4px;">
                                                                Takım yanıtı · <?= h(date('d.m.Y H:i', strtotime($fu['created_at']))) ?> · #<?= (int)$fu['id'] ?>
                                                            </div>
                                                            <div><?= nl2br(h($fu['message'])) ?></div>
                                                        </div>

                                                        <!-- Sil formu (sadece follow-up için) -->
                                                        <form method="post" class="no-print"
                                                              onsubmit="return confirm('Bu yanıtı silmek istiyor musunuz?');">
                                                            <input type="hidden" name="csrf" value="<?= h($CSRF) ?>">
                                                            <input type="hidden" name="action" value="delete_followup">
                                                            <input type="hidden" name="followup_id" value="<?= (int)$fu['id'] ?>">
                                                            <button class="btn btn-outline btn-danger" title="Yanıtı sil">
                                                                <i data-lucide="trash-2"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>


                                    <?php endforeach; ?>
                                    <!-- Takım follow-up formu (bu admin cevabına) -->
                                    <form method="post" class="no-print" style="margin-top:10px; display: flex; flex-direction: row; justify-content: space-between">
                                        <input type="hidden" name="csrf" value="<?= h($CSRF) ?>">
                                        <input type="hidden" name="action" value="send_followup">
                                        <input type="hidden" name="root_id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="reply_to" value="<?= (int)$ans['id'] ?>">
                                        <textarea name="message" rows="2" maxlength="250" placeholder="Hala bu konu hakkında kafanızda soru işaretleri varsa bizimle paylaşın!"
                                                  style="width:85%;padding:8px;border:1px solid #ddd;border-radius:8px"></textarea>
                                        <div style="text-align:right;margin-top:6px; display: flex">
                                            <button type="submit" class="btn btn-outline"><i data-lucide="corner-down-right"></i> Yanıtla</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div style="font-size:12px;color:#888;margin-top:8px;">Henüz admin cevabı yok.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Kök talebi sil (soft delete) -->
                            <form method="post" class="no-print"
                                  onsubmit="return confirm('Bu talebi silmek istiyor musunuz? Bu işlem admin cevabını ve takım follow-up’larını da gizler.');">
                                <input type="hidden" name="csrf" value="<?= h($CSRF) ?>">
                                <input type="hidden" name="action" value="delete_request">
                                <input type="hidden" name="req_id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-outline" title="Talebi sil">
                                    <i data-lucide="trash-2"></i> Sil
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>
    </div>
</main>

</div>
<div class="sidebar-overlay"></div>

<?php require_once '../admin/admin_footer.php'; ?>
<script>
    // Lucide yükle
    (function(){
        const init=()=>{ try{ window.lucide?.createIcons?.(); }catch(e){} };
        if (!window.lucide) {
            const s=document.createElement('script'); s.src='https://unpkg.com/lucide@latest'; s.defer=true; s.onload=init; document.head.appendChild(s);
        } else { init(); }
    })();

    // Bildirim butonu
    document.querySelector('#notif-button')?.addEventListener('click', ()=>{ location.href='notifications.php'; });
    document.addEventListener('DOMContentLoaded', () => {
        try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}

        // aktif link (opsiyonel)
        (function(){
        var here = 'support_requests.php';
        document.querySelectorAll('.sidebar-nav a').forEach(a=>{
        if((a.getAttribute('href')||'').endsWith(here)) a.classList.add('active');
    });
    })();

        // bildirim
        document.getElementById('notif-button')?.addEventListener('click', ()=>{ location.href='notifications.php'; });

        // sidebar toggle
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const toggle  = document.getElementById('sidebarToggle');
        if (!sidebar || !overlay || !toggle) return;

        const swapIcon = (name) => {
        const i = toggle.querySelector('i.lucide');
        if (!i) return;
        i.setAttribute('data-lucide', name);
        try { lucide.createIcons(); } catch(_) {}
    };

        const open = () => {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        swapIcon('x');
    };
        const close = () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        swapIcon('menu');
    };

        toggle.addEventListener('click', ()=>{ sidebar.classList.contains('open') ? close() : open(); });
        overlay.addEventListener('click', close);
        document.addEventListener('keydown', e=>{ if(e.key==='Escape' && sidebar.classList.contains('open')) close(); });

        // küçük ekranda menü linkine tıklanınca kapan
        sidebar.querySelectorAll('a').forEach(a=>{
        a.addEventListener('click', ()=>{ if (window.matchMedia('(max-width:1023px)').matches) close(); });
    });
    });

</script>
</body>
</html>
