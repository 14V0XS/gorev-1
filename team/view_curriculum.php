<?php
session_start();
if (!isset($_SESSION['team_logged_in'])) { header('Location: ../team-login.php'); exit(); }
$projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php');
$course_id = $_GET['id'] ?? null;
if (!$course_id) { die("Kurs ID'si bulunamadı."); }

$pdo = get_db_connection();

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Güvenlik ve kurs başlığını alma
$stmt_course = $pdo->prepare("SELECT title, status, team_db_id FROM courses WHERE id = ? AND team_db_id = ?");
$stmt_course->execute([$course_id, $_SESSION['team_db_id']]);
$course = $stmt_course->fetch();
if (!$course) { die("Hata: Bu kurs size ait değil veya bulunamadı."); }

// Bu kursa ait mevcut bölümleri çek
$stmt_modules = $pdo->prepare("SELECT id, title, sort_order FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC, id ASC");
$stmt_modules->execute([$course_id]);
$modules = $stmt_modules->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Bölüm Yönetimi";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars($course['title']); ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/manage_curriculum.css">
    <style>
        .module-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; display:flex; align-items:center; gap:10px; }
        .drag-handle{ cursor:grab; padding:6px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; }
        .module-header{ display:flex; align-items:center; gap:10px; flex:1; }
        .module-actions a, .module-actions button{ padding:6px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; }
        .module-card.dragging{ opacity:.6; }
        .drop-target{ border-color:#f59e0b !important; box-shadow:0 0 0 2px rgb(245 158 11 / 25%); }
        .btn{ background:#f59e0b; color:#111827; padding:.6rem 1rem; border-radius:.75rem; font-weight:700; }
        .btn:hover{ filter:brightness(.95); }
        .btn-secondary{ background:#eef2ff; color:#1f2937; }
        .toast{ position:fixed; right:16px; bottom:16px; background:#111827; color:#fff; padding:10px 14px; border-radius:10px; display:none; }
        .toast.show{ display:block; }
    </style>
</head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Bölüm Yönetimi</h1>
            <p class="text-gray-600">"<?php echo htmlspecialchars($course['title']); ?>" kursunun bölümlerini düzenleyin.</p>
        </div>
        <a href="panel.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300">Panele Geri Dön</a>
    </div>

    <div id="modules-container" class="space-y-3">
        <?php if (count($modules) > 0): ?>
            <?php foreach ($modules as $module): ?>
                <div class="module-card" data-module-id="<?php echo (int)$module['id']; ?>" draggable="true">
                    <button class="drag-handle" title="Sürükle"><i data-lucide="grip-vertical"></i></button>
                    <div class="module-header">
                        <h3 class="flex-grow font-semibold text-gray-800"><?php echo htmlspecialchars($module['title']); ?></h3>
                    </div>
                    <div class="module-actions flex items-center gap-2">
                        <a href="edit_module_content.php?id=<?php echo (int)$module['id']; ?>" title="İçeriği Düzenle"><i data-lucide="pencil"></i></a>
                        <button class="move-up" title="Yukarı Taşı"><i data-lucide="chevron-up"></i></button>
                        <button class="move-down" title="Aşağı Taşı"><i data-lucide="chevron-down"></i></button>
                        <button class="delete-module text-red-600" title="Sil"><i data-lucide="trash-2"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card empty-state-card">
                <h3 class="text-xl font-semibold text-gray-700">Bu kurs için henüz bir bölüm bulunamadı.</h3>
                <p class="mt-2 mb-4">Aşağıdaki butona tıklayarak ilk bölümünüzü oluşturmaya başlayın.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-8">
        <a href="add_module.php?course_id=<?php echo (int)$course_id; ?>" class="btn text-lg"><i data-lucide="plus-circle" class="mr-2"></i> Yeni Bölüm Ekle</a>
    </div>
</div>

<div id="toast" class="toast"></div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    (function(){
        const container = document.getElementById('modules-container');
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const courseId = <?php echo (int)$course_id; ?>;
        let draggingEl = null;
        saveOrder();

        // Toast
        function showToast(msg){
            const t = document.getElementById('toast');
            t.textContent = msg; t.classList.add('show');
            setTimeout(()=>t.classList.remove('show'), 1800);
        }

        // --- Drag & Drop ---
        container.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('dragstart', (e)=>{
                if (!e.target.classList.contains('module-card')) return;
                draggingEl = e.target;
                requestAnimationFrame(()=> draggingEl.classList.add('dragging'));
            });
            card.addEventListener('dragend', ()=>{
                if (draggingEl){ draggingEl.classList.remove('dragging'); draggingEl = null; saveOrder(); }
            });
        });

        container.addEventListener('dragover', (e)=>{
            e.preventDefault();
            const after = getDragAfterElement(container, e.clientY);
            const dragging = container.querySelector('.dragging');
            if (!dragging) return;
            if (after == null) {
                container.appendChild(dragging);
            } else {
                container.insertBefore(dragging, after);
            }
        });

        function getDragAfterElement(container, y){
            const cards = [...container.querySelectorAll('.module-card:not(.dragging)')];
            let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
            for (const el of cards){
                const box = el.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset){
                    closest = { offset, element: el };
                }
            }
            return closest.element;
        }

        // --- Up / Down Buttons ---
        container.addEventListener('click', (e)=>{
            const btnUp = e.target.closest('.move-up');
            const btnDown = e.target.closest('.move-down');
            const btnDel = e.target.closest('.delete-module');

            if (btnUp || btnDown){
                const card = e.target.closest('.module-card');
                if (!card) return;
                if (btnUp) {
                    const prev = card.previousElementSibling;
                    if (prev) container.insertBefore(card, prev);
                } else if (btnDown) {
                    const next = card.nextElementSibling;
                    if (next) container.insertBefore(next, card);
                }
                saveOrder();
            }

            if (btnDel){
                const card = e.target.closest('.module-card');
                if (!card) return;
                const id = card.getAttribute('data-module-id');
                if (!confirm('Bu bölümü silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) return;

                fetch('delete_module.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ module_id: parseInt(id,10), csrf })
                })
                    .then(r => r.json())
                    .then(resp => {
                        if (resp.ok) {
                            card.remove();
                            saveOrder(); // kalanları yeniden sırala
                            showToast('Bölüm silindi');
                        } else {
                            alert(resp.error || 'Silme sırasında hata oluştu.');
                        }
                    })
                    .catch(()=> alert('Silme isteği başarısız.'));
            }
        });

        // --- Order Save ---
        function saveOrder(){
            const ids = [...container.querySelectorAll('.module-card')].map(el => parseInt(el.getAttribute('data-module-id'),10));
            fetch('reorder_modules.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ course_id: courseId, module_ids: ids, csrf })
            })
                .then(r => r.json())
                .then(resp => {
                    if (!resp.ok) { console.warn(resp.error || 'Sıralama kaydedilemedi'); }
                    else { showToast('Sıralama kaydedildi'); }
                })
                .catch(()=> console.warn('Sıralama isteği başarısız'));
        }
    })();
</script>
<script>lucide.createIcons();</script>
</body>
</html>
