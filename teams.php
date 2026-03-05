<?php

// teams.php

session_start();

$projectRoot = __DIR__;

require_once $projectRoot . '/config.php';



$pdo = get_db_connection();



/** ---- Arama & Sayfalama ---- */

$q       = trim($_GET['q'] ?? '');

$page    = max(1, (int)($_GET['page'] ?? 1));

$perPage = 24;

$offset  = ($page - 1) * $perPage;



$where = '';

$args  = [];

if ($q !== '') {

    // isim ya da numara içinde arama

    $where = "WHERE team_name LIKE :q OR CAST(team_number AS CHAR) LIKE :q";

    $args[':q'] = "%{$q}%";

}



/** ---- Toplam ---- */

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM teams $where");

$stmtCount->execute($args);

$total = (int)$stmtCount->fetchColumn();



/** ---- Liste ---- */

$sql = "

SELECT id, team_number, team_name, COALESCE(profile_pic_path,'assets/images/default-team.png') AS logo

FROM teams

$where

ORDER BY team_number ASC

LIMIT :limit OFFSET :offset

";

$stmt = $pdo->prepare($sql);

foreach ($args as $k=>$v) $stmt->bindValue($k, $v, PDO::PARAM_STR);

$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);

$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);

$stmt->execute();

$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);



/** ---- Sayfalama bilgisi ---- */

$pages = max(1, (int)ceil($total / $perPage));

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }



?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1" />



    <title><?= __('teams.page_title') ?></title>

    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">



    <script src="https://cdn.tailwindcss.com"></script>



    <script>

        tailwind.config = { theme: { extend: { colors: { 'custom-yellow':'#E5AE32' } } } }

    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/index.css">

    <style>

        .team-card { transition: transform .2s ease, box-shadow .2s ease; }

        .team-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }

        .team-logo { width: 72px; height:72px; border-radius: 16px; object-fit: cover; border:1px solid #e5e7eb; background:#fff; }

        .chip { font-size:.75rem; padding:.15rem .5rem; border-radius:9999px; background:#f3f4f6; border:1px solid #e5e7eb; color:#6b7280; }

        .pager a[aria-current="page"] { background:#E5AE32; color:#fff; border-color:#E5AE32; }

    </style>

    <!-- Google tag (gtag.js) -->

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>

    <script>

        window.dataLayer = window.dataLayer || [];

        function gtag(){dataLayer.push(arguments);}

        gtag('js', new Date());



        gtag('config', 'G-EDSVL8LRCY');

    </script>

</head>

<body class="bg-gray-50">

<?php require_once 'navbar.php'?>

<main class="main-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="content-area">

        <div class="page-header mb-6">

            <h1 class="text-3xl font-bold text-gray-900"><?= __('teams.title') ?></h1>

            <p class="text-gray-600 mt-1"><?= __('teams.total_listed', $total) ?></p>

        </div>



        <!-- Arama Kutusu -->

        <div class="card mb-6">

            <form method="get" class="flex flex-col md:flex-row gap-3 md:items-center">

                <div class="relative flex-1 min-w-[240px]">

                    <input name="q" value="<?= h($q) ?>" class="w-full border rounded-lg pl-10 pr-3 py-2 focus:ring-2 focus:ring-custom-yellow focus:border-custom-yellow" placeholder="<?= __('teams.search_placeholder') ?>"/>

                    <i data-lucide="search" class="absolute left-3 top-2.5 text-gray-400"></i>

                </div>

                <button class="inline-flex items-center gap-2 border rounded-lg px-4 py-2 hover:bg-gray-50"><i data-lucide="rotate-cw"></i> <?= __('teams.search') ?></button>

                <?php if ($q !== ''): ?>

                    <a href="teams.php" class="inline-flex items-center gap-2 border rounded-lg px-4 py-2 hover:bg-gray-50"><i data-lucide="x"></i> <?= __('teams.clear') ?></a>

                <?php endif; ?>

            </form>

        </div>



        <!-- Grid -->

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2 gap-5">

            <?php foreach ($teams as $t): ?>

                <div class="team-card bg-white rounded-xl border p-4 flex items-center gap-4">

                    <img src="<?= h($t['logo']) ?>" alt="Logo #<?= (int)$t['team_number'] ?>" class="team-logo" loading="lazy">

                    <div class="flex-1 min-w-0">

                        <div class="flex items-center gap-2">

                            <h3 class="mt-1 font-bold text-gray-900 truncate" title="<?= h($t['team_name']) ?>"><?= h($t['team_name']) ?></h3>



                            <span class="chip">#<?= (int)$t['team_number'] ?></span>

                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">

                            <a href="teamCourses.php?team_number=<?= (int)$t['team_number'] ?>#courses-section"  class="inline-flex items-center gap-2 rounded-md bg-custom-yellow text-white px-3 py-1.5 text-sm hover:opacity-90">

                                <i data-lucide="book-open" class=" w-4 h-4"></i> <?= __('teams.courses') ?>

                            </a>

                            <a href="teamCourses.php?team_number=<?= (int)$t['team_number'] ?>" class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">

                                <i data-lucide="users" class=" w-4 h-4"></i> <?= __('teams.profile') ?>

                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>



            <?php if (empty($teams)): ?>

                <div class="col-span-full">

                    <div class="bg-white border rounded-xl p-8 text-center text-gray-600">

                        <?= __('teams.no_results') ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>



        <!-- Sayfalama -->

        <?php if ($pages > 1): ?>

            <div class="mt-8 flex items-center justify-between">

                <div class="text-sm text-gray-600">

                    <?= __('teams.page_info', $page, $pages) ?>

                </div>

                <div class="pager flex items-center gap-2">

                    <?php

                    // Basit pager (‹‹ ‹ 1 2 3 › ››)

                    $base = 'teams.php?' . http_build_query(array_filter(['q'=>$q]));

                    $prev = max(1, $page-1);

                    $next = min($pages, $page+1);

                    $link = fn($p)=> $base . ($base ? '&' : '') . 'page=' . $p;

                    ?>

                    <a class="border rounded-md px-3 py-1.5 hover:bg-gray-50 <?= $page==1?'pointer-events-none opacity-50':'' ?>" href="<?= h($link(1)) ?>"><?= __('teams.first') ?></a>

                    <a class="border rounded-md px-3 py-1.5 hover:bg-gray-50 <?= $page==1?'pointer-events-none opacity-50':'' ?>" href="<?= h($link($prev)) ?>"><?= __('teams.prev') ?></a>

                    <?php

                    // Yakın sayfalar

                    $start = max(1, $page-2);

                    $end   = min($pages, $page+2);

                    for ($i=$start; $i<=$end; $i++):

                        ?>

                        <a class="border rounded-md px-3 py-1.5 hover:bg-gray-50" href="<?= h($link($i)) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a>

                    <?php endfor; ?>

                    <a class="border rounded-md px-3 py-1.5 hover:bg-gray-50 <?= $page==$pages?'pointer-events-none opacity-50':'' ?>" href="<?= h($link($next)) ?>"><?= __('teams.next') ?></a>

                    <a class="border rounded-md px-3 py-1.5 hover:bg-gray-50 <?= $page==$pages?'pointer-events-none opacity-50':'' ?>" href="<?= h($link($pages)) ?>"><?= __('teams.last') ?></a>

                </div>

            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once 'footer.php'?>



<script src="https://unpkg.com/lucide@latest"></script>



<script>

    document.addEventListener('DOMContentLoaded', () => {

        lucide.createIcons();

    });



</script>

</body>

</html>

