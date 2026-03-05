<?php
session_start();

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';

// 1) Auth
if (empty($_SESSION['team_logged_in'])) {
    header('Location: ../team-login.php');
    exit;
}

// CSRF hazırla
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$pdo  = get_db_connection();
$team = getTeam($_SESSION['team_number']);

// Takıma ait kursları çek
$stmt = $pdo->prepare("SELECT id, title, status FROM courses WHERE team_db_id = ? AND is_deleted = 0 ORDER BY id DESC");
$stmt->execute([$_SESSION['team_db_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = __('team_panel.panel_title');

// 2) HTML çıktısı başlatan dosyaları EN SON ekle
// Takıma ait bekleyen remove istekleri (is_resolved=0)
$pendingStmt = $pdo->prepare("
    SELECT content_id 
    FROM team_support 
    WHERE team_id = ? AND type = 'remove' AND is_resolved = 0
");
$pendingStmt->execute([$_SESSION['team_db_id']]);
$pending = array_fill_keys(array_map('intval', $pendingStmt->fetchAll(PDO::FETCH_COLUMN)), true);

require_once __DIR__ . '/team_header.php';
?>
<!-- buradan itibaren HTML -->

<script>
    // Lucide varsa ikonları çizelim; yoksa sessiz geç
    try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}
    // Aktif link sınıfını otomatik ata (exact match)
    (function(){
        var here = location.pathname.split('/').pop() || 'panel.php';
        document.querySelectorAll('.sidebar-nav a').forEach(a=>{
            var href = a.getAttribute('href') || '';
            if(href === here){ a.classList.add('active'); }
        });
    })();
</script>

<style>
    .logo-preview {
        width: 65px;
        height: 65px;
        border-radius: 18px;
        object-fit: cover;
        border: 1px solid var(--border);
        background: #fff;
        display: block;
        flex-shrink: 0;
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
        color: black;
        width: 24px;
        height: 24px;
        margin-right: 15px;
    }
    .notification-badge {
        position: absolute;
        top: -5px;
        right: 2px;
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
    .menu-label-with-badge{display:inline-flex;align-items:center;gap:8px}
    .menu-badge{
        background:#ef4444;color:#fff;border-radius:9999px;
        font-size:11px;line-height:1;padding:4px 6px;min-height: 15px;
        display:inline-flex;align-items:center;justify-content:center;
        font-weight:600;
    }
    .btn-danger{
        background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;
    }
    .btn-danger:hover{ background:#fecaca; }
    .soft-badge{
        display:inline-flex;align-items:center;gap:6px;
        font-size:12px;color:#374151;background:#f3f4f6;border:1px solid #e5e7eb;
        padding:4px 8px;border-radius:9999px;
    }
    /* ===== LAYOUT ===== */
    :root{
        --sidebar-w: 280px;
    }

    body, html { height: 100%; }
    .main-wrapper{
        display:grid;
        grid-template-columns: var(--sidebar-w) 1fr;
        min-height: 100vh;
    }
    #sidebarToggle {
        display: inline-flex;
    }

    /* Büyük ekran: normal iki sütun */
    @media (min-width: 1024px){
        .sidebar{
            position: sticky;
            top: 0;
            height: 100vh;
            transform: translateX(0) !important;
            transition: none;
            box-shadow: inset -1px 0 0 rgba(0,0,0,.05);
        }
        .sidebar-overlay{ display:none !important; }



        #sidebarToggle {
            display: none;
        }
    }

    /* Küçük ekran: sidebar gizli, hamburgerle açılır */
    @media (max-width: 1023px){
        .main-wrapper{
            grid-template-columns: 1fr; /* tek sütun */
        }

        .sidebar{
            position: fixed;
            inset: 0 auto 0 0;         /* left:0, top/bottom:0 */
            width: 50vw;
            max-width: 85vw;
            background: #fff;
            z-index: 50;
            border-right: 1px solid #e5e7eb;
            transform: translateX(-100%);
            transition: transform .25s ease;
            overflow-y:auto;
        }
        .sidebar.open{
            transform: translateX(0);
        }

        .sidebar-overlay{
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            backdrop-filter: blur(1px);
            z-index: 40;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }
        .sidebar-overlay.show{
            opacity: 1;
            pointer-events: auto;
        }

        .top-bar{
            position: sticky;
            top: 0;
            z-index: 30;
            background: #fff;
        }
    }

    /* Genel aralıklar ve küçük iyileştirmeler */
    .top-bar{
        display:flex; align-items:center; justify-content:space-between;
        padding: 12px 16px; border-bottom: 1px solid #eee;
    }

    .main-content{
        padding: 16px;
    }
    @media (min-width: 1024px){
        .main-content{ padding: 24px; }
    }

    .page-header h1{
        font-size: clamp(18px, 2.5vw, 24px);
        margin: 0 0 12px;
    }

    /* ===== TABLO ===== */

    /* Masaüstü: normal tablo */
    table{
        width:100%;
        border-collapse: collapse;
    }
    thead th{
        text-align:left;
        font-weight:700;
        font-size: 14px;
        color:#374151;
        border-bottom:1px solid #e5e7eb;
        padding:12px;
    }
    tbody td{
        border-bottom:1px solid #f1f5f9;
        padding:12px;
        vertical-align: middle;
    }

    /* Mobilde yatay kaydırma */
    .table-wrap{
        width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
    }

    /* (Opsiyonel) 640px altı ekranlarda tabloyu “kart” yapısı gibi göster */
    @media (max-width: 640px){
        table.responsive-stack thead{
            display:none;
        }
        table.responsive-stack,
        table.responsive-stack tbody,
        table.responsive-stack tr,
        table.responsive-stack td{
            display:block; width:100%;
        }
        table.responsive-stack tr{
            border-bottom:1px solid #e5e7eb;
            padding: 8px 10px;
        }
        table.responsive-stack td{
            padding: 8px 10px;
        }
        table.responsive-stack td::before{
            content: attr(data-label);
            display:block;
            font-size:11px;
            font-weight:600;
            color:#6b7280;
            margin-bottom:4px;
            text-transform: uppercase;
            letter-spacing:.02em;
        }
        /* İşlem butonları alt alta daha ferah dursun */
        table.responsive-stack td.space-x-2 > *{
            display:inline-flex; margin: 4px 6px 0 0;
        }
    }
    /* === TABLO — kart gibi aralıklı satırlar, sağlam buton yerleşimi === */

    /* Wrapper zaten var; dış kenarda çizgi yerine iç boşluk */
    .table-wrap{
        width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling: touch;
        padding: 6px;
        border: 0;
        background: transparent;
    }

    /* Satırlar aralıklı: border-collapse yerine separate + row gap */
    table{
        width:100%;
        border-collapse: separate !important;
        border-spacing: 0 10px; /* satırlar arası boşluk */
        table-layout: auto;     /* metin sarsın, dar alanda taşmasın */
    }

    /* Başlık */
    thead th{
        text-align:left;
        font-weight:700;
        font-size:14px;
        color:#374151;
        padding:10px 14px;
        border:0;
    }

    /* Satır “kart” görünümü */
    tbody tr{
        background:#fff;
        border:1px solid #e5e7eb;
        box-shadow: 0 1px 0 rgba(0,0,0,.02);
    }

    /* Hücre dolguları; sınırları kartın sınırına taşıdık */
    tbody td{
        padding: 12px 14px;
        border:0 !important;
        vertical-align: middle;
    }

    /* Kart köşeleri */
    tbody tr > td:first-child{
        border-top-left-radius:12px;
        border-bottom-left-radius:12px;
    }
    tbody tr > td:last-child{
        border-top-right-radius:12px;
        border-bottom-right-radius:12px;
    }

    /* Uzun başlıklar taşmasın, satıra sarsın */
    td[data-label]:first-child{
        max-width: 460px;            /* istersen büyüt/küçült */
        word-break: break-word;
        white-space: normal;
    }

    /* İşlem sütunu: butonlar satır kırabilsin ve aralıklı dursun */
    td.space-x-2{
        display: flex;
        flex-wrap: wrap;
        gap: 8px;                    /* tailwind space-x yerine modern gap */
    }
    td.space-x-2 .btn{
        display:inline-flex;
        align-items:center;
        height: 34px;                /* dokunmatik için yeterli yükseklik */
    }
    td.space-x-2 .btn i.lucide{
        margin-right: 6px;
    }

    /* Rozet/ikonlar satır yüksekliğini bozmasın */
    .soft-badge, .menu-badge{
        line-height: 1;
    }

    /* Mobil “stacked” görünüm: blok kart + aralık + başlık etiketleri */
    @media (max-width: 640px){
        table.responsive-stack thead{ display:none; }
        table.responsive-stack,
        table.responsive-stack tbody,
        table.responsive-stack tr,
        table.responsive-stack td{ display:block; width:100%; }

        table.responsive-stack tr{
            margin: 10px 0;            /* kartlar arası boşluk */
            padding: 0;                /* padding hücrelerde */
        }
        table.responsive-stack td{
            padding: 10px 14px;
        }
        table.responsive-stack td::before{
            content: attr(data-label);
            display:block;
            font-size:11px;
            font-weight:600;
            color:#6b7280;
            margin-bottom:4px;
            text-transform: uppercase;
            letter-spacing:.02em;
        }

        /* İşlem butonları mobilde de güzel dizilsin */
        td.space-x-2{ gap: 8px; }
    }

    /* Küçük görsel & buton uyarlamaları */
    .logo-preview{ width:60px; height:60px; }
    .notification-button .lucide{ margin-right:8px; }
    .btn{ white-space: nowrap; }

    /* ===== LANGUAGE SWITCHER ===== */
    .tp-lang-switcher{ position: relative; }
    .tp-lang-btn{
        display:inline-flex; align-items:center; gap:4px;
        padding:5px 10px; border-radius:6px; font-size:13px; font-weight:600;
        border:1px solid #e5e7eb; background:#fff; color:#374151; cursor:pointer;
        transition: border-color .2s;
    }
    .tp-lang-btn:hover{ border-color:#E5AE32; }
    .tp-lang-dropdown{
        display:none; position:absolute; top:100%; right:0; margin-top:4px;
        background:#fff; border:1px solid #e5e7eb; border-radius:8px;
        box-shadow:0 4px 12px rgba(0,0,0,.1); min-width:130px; z-index:60;
        overflow:hidden;
    }
    .tp-lang-dropdown.open{ display:block; }
    .tp-lang-option{
        display:block; padding:8px 14px; font-size:13px; font-weight:500;
        color:#374151; text-decoration:none; transition: background .15s;
    }
    .tp-lang-option:hover{ background:#f9fafb; }
    .tp-lang-option.active{ background:#fef9ee; color:#b8860b; font-weight:600; }

</style>

<div class="main-wrapper">

<aside class="sidebar">
    <?php if (!isset($_SESSION['admin_panel_view'])): ?>
        <a class="flex items-center space-x-2" href="<?php echo BASE_URL; ?>">
            <span class="rookieverse">FRC ROOKIEVERSE</span>
        </a>
    <?php endif; ?>
    <div class="sidebar-profile">
        <h2><?= __('team_panel.welcome') ?></h2>
        <p><?= __('team_panel.team') ?> #<?php echo htmlspecialchars($_SESSION['team_number']); ?></p>
    </div>
    <nav class="sidebar-nav">
        <a href="panel.php" class="active"><i data-lucide="layout-dashboard"></i> <?= __('team_panel.my_panel') ?></a>
        <a href="create_course.php"><i data-lucide="plus-square"></i> <?= __('team_panel.new_course') ?></a>
        <a href="profile.php"><i data-lucide="settings"></i> <?= __('team_panel.edit_profile') ?></a>
        <a href="notifications.php"><i data-lucide="bell"></i> <?= __('team_panel.notifications') ?><?php if (!empty($unreadTotal)): ?><span class="menu-badge"><?php echo (int)$unreadTotal; ?></span><?php endif; ?></a>
        <a href="list_questions.php" ><i data-lucide="message-square"></i> <?= __('team_panel.questions') ?></a>
        <a href="support_requests.php"><i data-lucide="life-buoy"></i> <?= __('team_panel.support') ?></a>
        <?php if (!isset($_SESSION['admin_panel_view'])): ?>
            <a href="logout.php" class="logout-link"><i data-lucide="log-out"></i> <?= __('team_panel.logout') ?></a>
        <?php endif; ?>
    </nav>
</aside>
<div class="sidebar-overlay"></div>

<main style="margin-left: 0px; padding: 0px" class="main-content">
    <div class="top-bar">
        <button style="background-color: white" id="sidebarToggle" class="inline-flex items-center justify-center lg:hidden w-10 h-10 rounded-md border"
                aria-label="Menüyü aç/kapat" type="button">
            <i data-lucide="menu"></i>
        </button>
        <div class="font-bold"><?= __('team_panel.team') ?> #<?php echo (int)$_SESSION['team_number']; ?> <?= __('team_panel.my_panel') ?></div>

        <div class="actions" style="display:flex;align-items:center;gap:8px;">
            <!-- Language Switcher -->
            <div class="tp-lang-switcher">
                <button class="tp-lang-btn" id="tp-lang-toggle" type="button">
                    <?= CURRENT_LANG === 'tr' ? 'TR' : 'EN' ?>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="tp-lang-dropdown" id="tp-lang-dropdown">
                    <a href="<?= BASE_URL ?>/set_lang.php?lang=tr&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="tp-lang-option <?= CURRENT_LANG==='tr'?'active':'' ?>">Türkçe</a>
                    <a href="<?= BASE_URL ?>/set_lang.php?lang=en&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="tp-lang-option <?= CURRENT_LANG==='en'?'active':'' ?>">English</a>
                </div>
            </div>

            <button id="notif-button" class="notification-button">
                <i data-lucide="bell"></i>
                <?php if (!empty($unreadTotal)): ?>
                    <div class="notification-badge"><?= htmlspecialchars($unreadTotal) ?></div>
                <?php endif; ?>
            </button>

            <?php if (isset($_SESSION['admin_panel_view'])): ?>
                <a href="../admin/accessTeamPanel.php?exit=1" class="btn btn-sm"><i data-lucide="arrow-left"></i><?= __('team_panel.back_admin') ?></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="content-area">
        <div class="page-header"><h1><?= __('team_panel.my_panel') ?></h1></div>
        <div class="card mb-6">
            <a href="create_course.php" class="btn"><i data-lucide="plus"></i> <?= __('team_panel.new_course_request') ?></a>
        </div>

        <div class="card">
            <h2><?= __('team_panel.your_courses') ?></h2>
            <?php if (count($courses) > 0): ?>
                <div class="table-wrap">
                    <table class="responsive-stack">
                        <thead>
                        <tr>
                            <th><?= __('team_panel.course_title') ?></th>
                            <th><?= __('team_panel.status') ?></th>
                            <th><?= __('team_panel.actions') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr data-course-row-id="<?= (int)$course['id'] ?>">
                                <td data-label="<?= __('team_panel.course_title') ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </td>
                                <td data-label="<?= __('team_panel.status') ?>">
              <span class="text-xs font-semibold px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                <?php echo htmlspecialchars($course['status']); ?>
              </span>
                                </td>
                                <td class="space-x-2" data-label="<?= __('team_panel.actions') ?>">
                                    <a href="editCourse.php?id=<?= (int)$course['id'] ?>" class="btn btn-sm btn-outline">
                                        <i data-lucide="edit-3"></i> <?= __('team_panel.edit_course') ?>
                                    </a>
                                    <a href="view_curriculum.php?id=<?= (int)$course['id'] ?>" class="btn btn-sm">
                                        <i data-lucide="list"></i> <?= __('team_panel.manage_content') ?>
                                    </a>
                                    <?php $hasReq = !empty($pending[(int)$course['id']]); ?>
                                    <button
                                            class="btn btn-sm btn-danger delete-request-btn <?= $hasReq ? 'opacity-60' : '' ?>"
                                            data-course-id="<?= (int)$course['id'] ?>"
                                            data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>"
                                            data-requested="<?= $hasReq ? '1' : '0' ?>"
                                            type="button">
                                        <i data-lucide="<?= $hasReq ? 'clock' : 'trash-2' ?>"></i>
                                        <span class="btn-text"><?= $hasReq ? __('team_panel.withdraw_request') : __('team_panel.delete_course') ?></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?= __('team_panel.no_courses') ?></p>
            <?php endif; ?>
        </div>

    </div>
</main>
</div>

<div class="sidebar-overlay"></div>

<script>
        function renderToast(msg, type='info'){ /* … sende zaten var … */ }

        async function sendDeleteRequest(courseId, csrf){
        try{
        const res = await fetch('request_course_delete.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({course_id: courseId, csrf})
    });
        if(!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json().catch(()=>({}));
        return !!data.ok;
    }catch(e){ return false; }
    }

        async function sendCancelRequest(courseId, csrf){
        try{
        const res = await fetch('cancel_course_delete.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({course_id: courseId, csrf})
    });
        if(!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json().catch(()=>({}));
        return !!data.ok;
    }catch(e){ return false; }
    }

        function markRowRequested(row){
        const btn   = row.querySelector('.delete-request-btn');
        const icon  = btn?.querySelector('i.lucide');
        const text  = btn?.querySelector('.btn-text');

        btn?.classList.add('opacity-60','cursor-not-allowed');
        btn?.setAttribute('data-requested','1');
        if (icon) icon.setAttribute('data-lucide','clock');
        if (text) text.textContent = '<?= __('team_panel.withdraw_request') ?>';

    }
        try{ lucide.createIcons(); }catch(_){}

        function unmarkRowRequested(row){
        const btn   = row.querySelector('.delete-request-btn');
        const icon  = btn?.querySelector('i.lucide');
        const text  = btn?.querySelector('.btn-text');

        btn?.classList.remove('opacity-60','cursor-not-allowed');
        btn?.setAttribute('data-requested','0');
        if (icon) icon.setAttribute('data-lucide','trash-2');
        if (text) text.textContent = '<?= __('team_panel.delete_course') ?>';

        // Rozeti kaldır
        const badge = row.querySelector('.delete-request-badge');
        badge?.remove();
        try{ lucide.createIcons(); }catch(_){}
    }

        document.querySelectorAll('.delete-request-btn').forEach(btn=>{
        btn.addEventListener('click', async ()=>{
            const row      = btn.closest('tr');
            const courseId = btn.dataset.courseId;
            const csrf     = btn.dataset.csrf;
            const requested = btn.getAttribute('data-requested') === '1';

            // Toggle: eğer zaten istek varsa -> Geri çek; yoksa -> İstek oluştur
            if (!requested){
                // optimistic
                markRowRequested(row);
                renderToast('<?= __('team_panel.delete_sent') ?>', 'success');

                const ok = await sendDeleteRequest(courseId, csrf);
                if (!ok){
                    // başarısızsa geri al
                    unmarkRowRequested(row);
                    renderToast('Sunucu hatası: istek oluşturulamadı.', 'error');
                }
            } else {
                // Geri çek
                // optimistic
                unmarkRowRequested(row);
                renderToast('<?= __('team_panel.delete_withdrawn') ?>', 'success');

                const ok = await sendCancelRequest(courseId, csrf);
                if (!ok){
                    // başarısızsa eski haline dön
                    markRowRequested(row);
                    renderToast('Sunucu hatası: istek geri çekilemedi.', 'error');
                }
            }
        });
    });
        try { if (window.lucide?.createIcons) lucide.createIcons(); } catch(e){}

        // Sidebar toggle
        (function(){
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const toggle  = document.getElementById('sidebarToggle');

            if(!sidebar || !toggle || !overlay) return;

            const open = () => {
                sidebar.classList.add('open');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
                try { lucide.createIcons(); } catch(_){}
            };
            const close = () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            };

            toggle.addEventListener('click', () => {
                if(sidebar.classList.contains('open')) close(); else open();
            });
            overlay.addEventListener('click', close);

            // ESC ile kapat
            document.addEventListener('keydown', (e)=>{
                if(e.key === 'Escape' && sidebar.classList.contains('open')) close();
            });
        })();

        // Küçük ekranlarda bir menü linkine tıklanınca sidebar kapansın
        (function(){
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            if(!sidebar) return;
            sidebar.querySelectorAll('a').forEach(a=>{
                a.addEventListener('click', ()=>{
                    if (window.matchMedia('(max-width: 1023px)').matches){
                        sidebar.classList.remove('open');
                        overlay?.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                });
            });
        })();

        // Language switcher toggle
        (function(){
            const btn = document.getElementById('tp-lang-toggle');
            const dd  = document.getElementById('tp-lang-dropdown');
            if(!btn || !dd) return;
            btn.addEventListener('click', (e)=>{
                e.stopPropagation();
                dd.classList.toggle('open');
            });
            document.addEventListener('click', ()=> dd.classList.remove('open'));
        })();
</script>

<?php require_once '../admin/admin_footer.php'; ?>
