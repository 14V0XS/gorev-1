<?php
/** ----------------------------------------------------------------------------
 * RookieVerse - Team Courses Page (no cover)
 * Bu dosya tek bir takımın profilini + kurslarını gösterir.
 * Gerekli veri yapıları:
 *   $team = [
 *     'id' => 123,
 *     'name' => 'Rookie Mechanics',
 *     'number' => 'FRC #9072',
 *     'bio' => 'Kısa takım biyosu.',
 *     'about' => 'Uzun açıklama (Hakkında) metni.',
 *     'avatar' => BASE_URL . '/uploads/teams/123/avatar.jpg',
 *     'website' => 'https://team.example',
 *     'instagram' => 'https://instagram.com/team',
 *     'youtube' => 'https://youtube.com/@team',
 *     'linkedin' => 'https://www.linkedin.com/company/team',
 *     'email' => 'hello@team.org',
 *     'city' => 'İstanbul, Türkiye',
 *     'mentors' => ['Ayşe K.', 'Burak T.'],
 *     'stats' => ['courses' => 6, 'hours' => 28, 'members' => 24],
 *     'awards' => ['2024 Rookie All-Star', 'Innovation in Control Finalist'],
 *   ];
 *
 *   $categories = [ ['id'=>1,'name'=>'Yazılım'], ['id'=>2,'name'=>'Mekanik'], ... ];
 *
 *   $courses = [
 *     [
 *       'id'=>1,
 *       'title'=>'WPILib’e Giriş',
 *       'level'=>'Başlangıç', // Başlangıç | Orta | İleri
 *       'duration'=>'2s 10d',
 *       'category_id'=>1,
 *       'category_name'=>'Yazılım',
 *       'goal_text'=>'İlk robot kodunuz, komut tabanlı yapı.',
 *       'cover_image_url'=> BASE_URL . '/uploads/courses/1/cover.jpg',
 *       'course_uid'=>'abc123'
 *     ],
 *     ...
 *   ];
 *
 * Not: Aşağıda örnek veri yoksa, backend’de kendi getTeam/getTeamCourses fonksiyonlarınıza bağlayın.
 * ---------------------------------------------------------------------------*/

require_once 'config.php';
session_start();
$isEn = CURRENT_LANG === 'en';

$teamNumber = $_GET['team_number'];
$team = getTeambyNumber($teamNumber);
$courses = getTeamsCourses($team['id']);
$sum = sumUpStudents($courses);
$categories = getApprovedCategories();
$teamCourses = true;
// ÖRNEK: Backend’inizden veri çekiyorsanız şu tarz kullanın:
// $teamId     = (int)($_GET['team'] ?? 0);
// $team       = getTeamById($teamId);
// $categories = getApprovedCategoriesForTeam($teamId);
// $courses    = getApprovedCoursesByTeam($teamId);

// Güvenli çıktı yardımcıları
function e(?string $s): string { return htmlspecialchars((string)$s ?? '', ENT_QUOTES, 'UTF-8'); }

function tc_t(string $key): string {
    static $tr = [
        'team_default' => 'Takım',
        'title_suffix' => 'Kurslar | RookieVerse',
        'profile_photo' => 'Takım Profil Fotoğrafı',
        'website' => 'Web Sitesi',
        'courses' => 'Kurs',
        'students' => 'Öğrenci',
        'tab_courses' => 'Kurslar',
        'tab_about' => 'Hakkında',
        'search_placeholder' => 'Kurslarda ara...',
        'level_all' => 'Seviye (tümü)',
        'all' => 'Tümü',
        'open_course' => 'Kursu Aç',
        'no_courses_title' => 'Bu takım henüz kurs yüklememiş.',
        'no_courses_desc' => 'Yeni kurslar eklendiğinde burada görünecek.',
        'empty_title' => 'Eşleşen kurs bulunamadı.',
        'empty_desc' => 'Filtreleri temizlemeyi veya arama terimini değiştirmeyi deneyin.',
        'about_team' => 'Takım Hakkında',
        'contact' => 'İletişim'
    ];
    static $en = [
        'team_default' => 'Team',
        'title_suffix' => 'Courses | RookieVerse',
        'profile_photo' => 'Team Profile Photo',
        'website' => 'Website',
        'courses' => 'Courses',
        'students' => 'Students',
        'tab_courses' => 'Courses',
        'tab_about' => 'About',
        'search_placeholder' => 'Search courses...',
        'level_all' => 'Level (all)',
        'all' => 'All',
        'open_course' => 'Open Course',
        'no_courses_title' => 'This team has not published any courses yet.',
        'no_courses_desc' => 'New courses will appear here when available.',
        'empty_title' => 'No matching courses found.',
        'empty_desc' => 'Try clearing filters or changing your search text.',
        'about_team' => 'About Team',
        'contact' => 'Contact'
    ];
    $dict = (CURRENT_LANG === 'en') ? $en : $tr;
    return $dict[$key] ?? $key;
}
?>
<!DOCTYPE html>
<html lang="<?= CURRENT_LANG ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet"  href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <title><?= e($team['name'] ?? tc_t('team_default')) ?> — <?= tc_t('title_suffix') ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">


    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#E5AE32', ink: '#0f172a', 'custom-yellow': '#E5AE32' },
                    boxShadow: { soft: '0 10px 30px -12px rgba(0,0,0,.12)' },

                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body{ font-family: system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,"Noto Sans",sans-serif; }
        .clamp-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .clamp-3{ display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        .focus-ring:focus{ outline:none; box-shadow:0 0 0 3px rgba(229,174,50,.35); }
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
<body class="bg-white text-ink">

<!-- NAVBAR (isterseniz kendi navbar.php’nizi include edin) -->
<?php if (file_exists(__DIR__ . '/navbar.php')) require_once __DIR__ . '/navbar.php'; ?>

<main class="min-h-screen">
    <!-- Üst başlık (kapaksız) -->
    <section class="border-b bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center gap-6">
                <!-- Profil görseli -->
                <?php
                if (!empty($team['profile_pic_path'])):
                ?>
                    <img
                        src="<?=$team['profile_pic_path'] ?>"
                        alt="<?= tc_t('profile_photo') ?>"
                        class="w-24 h-24 rounded-2xl object-cover bg-slate-100 border"
                    />
                <?php endif;?>

                <!-- Temel bilgiler -->
                <div class="flex-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl md:text-3xl font-extrabold"><?= e($team['team_name'] ?? tc_t('team_default')) ?></h1>
                        <?php if (!empty($team['team_number'])): ?>
                            <span class="text-xs font-semibold uppercase tracking-wider bg-slate-100 text-ink px-2 py-1 rounded-md">
                #<?= e($team['team_number']) ?>
              </span>
                        <?php endif; ?>
                    </div>


                    <!-- Website + Sosyaller -->
                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <?php if (!empty($team['website'])): ?>
                            <a href="<?= e($team['website']) ?>" target="_blank"
                               class="inline-flex items-center gap-2 text-sm bg-primary text-white hover:opacity-90 px-3 py-1.5 rounded-lg focus-ring">
                                <i data-lucide="globe" class="w-4 h-4"></i><span><?= tc_t('website') ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($team['instagram'])): ?>
                            <a href="<?= e($team['instagram']) ?>" target="_blank"
                               class="inline-flex items-center gap-2 text-sm bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg border">
                                <i data-lucide="instagram" class="w-4 h-4"></i><span>Instagram</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($team['youtube'])): ?>
                            <a href="<?= e($team['youtube']) ?>" target="_blank"
                               class="inline-flex items-center gap-2 text-sm bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg border">
                                <i data-lucide="youtube" class="w-4 h-4"></i><span>YouTube</span>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($team['linkedin'])): ?>
                            <a href="<?= e($team['linkedin']) ?>" target="_blank"
                               class="inline-flex items-center gap-2 text-sm bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg border">
                                <i data-lucide="linkedin" class="w-4 h-4"></i><span>LinkedIn</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="grid grid-cols-2 gap-4 w-full md:w-auto md:min-w-[200px]">
                    <div class="text-center bg-slate-50 rounded-xl p-3 border">
                        <div class="text-2xl font-extrabold text-ink"><?= count($courses) ?></div>
                        <div class="text-xs text-slate-600"><?= tc_t('courses') ?></div>
                    </div>
                    <div class="text-center bg-slate-50 rounded-xl p-3 border">
                        <div class="text-2xl font-extrabold text-ink"><?= $sum?></div>
                        <div class="text-xs text-slate-600"><?= tc_t('students') ?></div>
                    </div>
                </div>
            </div>

            <!-- Sekmeler -->
            <!-- Sekmeler (küçük bir iyileştirme: role/tab ekledim) -->
            <div class="mt-6 border-b border-slate-200 flex items-center gap-6" role="tablist">
                <button id="tab-courses" role="tab" class="tab-btn border-b-2 border-ink font-semibold pb-3 text-ink" aria-selected="true"><?= tc_t('tab_courses') ?></button>
                <button id="tab-about" role="tab" class="tab-btn pb-3 text-slate-600 border-b-2 border-transparent" aria-selected="false"><?= tc_t('tab_about') ?></button>
            </div>

        </div>
    </section>

    <!-- Kurslar Bölümü -->
    <section id="courses-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
        <!-- Filtre satırı -->
        <div class="flex flex-col lg:flex-column gap-8 lg:items-center lg:justify-between">


            <div class="flex items-center gap-2">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input id="searchInput" type="text" placeholder="<?= tc_t('search_placeholder') ?>"
                           class="focus-ring pl-9 pr-3 py-2 w-72 rounded-full border border-slate-200"/>
                </div>
                <select id="levelSelect" class="focus-ring px-3 py-2 rounded-full border border-slate-200">
                    <option value=""><?= tc_t('level_all') ?></option>
                    <option value="Başlangıç">Başlangıç</option>
                    <option value="Orta">Orta</option>
                    <option value="İleri">İleri</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2" id="categoryPills">
                <?php
                // "Tümü" kapsülü
                echo '<button data-cat="" class="px-4 py-2 rounded-full border bg-primary text-white border-primary text-sm">' . tc_t('all') . '</button>';
                // Diğer kategoriler
                if (!empty($categories)) {
                    foreach ($categories as $cat) {
                        echo '<button data-cat="'. e($cat['id']) .'" class="px-4 py-2 rounded-full border border-slate-200 text-slate-700 hover:border-primary hover:text-ink text-sm">'
                            . e($cat['name']) .'</button>';
                    }
                }
                ?>
            </div>

        </div>

        <!-- Kurs grid -->
        <div id="coursesGrid" class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $c):
                    $catg = getCategory($c['category_id']);
                    ?>
                    <article
                        class="course-card group rounded-2xl border overflow-hidden hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-300"
                        data-title="<?= e(mb_strtolower($c['title'])) ?>"
                        data-goal="<?= e(mb_strtolower($c['goal_text'] ?? '')) ?>"
                        data-category="<?= e((string)$c['category_id']) ?>"
                        data-level="<?= e($c['level']) ?>">
                        <div class="relative aspect-video overflow-hidden bg-slate-100">
                            <?php if (!empty($c['cover_image_url'])): ?>
                                <img src="<?= e($c['cover_image_url']) ?>" alt="<?= $c['title'] ?>"
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                            <?php endif; ?>
                            <div class="absolute left-3 top-3 inline-flex items-center gap-2">
                                <?php
                                $level = $c['level'] ?? '';
                                $badgeClass = 'bg-slate-100 text-slate-700';
                                if ($level === 'Başlangıç') $badgeClass = 'bg-emerald-100 text-emerald-700';
                                elseif ($level === 'Orta')  $badgeClass = 'bg-amber-100 text-amber-700';
                                elseif ($level === 'İleri') $badgeClass = 'bg-rose-100 text-rose-700';
                                ?>
                                <span class="text-xs font-semibold px-2 py-1 rounded-full <?= $badgeClass ?>"><?= e($level) ?></span>
                                <span class="text-xs bg-white/90 text-ink px-2 py-1 rounded-md border"><?= e($catg['name']) ?></span>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-bold clamp-2"><?= e($c['title']) ?></h3>
                            <?php if (!empty($c['goal_text'])): ?>
                                <p class="text-slate-600 text-sm clamp-3 mt-1"><?= e($c['goal_text']) ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between text-sm text-slate-600 mt-4">
                                <span></span>
                                <a href="<?= BASE_URL ?>/courseDetails.php?course=<?= e($c['course_uid']) ?>"
                                   class="inline-flex items-center gap-1 font-semibold text-primary hover:opacity-80">
                                    <?= tc_t('open_course') ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full">
                    <div class="rounded-2xl border p-8 text-center text-slate-600">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mx-auto mb-3">
                            <i data-lucide="inbox" class="w-6 h-6"></i>
                        </div>
                        <p class="font-semibold"><?= tc_t('no_courses_title') ?></p>
                        <p class="text-sm"><?= tc_t('no_courses_desc') ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Boş arama durumu (JS ile kontrol) -->
        <div id="emptyState" class="hidden mt-16 text-center text-slate-500">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-100 mx-auto mb-3">
                <i data-lucide="search-x" class="w-6 h-6"></i>
            </div>
            <p class="font-semibold"><?= tc_t('empty_title') ?></p>
            <p class="text-sm"><?= tc_t('empty_desc') ?></p>
        </div>
    </section>

    <!-- Hakkında Bölümü -->
    <section id="about-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 hidden">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-4">
                <h3 class="text-xl font-bold"><?= tc_t('about_team') ?></h3>
                <p class="text-slate-700 leading-7">
                    <?= e($team['about'] ?? ($team['bio'] ?? '')) ?>
                </p>
            </div>
            <?php
            if (!empty($team['email'])):
            ?>
                <aside class="space-y-4">
                    <h3 class="text-xl font-bold"><?= tc_t('contact') ?></h3>
                    <div class="rounded-2xl border p-4 space-y-2 text-sm">

                        <?php if (!empty($team['email'])): ?>
                            <div class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4"></i>
                                <a class="underline" href="mailto:<?= e($team['email']) ?>"><?= e($team['email']) ?></a></div>
                        <?php endif; ?>
                    </div>
                </aside>
            <?php endif; ?>

        </div>
    </section>
</main>


<script>
    // Sekmeler
    const tabCourses = document.getElementById('tab-courses');
    const tabAbout   = document.getElementById('tab-about');
    const secCourses = document.getElementById('courses-section');
    const secAbout   = document.getElementById('about-section');

    function applyInactive(btn){
        btn.classList.remove('border-ink','font-semibold','text-ink');
        btn.classList.add('border-transparent','text-slate-600');
        btn.setAttribute('aria-selected','false');
        btn.tabIndex = -1;
    }

    function applyActive(btn){
        btn.classList.remove('border-transparent','text-slate-600');
        btn.classList.add('border-ink','font-semibold','text-ink');
        btn.setAttribute('aria-selected','true');
        btn.tabIndex = 0;
    }

    function setTab(which){
        // her iki tabın ortak tab-bordur ayarı dursun
        [tabCourses, tabAbout].forEach(btn=>{
            btn.classList.add('border-b-2', 'pb-3'); // güvence
        });

        if (which === 'courses'){
            applyActive(tabCourses);
            applyInactive(tabAbout);
            secCourses.classList.remove('hidden');
            secAbout.classList.add('hidden');
        } else {
            applyActive(tabAbout);
            applyInactive(tabCourses);
            secAbout.classList.remove('hidden');
            secCourses.classList.add('hidden');
        }
    }

    tabCourses.addEventListener('click', ()=>setTab('courses'));
    tabAbout.addEventListener('click', ()=>setTab('about'));

    // Varsayılan
    setTab('courses');
    // Filtreler
    let activeCategory = '';
    let searchTerm = '';
    let levelFilter = '';

    document.getElementById('categoryPills')?.addEventListener('click', (e)=>{
        if(e.target.closest('button')){
            const btns = Array.from(e.currentTarget.querySelectorAll('button'));
            btns.forEach(b=>{
                b.classList.remove('bg-primary','text-white','border-primary');
                b.classList.add('border-slate-200','text-slate-700');
            });
            const btn = e.target.closest('button');
            btn.classList.add('bg-primary','text-white','border-primary');
            btn.classList.remove('border-slate-200','text-slate-700');

            activeCategory = btn.getAttribute('data-cat') || '';
            filterCourses();
        }
    });

    document.getElementById('searchInput')?.addEventListener('input', (e)=>{
        searchTerm = (e.target.value || '').toLowerCase().trim();
        filterCourses();
    });

    document.getElementById('levelSelect')?.addEventListener('change', (e)=>{
        levelFilter = e.target.value || '';
        filterCourses();
    });

    function filterCourses(){
        const cards = document.querySelectorAll('.course-card');
        let visibleCount = 0;
        cards.forEach(card=>{
            const t = (card.getAttribute('data-title')||'') + ' ' + (card.getAttribute('data-goal')||'');
            const cat = card.getAttribute('data-category')||'';
            const lvl = card.getAttribute('data-level')||'';

            const okCat = !activeCategory || cat === activeCategory;
            const okSearch = !searchTerm || t.includes(searchTerm);
            const okLevel = !levelFilter || lvl === levelFilter;

            const show = okCat && okSearch && okLevel;
            card.classList.toggle('hidden', !show);
            if(show) visibleCount++;
        });

        document.getElementById('emptyState')?.classList.toggle('hidden', visibleCount !== 0);
    }

    // Icons
    lucide.createIcons();
    // Varsayılan: Kurslar sekmesi
</script>
<?php require_once 'footer.php'?>

</body>
</html>
