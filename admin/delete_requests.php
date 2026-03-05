<?php
// admin/delete_requests.php (badgesiz)
declare(strict_types=1);

session_start();
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$pdo = get_db_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$page_title   = 'Silme İstekleri';
$current_page = basename(__FILE__);

// CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/** ---------------------------------------------------------
 * POST: Onayla / Reddet
 * --------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $out = ['ok' => false];

    try {
        if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            throw new RuntimeException('CSRF doğrulanamadı.');
        }

        // ... POST bloğunun içinde

        $action = $_POST['action'] ?? '';
        $reqId  = (int)($_POST['request_id'] ?? 0);
        if ($reqId <= 0) throw new RuntimeException('Geçersiz istek.');

// 1) recycle'ı whiteliste ekle
        if (!in_array($action, ['approve_request','reject_request','recycle'], true)) {
            throw new RuntimeException('Geçersiz işlem.');
        }

// İsteği çek – is_deleted'i da alalım
        $q = $pdo->prepare("
    SELECT r.id AS req_id, r.team_id, r.content_id AS course_id, r.is_resolved, r.type,
           c.title AS course_title, c.status AS course_status, c.course_uid, c.team_db_id,
           c.is_deleted AS course_deleted,
           t.team_name
    FROM team_support r
    JOIN courses c ON c.id = r.content_id
    JOIN teams   t ON t.id = r.team_id
    WHERE r.id = ? AND r.type = 'remove'
    LIMIT 1
");
        $q->execute([$reqId]);
        $req = $q->fetch(PDO::FETCH_ASSOC);
        if (!$req) throw new RuntimeException('Silme isteği bulunamadı.');

// 2) Sahiplik
        if ((int)$req['team_id'] !== (int)$req['team_db_id']) {
            throw new RuntimeException('Sahiplik tutarsızlığı.');
        }

// 3) Sadece approve/reject için "zaten sonuçlanmış" kontrolü
        if (in_array($action, ['approve_request','reject_request'], true) && (int)$req['is_resolved'] === 1) {
            throw new RuntimeException('Bu istek zaten sonuçlanmış.');
        }

        $pdo->beginTransaction();

// 4) Önceki istekleri arşivleme: sadece approve/reject’te yap
        if (in_array($action, ['approve_request','reject_request'], true)) {
            $arch = $pdo->prepare("
        UPDATE team_support
           SET archived = 1
         WHERE type = 'remove'
           AND content_id = ?
           AND id < ?
           AND archived = 0
    ");
            $arch->execute([(int)$req['course_id'], $reqId]);
        }

        if ($action === 'approve_request') {
            $pdo->prepare("UPDATE team_support SET is_resolved = 1 WHERE id = ? LIMIT 1")
                ->execute([$reqId]);

            $pdo->prepare("UPDATE courses SET status = 'rejected', is_deleted = 1 WHERE id = ? LIMIT 1")
                ->execute([(int)$req['course_id']]);

            $pdo->commit();
            echo json_encode(['ok'=>true, 'status'=>'approved', 'msg'=>'Silme isteği onaylandı. Önceki istekler arşivlendi, kurs silinmiş olarak işaretlendi.']);
            exit;
        }

        if ($action === 'recycle') {
            // 5) Geri yükleme, silinmiş kurs şartı
            if ((int)$req['course_deleted'] !== 1) {
                throw new RuntimeException('Kurs şu anda silinmiş değil.');
            }

            // Bu isteği arşivle (istersen is_resolved=1 da yapabilirsin)
            $pdo->prepare("UPDATE team_support SET archived = 1 WHERE id = ? LIMIT 1")
                ->execute([$reqId]);

            $pdo->prepare("UPDATE courses SET status = 'pending', is_deleted = 0 WHERE id = ? LIMIT 1")
                ->execute([(int)$req['course_id']]);

            $pdo->commit();
            echo json_encode(['ok'=>true, 'status'=>'recycled', 'msg'=>'Silinmiş kurs kurtarıldı.']);
            exit;
        }

        if ($action === 'reject_request') {
            $pdo->prepare("UPDATE team_support SET is_resolved = 1 WHERE id = ? LIMIT 1")
                ->execute([$reqId]);

            $pdo->commit();
            echo json_encode(['ok'=>true, 'status'=>'rejected', 'msg'=>'Silme isteği reddedildi. Önceki istekler arşivlendi.']);
            exit;
        }


    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        http_response_code(400);
        echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
        exit;
    }
}


/** ---------------------------------------------------------
 * Listeleme
 * --------------------------------------------------------*/
$requests = $pdo->query("
    SELECT r.id AS req_id, r.team_id, r.content_id AS course_id, r.is_resolved, r.type, r.created_at,
           c.title AS course_title, c.status AS course_status, c.course_uid, c.team_db_id, c.is_deleted,
           t.team_name
    FROM team_support r
    JOIN courses c ON c.id = r.content_id
    JOIN teams   t ON t.id = r.team_id
    WHERE r.type = 'remove' AND r.archived = 0
    ORDER BY r.is_resolved ASC, r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function courseStatusText(?string $s): string {
    return match($s){
        'approved' => 'Onaylı',
        'rejected' => 'Reddedildi',
        default    => 'Beklemede'
    };
}

require_once __DIR__ . '/admin_header.php';
require_once __DIR__ . '/admin_sidebar.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FBBF24;
            --primary-dark-color: #F59E0B;
            --background-color: #F8FAFC;
            --card-background-color: #FFFFFF;
            --text-color: #1F2937;
            --border-color: #E5E7EB;
            --danger-color: #EF4444;
            --info-color: #6B7280;
        }
        body { background: var(--background-color); color: var(--text-color); }
        .main-content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--card-background-color); border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,.08); padding: 1.25rem; transition:.3s; }
        .card:hover { transform: translateY(-1px); box-shadow: 0 8px 15px rgba(0,0,0,.1); }
        .btn { background: var(--primary-color); color: #fff; padding: .55rem .9rem; border-radius: 8px; font-weight: 600; display:inline-flex; align-items:center; gap:.4rem; transition:.15s; }
        .btn:hover { background: var(--primary-dark-color); }
        .btn-ghost { background: #fff; border:1px solid var(--border-color); color:#111827; }
        .btn-ghost:hover { background:#F3F4F6; }
        .btn-danger { background: var(--danger-color); }
        .btn-danger:hover { background:#DC2626; }
        .btn-info { background: var(--info-color); }
        .btn-info:hover { background:#4B5563; }

        .row { border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
        .row-head { display:flex; align-items:center; gap:1rem; padding:1rem; }
        .row-meta { font-size:.85rem; color:#6B7280; }
        .row-actions { margin-left:auto; display:flex; gap:.4rem; }
        .filter-tabs .tab { border:1px solid var(--border-color); padding:.45rem .9rem; border-radius:9999px; font-weight:600; background:#fff; }
        .filter-tabs .tab.active { background:var(--primary-color); color:#fff; border-color:transparent; }
        .empty { padding:3rem 1rem; text-align:center; color:#6B7280; }
    </style>
</head>
<body>
<div style="width: 100%" class="page-container">
    <main class="main-content">
        <div class="page-header mb-6" style="display:flex;flex-direction:column">
            <h1 class="text-3xl font-extrabold">Silme İstekleri</h1>
            <p class="text-sm text-gray-600 mt-1">Takımların gönderdiği <strong>remove</strong> taleplerini inceleyin, onaylayın veya reddedin.</p>
        </div>

        <!-- Filtre -->
        <div class="card mb-4">
            <div class="flex items-center gap-3 filter-tabs">
                <button class="tab active" data-filter="all">Tümü</button>
                <button class="tab" data-filter="pending">Beklemede</button>
                <button class="tab" data-filter="resolved">Sonuçlanan</button>
            </div>
        </div>

        <?php if (empty($requests)): ?>
            <div class="card empty">Henüz silme isteği bulunmuyor.</div>
        <?php else: ?>
            <div id="reqList" class="space-y-3">
                <?php foreach ($requests as $r): ?>
                    <?php
                    $reqText   = ((int)$r['is_resolved'] === 1) ? 'Sonuçlandı' : 'Beklemede';
                    $courseTxt = courseStatusText($r['course_status'] ?? 'pending');
                    ?>
                    <div class="row" data-status="<?= (int)$r['is_resolved'] === 1 ? 'resolved' : 'pending' ?>" data-id="<?= (int)$r['req_id'] ?>">
                        <div class="row-head">
                            <i data-lucide="trash-2" class="w-5 h-5 text-gray-500"></i>
                            <div class="flex flex-col min-w-0">
                                <div class="font-bold truncate">
                                    <?= htmlspecialchars($r['course_title'] ?: 'Başlıksız Kurs') ?>
                                </div>
                                <div class="row-meta">
                                    <span class="mr-2">Takım: <strong><?= htmlspecialchars($r['team_name']) ?></strong></span>
                                    <span class="mr-2">Kurs ID: <strong>#<?= (int)$r['course_id'] ?></strong></span>
                                    <span class="mr-2">Talep: <strong><?= htmlspecialchars(date('d.m.Y H:i', strtotime($r['created_at']))) ?></strong></span>
                                    <span class="mr-2">İstek Durumu: <strong class="req-status"><?= $reqText ?></strong></span>
                                    <span>Kurs Durumu: <strong class="course-status"><?= $courseTxt ?></strong></span>
                                </div>
                            </div>

                            <div class="row-actions">
                                <!-- İncele -->
                                <a class="btn btn-info" target="_blank" rel="noopener"
                                   href="../courseDetails.php?course=<?= urlencode($r['course_uid']) ?>&preview=1">
                                    <i data-lucide="search"></i> İncele
                                </a>
                                <?php
                                if ($r['is_deleted']):
                                ?>
                                    <button style="background-color: #0eaf00" class="btn recycle-btn" data-id="<?= (int)$r['req_id'] ?>">
                                        <i data-lucide="recycle"></i> Kurtar
                                    </button>
                                <?php
                                endif;
                                ?>
                                <?php if ((int)$r['is_resolved'] === 0): ?>
                                    <button class="btn approve-btn" data-id="<?= (int)$r['req_id'] ?>">
                                        <i data-lucide="check-circle"></i> Onayla
                                    </button>
                                    <button class="btn btn-ghost reject-btn" data-id="<?= (int)$r['req_id'] ?>">
                                        <i data-lucide="x-circle"></i> Reddet
                                    </button>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
<script>
    lucide.createIcons();

    // Basit toast
    function toast(msg, kind='info'){
        const t=document.createElement('div');
        t.textContent=msg;
        t.style.cssText='position:fixed;right:16px;bottom:16px;z-index:9999;padding:10px 14px;border-radius:10px;border:1px solid #e5e7eb;';
        if(kind==='success'){ t.style.background='#d1fae5'; t.style.color='#065f46'; }
        else if(kind==='error'){ t.style.background='#fee2e2'; t.style.color='#991b1b'; }
        else { t.style.background='#f3f4f6'; t.style.color='#111827'; }
        document.body.appendChild(t);
        setTimeout(()=>t.remove(),2200);
    }

    // Filtre
    const tabs = document.querySelectorAll('.filter-tabs .tab');
    const rows = document.querySelectorAll('#reqList .row');
    tabs.forEach(tab=>{
        tab.addEventListener('click',()=>{
            tabs.forEach(t=>t.classList.remove('active'));
            tab.classList.add('active');
            const f = tab.dataset.filter;
            rows.forEach(r=>{
                const st = r.getAttribute('data-status'); // 'pending' | 'resolved'
                r.style.display = (f==='all' || st===f) ? '' : 'none';
            });
        });
    });
    function attachRecycleHandler(btn){
        btn.addEventListener('click', async ()=>{
            const id = btn.dataset.id;
            const row = btn.closest('.row');
            try{
                const data = await postAction('recycle', id);
                if (data.ok){
                    const cEl = row.querySelector('.course-status');
                    if (cEl) cEl.textContent = 'Beklemede'; // pending'e döndü
                    toast('Kurs kurtarıldı.', 'success');
                    btn.remove(); // kurtarınca butonu kaldır
                } else {
                    toast(data.error || 'İşlem yapılamadı.', 'error');
                }
            }catch(e){ toast(e.message, 'error'); }
        });
    }

    function addRecycleButton(row, requestId){
        const actions = row.querySelector('.row-actions');
        if (!actions || actions.querySelector('.recycle-btn')) return; // zaten varsa ekleme

        const btn = document.createElement('button');
        btn.className = 'btn recycle-btn';
        btn.style.backgroundColor = '#0eaf00';
        btn.dataset.id = requestId;
        btn.innerHTML = '<i data-lucide="recycle"></i> Kurtar';

        // İstersen başa ekle, istersen sona:
        actions.appendChild(btn);

        attachRecycleHandler(btn);
        lucide.createIcons(); // ikonları yenile
    }

    // Ajax action helper
    async function postAction(action, requestId){
        const payload = new URLSearchParams();
        payload.set('action', action);
        payload.set('request_id', requestId);
        payload.set('csrf', '<?= $csrf ?>');

        const res = await fetch('<?= basename(__FILE__) ?>', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: payload
        });
        if(!res.ok) throw new Error('HTTP '+res.status);
        return res.json();
    }

    // UI update (badgesiz)
    function markResolved(row, courseRejected=false){
        if (!row) return;

        row.setAttribute('data-status','resolved');

        // Onayla/Reddet butonlarını kaldır
        row.querySelectorAll('.approve-btn,.reject-btn').forEach(b=>b.remove());



        // Meta satırındaki basit durum metinlerini güncelle
        const reqEl = row.querySelector('.req-status');
        if (reqEl) reqEl.textContent = 'Sonuçlandı';
        if (courseRejected) {
            const cEl = row.querySelector('.course-status');
            if (cEl) cEl.textContent = 'Reddedildi';
        }
    }

    // Onay / Reddet
    document.querySelectorAll('.approve-btn').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
            const id = btn.dataset.id;
            const row = btn.closest('.row');
            try{
                const data = await postAction('approve_request', id);
                if (data.ok){
                    markResolved(row, true);         // mevcut kodun
                    addRecycleButton(row, id);       // ➜ YENİ: Kurtar butonunu ekle
                    toast('Silme isteği onaylandı.', 'success');
                } else {
                    toast(data.error || 'İşlem yapılamadı.', 'error');
                }
            }catch(e){ toast(e.message, 'error'); }
        });
    });

    document.querySelectorAll('.reject-btn').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
            const id = btn.dataset.id;
            const row = btn.closest('.row');
            try{
                const data = await postAction('reject_request', id);
                if (data.ok){
                    markResolved(row, false);
                    toast('Silme isteği reddedildi.', 'success');
                } else {
                    toast(data.error || 'İşlem yapılamadı.', 'error');
                }
            }catch(e){ toast(e.message, 'error'); }
        });
    });
    document.querySelectorAll('.recycle-btn').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
            const id = btn.dataset.id;
            const row = btn.closest('.row');
            try{
                const data = await postAction('recycle', id);
                if (data.ok){
                    const cEl = row.querySelector('.course-status');
                    if (cEl) cEl.textContent = 'Beklemede';
                    btn.remove(); // recycle butonunu gizle
                    toast('Kurs kurtarıldı.', 'success');
                } else {
                    toast(data.error || 'İşlem yapılamadı.', 'error');
                }
            }catch(e){ toast(e.message, 'error'); }
        });
    });

</script>
</body>
</html>
