<?php require_once 'config.php';

session_start();



$courses = getCourses();

$categories = getApprovedCategories();

$approvedCourses = 0;

foreach ($courses as $course) {

    if ($course['status'] == 'approved') {

        $approvedCourses++;

    }

}





?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title><?= __('courses.page_title') ?></title>

    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">



    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/index.css">



    <script src="https://unpkg.com/lucide@latest"></script>

    <script>

        tailwind.config = {

            theme: {

                extend: {

                    colors: {

                        'custom-yellow': '#E5AE32',

                    }

                }

            }

        }

    </script>

    <!-- Google tag (gtag.js) -->

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>

    <script>

        window.dataLayer = window.dataLayer || [];

        function gtag(){dataLayer.push(arguments);}

        gtag('js', new Date());



        gtag('config', 'G-EDSVL8LRCY');

    </script>

    <style>

        body {

            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;

        }



        @font-face {

            font-family: "Sakana";

            src: url("/Sakana.ttf") format("truetype");

        }



        .rookieverse {

            font-family: "Sakana", system-ui, sans-serif !important;

            font-weight: bold;

            font-size: 1.25rem;

            color: #E5AE32;

        }

    </style>

</head>

<body class="bg-white">



<!--navbar-->

<?php

    require_once("navbar.php");

?>



<div class="min-h-screen py-8">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">

            <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?= __('courses.title') ?></h1>

            <p class="text-xl text-gray-600 max-w-3xl mx-auto">

                <?= __('courses.description') ?>

                

            </p>

        </div>



        <div class="mb-8">
            <h3 class="text-center text-lg font-semibold text-gray-700 mb-3"><?= __('courses.competition_type') ?></h3>
            <div class="flex flex-wrap justify-center gap-4 mb-6">
                <button data-comp-filter="all" class="px-6 py-2 font-medium bg-custom-yellow text-white rounded-md comp-filter-btn"><?= __('courses.all') ?></button>
                <button data-comp-filter="FRC" class="px-6 py-2 font-medium border border-custom-yellow text-custom-yellow hover:bg-custom-yellow hover:text-white rounded-md comp-filter-btn">FRC</button>
                <button data-comp-filter="FTC" class="px-6 py-2 font-medium border border-custom-yellow text-custom-yellow hover:bg-custom-yellow hover:text-white rounded-md comp-filter-btn">FTC</button>
                <button data-comp-filter="FLL" class="px-6 py-2 font-medium border border-custom-yellow text-custom-yellow hover:bg-custom-yellow hover:text-white rounded-md comp-filter-btn">FLL</button>
            </div>
        </div>

        <div class="mb-8">
            <h3 class="text-center text-lg font-semibold text-gray-700 mb-3"><?= __('courses.category') ?></h3>
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <button data-filter="all" class="px-6 py-2 font-medium bg-custom-yellow text-white rounded-md"><?= __('courses.all') ?></button>

                <?php

                foreach ($categories as $category):

                ?>

                    <button data-filter="<?=$category['id']?>"

                            class="px-6 py-2 font-medium border border-custom-yellow text-custom-yellow hover:bg-custom-yellow hover:text-white rounded-md"><?=$category['name']?>

                    </button>

                <?php endforeach;?>

            </div>
        </div>

        <!-- Language Filter -->
        <div class="mb-8">
            <h3 class="text-center text-lg font-semibold text-gray-700 mb-3"><?= __('courses.language_filter') ?></h3>
            <div class="flex flex-wrap justify-center gap-4 mb-6">
                <button data-lang-filter="all" class="px-6 py-2 font-medium bg-custom-yellow text-white rounded-md lang-filter-btn"><?= __('courses.lang_all') ?></button>
                <button data-lang-filter="tr" class="px-6 py-2 font-medium border border-custom-yellow text-custom-yellow hover:bg-custom-yellow hover:text-white rounded-md lang-filter-btn"><?= __('courses.lang_tr') ?></button>
                <button data-lang-filter="en" class="px-6 py-2 font-medium border border-custom-yellow text-custom-yellow hover:bg-custom-yellow hover:text-white rounded-md lang-filter-btn"><?= __('courses.lang_en') ?></button>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 mb-8 px-4">

            <input type="text" placeholder="<?= __('courses.search_placeholder') ?>"

                   class="w-full sm:w-auto flex-1 max-w-lg px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-custom-yellow" />



        </div>



        <!-- Mevcut Metrikler -->

        <div class="grid grid-cols-3 gap-3 sm:gap-6 mb-12">

            <div class="text-center border-2 border-custom-yellow/20 p-4 sm:p-6 rounded-lg">

                <h3 class="text-2xl sm:text-3xl font-bold text-custom-yellow"><?=$approvedCourses?></h3>

                <p class="text-sm sm:text-lg text-gray-500"><?= __('courses.total_courses') ?></p>

            </div>



            <div class="text-center border-2 border-custom-yellow/20 p-4 sm:p-6 rounded-lg">

                <h3 class="text-2xl sm:text-3xl font-bold text-custom-yellow"><?=count($categories)?></h3>

                <p class="text-sm sm:text-lg text-gray-500"><?= __('courses.category_label') ?></p>

            </div>



            <div class="text-center border-2 border-custom-yellow/20 p-4 sm:p-6 rounded-lg">

                <?php $studentcount = getStudents(); ?>

                <h3 class="text-2xl sm:text-3xl font-bold text-custom-yellow"><?=$studentcount?></h3>

                <p class="text-sm sm:text-lg text-gray-500"><?= __('courses.total_students') ?></p>

            </div>

        </div>





        <!-- KURS KARTLARI -->

        <div class="space-y-12">



            <?php

            foreach ($categories as $category):

                $crs = getCoursesByCat($category['id']);

                if(empty($crs)){

                    continue;

                }

            ?>

            <div data-category="<?=$category['id']?>">

                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-8 flex items-center">

                    <div style="width: auto; border-left: 5px solid #e5ae32f9; padding-left: 3px" class="w-1 h-8 mr-4"> <?= $category['name']?></div>

                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                    <?php

                    foreach ($courses as $course):

                        if ($course['category_id'] != $category['id']):

                            continue;

                        elseif ($course['status'] != "approved"):

                            continue;

                        else:

                    ?>

                        <div class="overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:scale-105 border-2 hover:border-custom-yellow/50 rounded-lg course-card" data-comp="<?= htmlspecialchars($course['comp'] ?? 'FRC') ?>" data-lang="<?= htmlspecialchars($course['language'] ?? 'tr') ?>">

                            <div class="aspect-video relative overflow-hidden group">

                                <img src="<?=$course['cover_image_url']?>" alt="<?=$course['title']?>"

                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">

                                <div

                                        class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">

                                    <a href="courseDetails.php?course=<?=$course['course_uid']?>"

                                       class="inline-flex items-center justify-center px-4 py-2 bg-white text-custom-yellow hover:bg-gray-100 rounded-md">

                                        <i data-lucide="play" class="mr-2" style="width: 16px; height: 16px;"></i> <?= __('courses.course_preview') ?>

                                    </a>

                                </div>

                            </div>

                            <div class="p-4">





                                <div class="flex justify-between items-start mb-2">

                                    <h3 class="text-xl font-bold text-gray-900 line-clamp-2"><?=$course['title']?></h3>

                                    <div class="flex flex-col gap-1 items-end">
                                        <?php
                                        if ($course['level'] == 'Başlangıç'):
                                        ?>
                                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-green-800 bg-green-100 border-0 whitespace-nowrap">Başlangıç</span>
                                        <?php
                                        elseif ($course['level'] == 'Orta'):
                                        ?>
                                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-yellow-800 bg-yellow-100 border-0 whitespace-nowrap">Orta</span>
                                        <?php
                                        elseif ($course['level'] == 'İleri'):
                                        ?>
                                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-red-800 bg-red-100 border-0 whitespace-nowrap">İleri</span>
                                        <?php endif;?>
                                        
                                        <?php
                                        $comp = $course['comp'] ?? 'FRC';
                                        $compColors = [
                                            'FRC' => 'text-blue-800 bg-blue-100',
                                            'FTC' => 'text-orange-800 bg-orange-100',
                                            'FLL' => 'text-red-800 bg-red-100'
                                        ];
                                        $compColor = $compColors[$comp] ?? $compColors['FRC'];
                                        ?>
                                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full <?= $compColor ?> border-0 whitespace-nowrap"><?= htmlspecialchars($comp) ?></span>
                                    </div>

                                </div>

                                <p class="text-gray-600 line-clamp-3 mb-4"><?=$course['goal_text']?></p>

                                <div class="flex items-center justify-between mb-4">



                                    <?php

                                    $team = getTeam($course['team_db_id']);

                                    ?>

                                        <div data-team-number="<?= htmlspecialchars($team['team_number']) ?>" class="flex items-center text-sm text-gray-500 hover:cursor-pointer team-name-button"><i data-lucide="users" class="mr-1" style="width: 16px; height: 16px;"></i> <?= $team['team_name']?></div>

                                        <div class="flex items-center text-sm text-gray-500"><i data-lucide="bar-chart-3" class="mr-1" style="width: 16px; height: 16px;"></i> <?= $course['level']?></div>



                                </div>

                                <a href="courseDetails.php?course=<?=$course['course_uid']?>"

                                   class="w-full inline-block text-center bg-custom-yellow hover:bg-opacity-90 text-white font-semibold py-2 transition-all duration-200 rounded-md"><?= __('courses.start_course') ?></a>

                            </div>

                        </div>

                        <?php endif;?>

                    <?php endforeach;?>



                </div>

            </div>

            <?php endforeach;?>











        </div>

    </div>

</div>



<?php require_once 'footer.php'; ?>





<script>

    lucide.createIcons();

    const buttons = document.querySelectorAll('button[data-filter]');
    const compButtons = document.querySelectorAll('button[data-comp-filter]');
    const sections = document.querySelectorAll('div[data-category]');

    const langButtons = document.querySelectorAll('button[data-lang-filter]');

    let activeCompFilter = 'all';
    let activeCategoryFilter = 'all';
    // Auto-select language filter based on site language
    let activeLangFilter = '<?= CURRENT_LANG === "en" ? "en" : "all" ?>';

    document.querySelectorAll('.team-name-button').forEach(button =>{

        button.addEventListener('click', () =>{

            const teamNumberRel = button.dataset.teamNumber; // "data-team-number" -> teamNumber

            window.location.href = `teamCourses.php?team_number=${teamNumberRel}`



        })

    })

    // Comp filter
    compButtons.forEach(button => {
        button.addEventListener('click', () => {
            activeCompFilter = button.getAttribute('data-comp-filter');
            
            // Update button styles
            compButtons.forEach(btn => {
                btn.classList.remove('bg-custom-yellow', 'text-white');
                btn.classList.add('border', 'border-custom-yellow', 'text-custom-yellow');
            });
            button.classList.add('bg-custom-yellow', 'text-white');
            button.classList.remove('border', 'text-custom-yellow');
            
            applyFilters();
        });
    });

    // Language filter
    langButtons.forEach(button => {
        button.addEventListener('click', () => {
            activeLangFilter = button.getAttribute('data-lang-filter');
            
            langButtons.forEach(btn => {
                btn.classList.remove('bg-custom-yellow', 'text-white');
                btn.classList.add('border', 'border-custom-yellow', 'text-custom-yellow');
            });
            button.classList.add('bg-custom-yellow', 'text-white');
            button.classList.remove('border', 'text-custom-yellow');
            
            applyFilters();
        });
    });

    // Auto-select the correct language filter button on page load
    (function(){
        if (activeLangFilter !== 'all') {
            langButtons.forEach(btn => {
                const f = btn.getAttribute('data-lang-filter');
                if (f === activeLangFilter) {
                    btn.classList.add('bg-custom-yellow', 'text-white');
                    btn.classList.remove('border', 'text-custom-yellow');
                } else {
                    btn.classList.remove('bg-custom-yellow', 'text-white');
                    btn.classList.add('border', 'border-custom-yellow', 'text-custom-yellow');
                }
            });
        }
    })();

    // Category filter
    buttons.forEach(button => {

        button.addEventListener('click', () => {

            activeCategoryFilter = button.getAttribute('data-filter');



            buttons.forEach(btn => {

                btn.classList.remove('bg-custom-yellow', 'text-white');

                btn.classList.add('border', 'border-custom-yellow', 'text-custom-yellow');

            });

            button.classList.add('bg-custom-yellow', 'text-white');

            button.classList.remove('border', 'text-custom-yellow');

            applyFilters();

        });

    });

    function applyFilters() {
        sections.forEach(section => {
            const categoryId = section.getAttribute('data-category');
            const cards = section.querySelectorAll('.course-card');
            let hasVisibleCards = false;

            cards.forEach(card => {
                const cardComp = card.getAttribute('data-comp');
                const cardLang = card.getAttribute('data-lang') || 'tr';
                const showByComp = activeCompFilter === 'all' || cardComp === activeCompFilter;
                const showByCategory = activeCategoryFilter === 'all' || categoryId === activeCategoryFilter;
                const showByLang = activeLangFilter === 'all' || cardLang === activeLangFilter;

                if (showByComp && showByCategory && showByLang) {
                    card.classList.remove('hidden');
                    hasVisibleCards = true;
                } else {
                    card.classList.add('hidden');
                }
            });

            // Hide section if no visible cards
            if (activeCategoryFilter === 'all') {
                if (hasVisibleCards) {
                    section.classList.remove('hidden');
                } else {
                    section.classList.add('hidden');
                }
            } else {
                section.classList.remove('hidden');
            }
        });
    }



    // Arama input ve kurs kartlarını seç

    const searchInput = document.querySelector('input[type="text"][placeholder="<?= __('courses.search_placeholder') ?>"]');

    const allSections = document.querySelectorAll('div[data-category]');



    searchInput.addEventListener('input', () => {

        const searchTerm = searchInput.value.trim().toLowerCase();



        allSections.forEach(section => {

            // O kategorideki tüm kurs kartlarını seçiyoruz (grid içindeki kartlar)

            const cards = section.querySelectorAll('.course-card');



            let anyVisible = false; // O kategoride aramaya uyan kurs var mı kontrolü



            cards.forEach(card => {

                // Karttaki başlık: h3 (örneğin)

                const title = card.querySelector('h3');

                if (!title) return;



                const titleText = title.textContent.toLowerCase();
                const cardComp = card.getAttribute('data-comp');
                const cardLang = card.getAttribute('data-lang') || 'tr';
                const categoryId = section.getAttribute('data-category');

                // Check all filters
                const matchesSearch = titleText.includes(searchTerm);
                const matchesComp = activeCompFilter === 'all' || cardComp === activeCompFilter;
                const matchesCategory = activeCategoryFilter === 'all' || categoryId === activeCategoryFilter;
                const matchesLang = activeLangFilter === 'all' || cardLang === activeLangFilter;



                if (matchesSearch && matchesComp && matchesCategory && matchesLang) {

                    card.classList.remove('hidden');

                    anyVisible = true;

                } else {

                    card.classList.add('hidden');

                }

            });



            // Eğer kategori içinde aramaya uyan en az bir kart varsa kategori görünür,

            // yoksa kategori gizlenir

            if (anyVisible || (searchTerm === '' && activeCategoryFilter === 'all')) {

                section.classList.remove('hidden');

            } else {

                section.classList.add('hidden');

            }

        });



        // Eğer arama boşsa, filtreleri uygula

        if (searchTerm === '') {

            applyFilters();

        }

    });

    // Apply filters on page load (for auto-selected language filter)
    applyFilters();

</script>

</body>

</html>

