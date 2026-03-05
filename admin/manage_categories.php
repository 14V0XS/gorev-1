<?php
/** admin/manage_categories.php
 *  Kategori Yönetimi — listeleme, ad düzenleme, onay/reddet, sil
 */

declare(strict_types=1);
session_start();

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/config.php';
if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$pdo = get_db_connection();

/** CSRF */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$CSRF = $_SESSION['csrf'];

/** Helpers */
function catStatusBadge(string $status): array {
    switch ($status) {
        case 'approved': return ['Onaylı',      'bg-green-100 text-green-700 border-green-200'];
        case 'rejected': return ['Reddedildi',  'bg-red-100 text-red-700 border-red-200'];
        default:         return ['Beklemede',   'bg-yellow-100 text-yellow-800 border-yellow-200'];
    }
}

/** Actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/html; charset=utf-8');

    try {
        if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            throw new RuntimeException('CSRF doğrulaması başarısız.');
        }

        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'rename':
                $id   = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                if ($id <= 0 || $name === '') throw new RuntimeException('Geçersiz veri.');
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? LIMIT 1");
                $stmt->execute([$name, $id]);
                break;

            case 'approve':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('Geçersiz ID.');
                $pdo->prepare("UPDATE categories SET status = 'approved' WHERE id = ? LIMIT 1")->execute([$id]);
                break;

            case 'reject':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('Geçersiz ID.');
                $pdo->prepare("UPDATE categories SET status = 'rejected' WHERE id = ? LIMIT 1")->execute([$id]);
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('Geçersiz ID.');
                $pdo->prepare("DELETE FROM categories WHERE id = ? LIMIT 1")->execute([$id]);
                break;

            default:
                http_response_code(400);
                throw new RuntimeException('Bilinmeyen işlem.');
        }

        header('Location: manage_categories.php'); // PRG
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

/** Data */
$filter = $_GET['status'] ?? 'all';
$params = [];
$sql = "SELECT id, name, status, created_at, updated_at FROM categories";
if (in_array($filter, ['pending','approved','rejected'], true)) {
    $sql .= " WHERE status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY 
    CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
    created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Kategori Yönetimi";

require_once 'admin_header.php';
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Tailwind (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root{
            --primary:#FBBF24; --primary-dark:#F59E0B;
            --card:#fff; --bg:#F8FAFC; --text:#1F2937; --muted:#6B7280; --border:#E5E7EB; --danger:#EF4444;
        }
        body{background:var(--bg); color:var(--text); font-family:Inter,system-ui,ui-sans-serif;}
        .main{max-width:1200px; margin:0 auto; padding:2rem;}
        .card{background:var(--card); border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,.08); padding:1rem;}
        .btn{display:inline-flex; align-items:center; gap:.4rem; background:var(--primary); color:#fff; font-weight:600; padding:.55rem .9rem; border-radius:10px;}
        .btn:hover{background:var(--primary-dark)}
        .btn-ghost{background:transparent; color:var(--text);}
        .btn-danger{background:var(--danger); color:#fff;}
        .badge{border:1px solid; border-radius:9999px; padding:.2rem .55rem; font-size:.75rem; font-weight:700;}
        .table{width:100%; border-collapse:separate; border-spacing:0 .6rem;}
        .row{background:#fff; border:1px solid var(--border); border-radius:12px;}
        .row > td{padding:1rem;}
        .filter .tab{border:1px solid var(--border); background:#fff; border-radius:9999px; padding:.45rem .9rem; font-weight:600;}
        .filter .tab.active{background:var(--primary); color:#fff; border-color:transparent;}
        .modal{position:fixed; inset:0; background:rgba(0,0,0,.4); display:none; align-items:center; justify-content:center; z-index:50;}
        .modal .box{background:#fff; border-radius:12px; padding:1rem; width:100%; max-width:460px;}
        .input{border:1px solid var(--border); border-radius:10px; padding:.6rem .8rem; width:100%;}
    </style>
</head>
<body>
<?php require_once 'admin_sidebar.php'; ?>

<div style="width: 100%" class="page-container">
    <main class="main">
        <div class="mb-6">
            <h1 class="text-3xl font-extrabold">Kategori Yönetimi</h1>
            <p class="text-sm text-gray-600 mt-1">Kategorileri görüntüleyin, isimlerini düzenleyin, onaylayın/ reddedin veya silin.</p>
            <?php if (!empty($error)): ?>
                <p class="mt-3 text-red-600 font-semibold"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>

        <!-- Filtreler -->
        <div class="card mb-4 filter flex items-center gap-2">
            <?php
            $tabs = [
                ['all','Tümü'], ['pending','Beklemede'], ['approved','Onaylı'], ['rejected','Reddedildi']
            ];
            foreach ($tabs as [$val,$label]): $active = ($filter===$val || ($val==='all' && !in_array($filter,['pending','approved','rejected'],true))); ?>
                <a class="tab <?= $active?'active':'' ?>" href="?status=<?= $val ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Liste -->
        <div class="card">
            <?php if (empty($categories)): ?>
                <p class="text-center text-gray-500 py-10">Kategori bulunamadı.</p>
            <?php else: ?>
                <table class="table">
                    <thead class="sr-only">
                    <tr><th>ID</th><th>Ad</th><th>Durum</th><th>Oluşturma</th><th>Güncelleme</th><th>İşlemler</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat): [$label,$cls] = catStatusBadge($cat['status']); ?>
                        <tr class="row" data-id="<?= (int)$cat['id'] ?>">
                            <td class="w-16 font-mono text-sm">#<?= (int)$cat['id'] ?></td>
                            <td class="font-semibold">
                                <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="badge <?= $cls ?>"><?= $label ?></span>
                            </td>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                <?= htmlspecialchars(date('d.m.Y H:i', strtotime($cat['created_at'] ?? 'now'))) ?>
                            </td>
                            <td class="text-sm text-gray-500 whitespace-nowrap">
                                Kurs sayısı: <?= count(getAllCoursesByCat($cat['id'])) ?>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <!-- Rename -->
                                    <button class="btn-ghost flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100"
                                            onclick="openRenameModal(<?= (int)$cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>')">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i><span>Adı Düzenle</span>
                                    </button>

                                    <?php if ($cat['status']==='rejected' || $cat['status'] === 'pending'): ?>

                                        <form method="post" onsubmit="return confirm('Onaylansın mı?')">
                                            <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                            <button class="btn"><i data-lucide="check"></i><span>Onayla</span></button>
                                        </form>
                                        <?php
                                        if ($cat['status'] === 'pending'): ?>
                                        <form method="post" onsubmit="return confirm('Reddedilsin mi?')">
                                            <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                            <button class="btn-ghost"><i data-lucide="x-circle" class="w-5 h-5"></i>Reddet</button>
                                        </form>
                                        <?php endif;?>

                                    <?php endif; ?>

                                    <!-- Delete -->
                                    <form method="post" onsubmit="return confirm('Bu kategoriyi kalıcı olarak silmek istediğinize emin misiniz?')">
                                        <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                        <button class="btn-danger rounded px-3 py-2 flex items-center gap-1">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i><span>Sil</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Rename Modal -->
<div id="renameModal" class="modal">
    <div class="box">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold">Kategori Adını Düzenle</h3>
            <button onclick="closeRenameModal()" class="p-2 rounded hover:bg-gray-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="post" id="renameForm">
            <input type="hidden" name="csrf" value="<?= $CSRF ?>">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="id" id="renameId" value="">
            <label class="block text-sm text-gray-600 mb-1">Yeni Ad</label>
            <input class="input mb-4" type="text" name="name" id="renameName" required maxlength="100">
            <div class="flex items-center gap-2 justify-end">
                <button type="button" class="btn-ghost px-3 py-2 rounded" onclick="closeRenameModal()">Vazgeç</button>
                <button type="submit" class="btn"><i data-lucide="save"></i>Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>
<script>
    lucide.createIcons();

    // Modal controls
    const modal = document.getElementById('renameModal');
    const renameId = document.getElementById('renameId');
    const renameName = document.getElementById('renameName');

    function openRenameModal(id, currentName){
        renameId.value = id;
        renameName.value = currentName || '';
        modal.style.display = 'flex';
        setTimeout(()=>renameName.focus(), 30);
    }
    function closeRenameModal(){
        modal.style.display = 'none';
    }
    modal.addEventListener('click', (e)=> { if (e.target === modal) closeRenameModal(); });
    window.addEventListener('keydown', (e)=> { if (e.key === 'Escape') closeRenameModal(); });
</script>
</body>
</html>
