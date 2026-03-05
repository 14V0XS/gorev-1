<?php
$projectRoot = dirname(__DIR__);
require_once('../config.php');
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: index.php"); exit(); }
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = "Kurs Yönetimi";
$pdo = get_db_connection();

/** Kurs listesi */
$courses = $pdo->query("
    SELECT c.id,
           c.title,
           c.status,            -- 'pending' | 'approved' | 'rejected'
           c.created_at,
           c.course_uid,
           COALESCE(t.team_name, 'Bilinmiyor') AS team_name,
           COUNT(m.id) AS module_count
    FROM courses c
    LEFT JOIN teams t ON t.id = c.team_db_id
    LEFT JOIN course_modules m ON m.course_id = c.id
    GROUP BY c.id, t.team_name
    ORDER BY 
        CASE c.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
        c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

/** Modüller (status ile birlikte) */
$courseIds = array_map(fn($c) => (int)$c['id'], $courses);
$modulesByCourse = [];
if (!empty($courseIds)) {
    $in = implode(',', array_fill(0, count($courseIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, course_id, title, sort_order, status
        FROM course_modules
        WHERE course_id IN ($in)
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute($courseIds);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modulesByCourse[$row['course_id']][] = $row;
    }
}

function statusBadge($status) {
    switch ($status) {
        case 'approved': return ['Onaylı', 'bg-green-100 text-green-700 border-green-200'];
        case 'rejected': return ['Reddedildi', 'bg-red-100 text-red-700 border-red-200'];
        default:         return ['Beklemede', 'bg-yellow-100 text-yellow-800 border-yellow-200'];
    }
}
function moduleStatusBadge($status) {
    // Görsel olarak biraz daha küçük rozet
    switch ($status) {
        case 'approved': return ['Onaylı', 'bg-green-50 text-green-700 border-green-200'];
        case 'rejected': return ['Reddedildi', 'bg-red-50 text-red-700 border-red-200'];
        default:         return ['Beklemede', 'bg-yellow-50 text-yellow-800 border-yellow-200'];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST'){

// ... session, auth, db vs
    $action = $_POST['action'] ?? null;

    switch ($action) {
        case 'approve_module':
            $mid = (int)($_POST['module_id'] ?? 0);
            $tid = getTeamIdByModule($mid);

            $stmt = $pdo->prepare("UPDATE course_modules SET status = 'approved' WHERE id = ?");
            $stmt->execute([$mid]);
            notify($mid, $tid,'module', 'approve');
            header('Location: ' . 'course_actions.php');
            exit;

        case 'reject_module':
            $mid = (int)($_POST['module_id'] ?? 0);
            $tid = getTeamIdByModule($mid);

            $stmt = $pdo->prepare("UPDATE course_modules SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$mid]);
            notify($mid, $tid,'module', 'reject');
            header('Location: ' . 'course_actions.php');
            exit;

        case 'approve_course':
            $cid = (int)($_POST['course_id'] ?? 0);
            $crs = getCourseDetailsById($cid);
            $tid = $crs['team_db_id'];

            $pdo->prepare("UPDATE courses SET status = 'approved' WHERE id = ?")->execute([$cid]);
            notify($cid, $tid, 'course', 'approve');
            header('Location: ' . 'course_actions.php');
            exit;

        case 'reject_course':
            $cid = (int)($_POST['course_id'] ?? 0);
            $crs = getCourseDetailsById($cid);
            $tid = $crs['team_db_id'];

            $pdo->prepare("UPDATE courses SET status = 'rejected' WHERE id = ?")->execute([$cid]);
            notify($cid, $tid,'course', 'reject');
            header('Location: ' . 'course_actions.php');
            exit;

        default:
            http_response_code(400);
    }
}
require_once 'admin_header.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary-color: #FBBF24;
            --primary-dark-color: #F59E0B;
            --secondary-color: #2DD4BF;
            --background-color: #F8FAFC;
            --card-background-color: #FFFFFF;
            --text-color: #1F2937;
            --border-color: #E5E7EB;
            --danger-color: #EF4444;
            --info-color: #6B7280;
        }
        body { font-family: 'Inter', sans-serif; background: var(--background-color); color: var(--text-color); }
        .main-content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--card-background-color); border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,.08); padding: 1.25rem; transition:.3s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,.1); }
        .btn { background: var(--primary-color); color: #fff; padding: .6rem 1rem; border-radius: 8px; font-weight: 600; display: inline-flex; align-items:center; gap:.4rem; transition:.2s; white-space: nowrap; }
        .btn:hover { background: var(--primary-dark-color); transform: translateY(-1px); }
        .btn-info { background: var(--info-color); }
        .btn-info:hover { background:#4B5563; }
        .btn-ghost { background: transparent; color: var(--text-color); }
        .btn-danger { background: var(--danger-color); color: #fff; }
        .btn-danger:hover { background: #DC2626; }
        .badge { border:1px solid; padding:.25rem .5rem; border-radius:9999px; font-size:.75rem; font-weight:700; white-space: nowrap; }
        .badge-sm { padding:.15rem .45rem; font-size:.7rem; }
        .course-row { border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
        .row-head { display:flex; align-items:center; gap:1rem; padding:1rem; cursor:pointer; }
        .row-head:hover { background:#F3F4F6; }
        .row-head .title { font-weight:700; }
        .row-actions { margin-left:auto; display:flex; gap:.5rem; }
        .row-meta { font-size:.85rem; color:#6B7280; }
        .row-body { display:none; border-top:1px dashed var(--border-color); padding:1rem; background:#FAFAFA; }
        .module-item { display:flex; justify-content:space-between; align-items:center; padding:.75rem 1rem; background:#fff; border:1px solid var(--border-color); border-radius:10px; gap:.75rem; }
        .module-left { display:flex; align-items:center; gap:.5rem; min-width:0; }
        .module-title { font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .filter-tabs .tab { border:1px solid var(--border-color); padding:.5rem .9rem; border-radius:9999px; font-weight:600; background:#fff; }
        .filter-tabs .tab.active { background:var(--primary-color); color:#fff; border-color:transparent; }
        @media (max-width:768px){ .row-actions { flex-wrap:wrap; } }

    </style>
</head>
<body>
<?php require_once 'admin_sidebar.php'; ?>

<div style="width: 100%" class="page-container">
    <main class="main-content">
        <div class="page-header mb-8 flex flex-col">
            <h1 class="text-4xl font-extrabold text-gray-800">Kurs Yönetimi</h1>
            <h2 class="text-gray-600 mt-2 text-sm">Onay bekleyen kursları ve modülleri inceleyin, önizleyin ve karar verin.</h2>
        </div>

        <!-- Filtreler -->
        <div class="card mb-6">
            <div class="flex items-center gap-3 filter-tabs">
                <button class="tab active" data-filter="all">Tümü</button>
                <button class="tab" data-filter="pending">Beklemede</button>
                <button class="tab" data-filter="approved">Onaylı</button>
                <button class="tab" data-filter="rejected">Reddedildi</button>
            </div>
        </div>

        <!-- Liste -->
        <?php if (empty($courses)): ?>
            <div class="card">
                <p class="text-center text-gray-500 py-10">Henüz kurs bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4" id="courseList">
                <?php foreach ($courses as $c): ?>
                    <?php [$label, $cls] = statusBadge($c['status']); ?>
                    <div class="course-row" data-status="<?= htmlspecialchars($c['status']) ?>">
                        <div class="row-head" onclick="toggleRowBody(event, 'row-<?= $c['id'] ?>')">
                            <i data-lucide="book-open" class="w-5 h-5 text-gray-500"></i>
                            <div class="flex flex-col min-w-0">
                                <div class="title text-lg truncate"><?= htmlspecialchars($c['title'] ?: 'Başlıksız Kurs') ?></div>
                                <div class="row-meta">
                                    <span class="mr-2">Ekip: <strong><?= htmlspecialchars($c['team_name']) ?></strong></span>
                                    <span class="mr-2">Modül: <strong><?= (int)$c['module_count'] ?></strong></span>
                                    <span>Oluşturma: <strong><?= htmlspecialchars(date('d.m.Y H:i', strtotime($c['created_at'] ?? 'now'))) ?></strong></span>
                                </div>
                            </div>

                            <span class="badge <?= $cls ?> ml-3"><?= $label ?></span>

                            <div class="row-actions">
                                <a href="../courseDetails.php?course=<?= $c['course_uid']?>&preview=1" class="btn btn-info" onclick="event.stopPropagation();">
                                    <i data-lucide="search"></i><span>İncele</span>
                                </a>
                                <?php if ($c['status'] === 'pending'): ?>
                                    <form action="course_actions.php" method="POST" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="action" value="approve_course">
                                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn">
                                            <i data-lucide="check-circle"></i><span>Onayla</span>
                                        </button>
                                    </form>
                                    <form action="course_actions.php" method="POST" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="action" value="reject_course">
                                        <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-ghost">
                                            <i data-lucide="x-circle"></i><span>Reddet</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Modüller -->
                        <div style="display: block" id="row-<?= $c['id'] ?>" class="row-body">
                            <?php $mods = $modulesByCourse[$c['id']] ?? []; ?>
                            <?php if (empty($mods)): ?>
                                <div class="text-gray-500 p-3">Bu kursta henüz modül bulunmuyor.</div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($mods as $m): ?>
                                        <?php [$mlabel, $mcls] = moduleStatusBadge($m['status'] ?? 'pending'); ?>
                                        <div class="module-item">
                                            <div class="module-left">
                                                <i data-lucide="layers" class="w-4 h-4 text-gray-500"></i>
                                                <div class="module-title">
                                                    <?= (int)$m['sort_order'] ?>. <?= htmlspecialchars($m['title'] ?: 'Başlıksız Modül') ?>
                                                </div>
                                                <span class="badge badge-sm <?= $mcls ?> ml-2"><?= $mlabel ?></span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <!-- Önizleme -->
                                                <a class="btn btn-ghost"
                                                   href="../moduleDetails.php?course=<?=$c['course_uid']?>&id=<?=$m['id']?>&ord=<?=$m['sort_order']?>&preview=1"
                                                   target="_blank" rel="noopener"
                                                   onclick="event.stopPropagation();">
                                                    <i data-lucide="play-circle"></i><span>Önizle</span>
                                                </a>

                                                <!-- Onay / Reddet (yalnızca beklemede ise) -->
                                                <?php if ($m['status'] === 'pending' || $m['status'] === 'draft'): ?>
                                                    <form action="course_actions.php" method="POST" onsubmit="event.stopPropagation(); return confirm('Bu modülü onaylamak istediğinize emin misiniz?');">
                                                        <input type="hidden" name="action" value="approve_module">
                                                        <input type="hidden" name="module_id" value="<?= (int)$m['id'] ?>">
                                                        <button type="submit" class="btn">
                                                            <i data-lucide="check"></i><span>Onayla</span>
                                                        </button>
                                                    </form>
                                                    <form action="course_actions.php" method="POST" onsubmit="event.stopPropagation(); return confirm('Bu modülü reddetmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="action" value="reject_module">
                                                        <input type="hidden" name="module_id" value="<?= (int)$m['id'] ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i data-lucide="x"></i><span>Reddet</span>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once 'admin_footer.php'; ?>
<script>
    lucide.createIcons();


    // Filtreler
    const tabs = document.querySelectorAll('.filter-tabs .tab');
    const rows = document.querySelectorAll('.course-row');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const f = tab.getAttribute('data-filter');
            rows.forEach(r => {
                const st = r.getAttribute('data-status');
                r.style.display = (f === 'all' || st === f) ? '' : 'none';
            });
        });
    });
</script>
</body>
</html>
