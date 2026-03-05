<?php require_once 'config.php';

//a

$page_title = "FIRST Terimleri Sözlüğü"; // Sayfa başlığını belirliyoruz

session_start();
$isEn = CURRENT_LANG === 'en';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$glossary = $isEn ? [
    'genel-terimler' => [
        'title' => 'General Terms',
        'items' => [
            ['term' => 'FIRST', 'desc' => 'A nonprofit organization focused on inspiring students in science and technology.'],
            ['term' => 'FRC', 'desc' => 'FIRST Robotics Competition, the flagship high school robotics program in FIRST.'],
            ['term' => 'Gracious Professionalism', 'desc' => 'A core FIRST value that combines strong competition with respect, kindness, and integrity.'],
            ['term' => 'Coopertition', 'desc' => 'A FIRST mindset where teams compete while still helping and learning from each other.'],
            ['term' => 'Rookie', 'desc' => 'A team or member participating in FRC for the first time.'],
            ['term' => 'Veteran', 'desc' => 'A team or member with at least one completed FRC season.'],
        ],
    ],
    'oyun-ve-saha' => [
        'title' => 'Game and Field',
        'items' => [
            ['term' => 'Alliance', 'desc' => 'A group of teams playing together in a match, usually red vs blue alliances.'],
            ['term' => 'Autonomous Period', 'desc' => 'The first 15 seconds of a match where robots run pre-programmed actions.'],
            ['term' => 'Tele-Op Period', 'desc' => 'The driver-controlled part of the match after autonomous mode.'],
            ['term' => 'Endgame', 'desc' => 'The final part of a match with bonus scoring tasks such as climbing.'],
            ['term' => 'Game Piece', 'desc' => 'Objects robots collect, move, place, or score during the season game.'],
            ['term' => 'Driver Station', 'desc' => 'The control area where drivers and operators command the robot.'],
            ['term' => 'Ranking Points (RP)', 'desc' => 'Points used for qualification ranking and playoff seeding.'],
        ],
    ],
    'robot-parcalari' => [
        'title' => 'Robot Parts and Mechanics',
        'items' => [
            ['term' => 'Chassis / Drivetrain', 'desc' => 'The base system including wheels, motors, and structure used for movement.'],
            ['term' => 'Bumper', 'desc' => 'Protective padding around the robot used for safety and alliance identification.'],
            ['term' => 'Manipulator / Actuator', 'desc' => 'Mechanisms used to intake, move, or score game pieces.'],
            ['term' => 'roboRIO', 'desc' => 'The main robot controller that manages motors, sensors, and other electronics.'],
            ['term' => 'Motor Controller', 'desc' => 'Electronics that convert roboRIO commands into motor output.'],
            ['term' => 'Pneumatics', 'desc' => 'Compressed-air systems used to power pistons and mechanisms.'],
            ['term' => 'Sensor', 'desc' => 'Devices such as cameras or gyros that help the robot understand its environment.'],
        ],
    ],
    'takim-ve-roller' => [
        'title' => 'Team and Roles',
        'items' => [
            ['term' => 'Drive Team', 'desc' => 'The match crew that typically includes Driver, Operator, Coach, and Human Player.'],
            ['term' => 'Pit', 'desc' => 'The team workspace where robots are repaired, improved, and prepared.'],
            ['term' => 'Scouting', 'desc' => 'Collecting and analyzing match data to support strategy and alliance decisions.'],
            ['term' => 'Mentor', 'desc' => 'An experienced volunteer who guides students in technical and personal development.'],
        ],
    ],
    'oduller-ve-etkinlikler' => [
        'title' => 'Awards and Events',
        'items' => [
            ['term' => 'Kickoff', 'desc' => 'The annual event where the new FRC game and rules are officially released.'],
            ['term' => 'Regional / District', 'desc' => 'Official event formats where teams compete during the season.'],
            ['term' => 'Championship', 'desc' => 'The world-level final event featuring top teams from around the globe.'],
            ['term' => 'FIRST Impact Award', 'desc' => 'The most prestigious FRC award recognizing long-term community impact.'],
            ['term' => 'Engineering Inspiration Award', 'desc' => 'Recognizes teams that strongly promote engineering culture and inspiration.'],
            ['term' => 'Rookie All-Star Award', 'desc' => 'Given to an outstanding rookie team with strong potential and FIRST values.'],
            ['term' => 'Excellence in Engineering Award', 'desc' => 'Honors robust and effective engineering in robot design and execution.'],
            ['term' => 'Industrial Design Award', 'desc' => 'Recognizes excellence in function-focused and elegant robot design.'],
            ['term' => 'Autonomous Award', 'desc' => 'Rewards consistent and effective autonomous performance.'],
            ['term' => 'Innovation in Control Award', 'desc' => 'Honors creative and effective control systems in software and electronics.'],
            ['term' => 'Creativity Award', 'desc' => 'Recognizes unique and clever mechanical or strategic problem solving.'],
            ['term' => 'Quality Award', 'desc' => 'Given for high-quality workmanship and reliability in robot construction.'],
            ['term' => 'Gracious Professionalism Award', 'desc' => 'Recognizes teams that best model respect and sportsmanship in competition.'],
            ['term' => 'Team Spirit Award', 'desc' => 'Celebrates teams that energize events with enthusiasm and identity.'],
            ['term' => 'Judges’ Award', 'desc' => 'A special award for notable achievements that may not fit other categories.'],
            ['term' => 'Winner', 'desc' => 'Teams on the alliance that wins the final playoff matches.'],
            ['term' => 'Finalist', 'desc' => 'Teams on the alliance that finishes second in the final playoff matches.'],
            ['term' => 'FIRST Dean’s List Award', 'desc' => 'An individual award for students with outstanding leadership and impact.'],
            ['term' => 'Woodie Flowers Finalist Award', 'desc' => 'An individual award honoring exceptional mentors.'],
            ['term' => 'Volunteer of the Year Award', 'desc' => 'Recognizes volunteers with major contributions to event success.'],
            ['term' => 'Digital Animation Award', 'desc' => 'Awarded for outstanding digital animation around STEM/FRC themes.'],
            ['term' => 'Safety Animation Award', 'desc' => 'Recognizes top animation work promoting safety in robotics events.'],
            ['term' => 'Imagery Award', 'desc' => 'Honors teams with outstanding visual identity and branding.'],
            ['term' => 'Team Sustainability Award', 'desc' => 'Recognizes strong long-term team structure and sustainability planning.'],
            ['term' => 'Rising All-Star Award', 'desc' => 'Given to progressing young teams showing strong development potential.'],
            ['term' => 'Founder’s Award', 'desc' => 'Recognizes individuals or organizations that significantly support FIRST values.'],
        ],
    ],
] : [
    'genel-terimler' => [
        'title' => 'Genel Terimler',
        'items' => [
            ['term' => 'FIRST', 'desc' => '"For Inspiration and Recognition of Science and Technology" kelimelerinin baş harflerinden oluşan, gençleri bilim ve teknolojiye teşvik etmek amacıyla kurulmuş olan kâr amacı gütmeyen organizasyon.'],
            ['term' => 'FRC', 'desc' => 'FIRST Robotics Competition. FIRST organizasyonunun lise öğrencilerine yönelik en büyük ve kapsamlı robotik yarışması.'],
            ['term' => 'Gracious Professionalism (Duyarlı Profesyonellik)', 'desc' => 'Hem rekabetin hem de karşılıklı saygının bir arada yürüdüğü bir FRC felsefesi. Rakiplerinize yardım etmeyi ve onlardan öğrenmeyi teşvik eder.'],
            ['term' => 'Coopertition (İşbirlikçi Rekabet)', 'desc' => '"Cooperation" ve "Competition" kelimelerinin birleşimi. Takımların hem rekabet edip hem de birbirlerine yardım ederek daha büyük başarılara ulaşmasını ifade eder.'],
            ['term' => 'Rookie', 'desc' => "FRC'ye ilk defa katılan takım veya üye."],
            ['term' => 'Veteran', 'desc' => 'FRC’de en az bir sezon tecrübesi olan takım veya üye.'],
        ],
    ],
    'oyun-ve-saha' => [
        'title' => 'Oyun ve Saha',
        'items' => [
            ['term' => 'Alliance (İttifak)', 'desc' => 'Maç sırasında birlikte hareket eden takımlar grubu. Genellikle Kırmızı ve Mavi olmak üzere iki ittifak bulunur.'],
            ['term' => 'Autonomous Period (Otonom Dönem)', 'desc' => 'Maçın ilk 15 saniyesi; robotlar sürücü kontrolü olmadan önceden programlanmış görevleri yapar.'],
            ['term' => 'Tele-Op Period (Sürücü Kontrol Dönemi)', 'desc' => 'Otonomdan sonraki sürücü kontrollü bölüm.'],
            ['term' => 'Endgame (Oyun Sonu)', 'desc' => 'Maçın sonunda ekstra puan getiren görevlerin yapıldığı bölüm.'],
            ['term' => 'Game Piece (Oyun Elemanı)', 'desc' => 'Robotların topladığı, taşıdığı ve skorladığı oyun nesneleri.'],
            ['term' => 'Driver Station (Sürücü İstasyonu)', 'desc' => 'Sürücülerin robotu yönettiği kontrol alanı.'],
            ['term' => 'Ranking Points (RP)', 'desc' => 'Takımların sıralamada kullanıldığı puan sistemi.'],
        ],
    ],
    'robot-parcalari' => [
        'title' => 'Robot Parçaları ve Mekanik',
        'items' => [
            ['term' => 'Chassis / Drivetrain', 'desc' => 'Robotun hareketini sağlayan temel mekanik ve aktarma yapısı.'],
            ['term' => 'Bumper (Tampon)', 'desc' => 'Robotu çarpışmalara karşı koruyan güvenlik bileşeni.'],
            ['term' => 'Manipulator / Actuator', 'desc' => 'Oyun elemanlarını toplama, taşıma ve yerleştirme mekanizmaları.'],
            ['term' => 'roboRIO', 'desc' => 'Robotun ana kontrol birimi.'],
            ['term' => 'Motor Controller (Motor Sürücü)', 'desc' => 'Motorlara giden gücü ve komutları yöneten sürücü kartı.'],
            ['term' => 'Pneumatics (Pnömatik Sistem)', 'desc' => 'Basınçlı hava ile çalışan mekanizma sistemi.'],
            ['term' => 'Sensor (Sensör)', 'desc' => 'Robotun çevreyi algılamasını sağlayan bileşenler.'],
        ],
    ],
    'takim-ve-roller' => [
        'title' => 'Takım ve Roller',
        'items' => [
            ['term' => 'Drive Team (Sürücü Ekibi)', 'desc' => 'Maç esnasında robotu yöneten ekip: sürücü, operatör, koç ve insan oyuncu.'],
            ['term' => 'Pit (Pit Alanı)', 'desc' => 'Takımın robot bakım ve hazırlıklarını yaptığı çalışma alanı.'],
            ['term' => 'Scouting', 'desc' => 'Takım performanslarını izleyip veri toplayarak strateji geliştirme süreci.'],
            ['term' => 'Mentor', 'desc' => 'Öğrencilere teknik ve sosyal alanlarda rehberlik eden gönüllü yetişkin.'],
        ],
    ],
    'oduller-ve-etkinlikler' => [
        'title' => 'Ödüller ve Etkinlikler',
        'items' => [
            ['term' => 'Kickoff', 'desc' => 'Yeni FRC oyununun ve kurallarının açıklandığı sezon başlangıç etkinliği.'],
            ['term' => 'Regional / District', 'desc' => 'Takımların sezon boyunca yarıştığı resmi turnuva formatları.'],
            ['term' => 'Championship (Dünya Şampiyonası)', 'desc' => 'Sezon sonunda en iyi takımların yarıştığı final etkinliği.'],
            ['term' => 'FIRST Impact Award (Etki Ödülü)', 'desc' => 'Toplumsal etki ve FIRST değerlerini en güçlü yansıtan takıma verilen prestijli ödül.'],
            ['term' => 'Engineering Inspiration Award', 'desc' => 'Mühendislik bilincini ve ilhamını güçlü şekilde yaygınlaştıran takımlara verilir.'],
            ['term' => 'Rookie All-Star Award', 'desc' => 'İlk yılında güçlü performans ve potansiyel gösteren çaylak takıma verilir.'],
            ['term' => 'Excellence in Engineering Award', 'desc' => 'Robot tasarımında güçlü mühendislik uygulamalarını ödüllendirir.'],
            ['term' => 'Industrial Design Award', 'desc' => 'İşlevsellik ve estetik odaklı tasarım başarısını ödüllendirir.'],
            ['term' => 'Autonomous Award', 'desc' => 'Otonom performansın tutarlılık ve başarısını ödüllendirir.'],
            ['term' => 'Innovation in Control Award', 'desc' => 'Kontrol sistemlerinde yenilikçi çözüm geliştiren takımlara verilir.'],
            ['term' => 'Creativity Award', 'desc' => 'Yaratıcı ve akıllı mekanik/stratejik çözümleri ödüllendirir.'],
            ['term' => 'Quality Award', 'desc' => 'Yüksek üretim kalitesi ve dayanıklılığı olan robotları ödüllendirir.'],
            ['term' => 'Gracious Professionalism Award', 'desc' => 'Sahada ve pitte saygı ile rekabeti birlikte yansıtan takımlara verilir.'],
            ['term' => 'Team Spirit Award', 'desc' => 'Turnuvaya enerji, coşku ve kimlik katan takımlara verilir.'],
            ['term' => 'Judges’ Award', 'desc' => 'Diğer kategorilere girmeyen özel ve değerli başarılara verilen jüri ödülüdür.'],
            ['term' => 'Winner', 'desc' => 'Final maçlarını kazanan ittifaktaki takımların unvanı.'],
            ['term' => 'Finalist', 'desc' => 'Final maçlarını ikinci sırada tamamlayan ittifaktaki takımların unvanı.'],
            ['term' => 'FIRST Dean’s List Award', 'desc' => 'Liderlik ve teknik etki gösteren öğrencilere verilen bireysel ödül.'],
            ['term' => 'Woodie Flowers Finalist Award', 'desc' => 'Öğrencilere ilham veren mentorlara verilen bireysel ödül.'],
            ['term' => 'Volunteer of the Year Award', 'desc' => 'Etkinlik başarısına önemli katkı sağlayan gönüllülere verilen ödül.'],
            ['term' => 'Digital Animation Award', 'desc' => 'STEM/FRC temasını en iyi anlatan dijital animasyon çalışmasını ödüllendirir.'],
            ['term' => 'Safety Animation Award', 'desc' => 'Güvenlik farkındalığını yaratıcı şekilde anlatan animasyonları ödüllendirir.'],
            ['term' => 'Imagery Award', 'desc' => 'Takım kimliği ve görsel bütünlüğü güçlü şekilde yansıtan takımlara verilir.'],
            ['term' => 'Team Sustainability Award', 'desc' => 'Uzun vadeli sürdürülebilir takım yapısı kuran takımlara verilir.'],
            ['term' => 'Rising All-Star Award', 'desc' => 'Gelişimini güçlü şekilde sürdüren genç takımları ödüllendirir.'],
            ['term' => 'Founder’s Award', 'desc' => 'FIRST misyonuna önemli katkı sağlayan kişi veya kurumları onurlandırır.'],
        ],
    ],
];

?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $page_title; ?> • RookieVerse</title>

    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">

    <!-- Google tag (gtag.js) -->

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>

    <script>

        window.dataLayer = window.dataLayer || [];

        function gtag(){dataLayer.push(arguments);}

        gtag('js', new Date());



        gtag('config', 'G-EDSVL8LRCY');

    </script>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

    

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">

    

    <script>

        tailwind.config = {

            theme: { 

                extend: { 

                    colors: { 

                        'custom-yellow': '#E5AE32',

                        'fyk-purple': '#7333B3' // Fikret Yüksel Vakfı moru

                    } 

                } 

            }

        }

    </script>

    

    <style>

        /* Daha akıcı kaydırma için */

        html {

            scroll-behavior: smooth;

        }

        

        /* Sol Menü - Ana Stil */

        .term-menu-container {

            /* Kart gibi görünmesi için: beyaz arka plan, gölge ve yuvarlak kenarlar */

            background-color: white;

            padding: 1rem;

            border-radius: 0.5rem; /* rounded-lg */

            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); /* shadow-md */

            border: 1px solid #e5e7eb; /* Hafif border */

        }

        

        /* Listeden noktaları kaldırma */

        .term-menu-container nav ul, .term-menu-container nav {

            list-style: none;

            padding-left: 0; /* Gerekli olabilir */

        }



        /* Aktif anchor link için stil */

        .term-link.active {

            /* Sarı çizgi isteği */

            border-left: 4px solid #E5AE32; /* custom-yellow */

            font-weight: 600; 

            color: #1f2937; /* Koyu gri metin */

            background-color: #E5AE3233; /* Hafif sarı arka plan */

            /* Karttaki gibi yuvarlak kenar */

            border-radius: 0.375rem; /* rounded-md */

        }

        

        /* Normal link stilini temizleyip yeni yapıyı uyguluyoruz */

        .term-link {

            transition: all 150ms ease-in-out;

            border-left: 4px solid transparent; /* Varsayılan olarak şeffaf çizgi */

            padding-left: 1rem !important; /* pl-4 */

            padding-right: 1rem !important; /* px-4 */

        }



        /* Aktif olmayan hover durumuna soluk çizgi */

        .term-link:not(.active):hover {

            border-left: 4px solid #E5AE3266; /* Soluk sarı çizgi */

            background-color: #E5AE321A; /* Çok hafif sarı arka plan */

            color: #1f2937; /* Koyu metin */

            border-radius: 0.375rem; /* rounded-md */

        }

        

        /* Sözlük terim başlıkları (Term & Açıklama) */

        .term-item {

            display: block; 

        }

    </style>



</head>

<body class="bg-gray-50">



    <?php require_once 'navbar.php'; ?>

    

    <div class="min-h-screen">

        <section class="bg-gradient-to-br from-custom-yellow/10 via-white to-custom-yellow/5 pt-24 pb-16">

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

                <div class="flex justify-center items-center gap-4">

                    <i data-lucide="book-marked" class="w-12 h-12 text-custom-yellow"></i>

                    <h1 class="text-4xl lg:text-5xl font-bold text-gray-900"><?= $isEn ? 'FIRST Glossary' : 'FIRST Terimleri Sözlüğü' ?></h1>

                </div>

                <p class="mt-4 text-xl text-gray-600 max-w-3xl mx-auto"><?= $isEn ? 'Find common terms and abbreviations used across FIRST programs (FRC, FTC, FLL).' : 'FIRST programlarında (FRC, FTC, FLL) sıkça karşılaşacağınız terimlerin ve kısaltmaların açıklamalarını burada bulabilirsiniz.' ?></p>



                <div class="mt-8 max-w-lg mx-auto relative">

                    <input type="text" id="term-search" placeholder="<?= $isEn ? 'Search a term (e.g. Rookie, Alliance, roboRIO)' : 'Terim ara (Örn: Rookie, Alliance, roboRIO)' ?>" 

                           class="w-full pl-12 pr-4 py-3 border-2 border-custom-yellow/50 rounded-full focus:ring-2 focus:ring-custom-yellow focus:border-custom-yellow transition duration-150 shadow-md placeholder-gray-500">

                    <i data-lucide="search" class="w-6 h-6 absolute left-4 top-1/2 transform -translate-y-1/2 text-custom-yellow"></i>

                </div>

                <?php if ($isEn): ?>
                    <p class="mt-4 text-sm text-gray-500">Core labels are localized in English mode. Detailed term descriptions are being expanded gradually.</p>
                <?php endif; ?>

                </div>

        </section>



        <section class="py-16">

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">

                    

                    <aside class="lg:col-span-1 lg:sticky lg:top-24 h-max">

                        <div class="term-menu-container">

                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2"><?= $isEn ? 'Categories' : 'Kategoriler' ?></h3>

                            <nav id="term-menu">

                                <ul class="space-y-1">

                                    <li><a href="#genel-terimler" class="term-link block py-2 text-gray-600"><?= $isEn ? 'General Terms' : 'Genel Terimler' ?></a></li>

                                    <li><a href="#oyun-ve-saha" class="term-link block py-2 text-gray-600"><?= $isEn ? 'Game and Field' : 'Oyun ve Saha' ?></a></li>

                                    <li><a href="#robot-parcalari" class="term-link block py-2 text-gray-600"><?= $isEn ? 'Robot Parts and Mechanics' : 'Robot Parçaları ve Mekanik' ?></a></li>

                                    <li><a href="#takim-ve-roller" class="term-link block py-2 text-gray-600"><?= $isEn ? 'Team and Roles' : 'Takım ve Roller' ?></a></li>

                                    <li><a href="#oduller-ve-etkinlikler" class="term-link block py-2 text-gray-600"><?= $isEn ? 'Awards and Events' : 'Ödüller ve Etkinlikler' ?></a></li>

                                </ul>

                            </nav>

                        </div>

                    </aside>

                    

                    <main class="lg:col-span-3 space-y-12" id="glossary-main">
                        <?php foreach ($glossary as $sectionId => $section): ?>
                            <div id="<?= h($sectionId) ?>" class="space-y-6 scroll-mt-24">
                                <h2 class="text-3xl font-bold text-gray-900 border-l-4 border-custom-yellow pl-4"><?= h($section['title']) ?></h2>
                                <div class="bg-white p-6 rounded-lg shadow-sm border space-y-4 term-category">
                                    <?php foreach ($section['items'] as $item): ?>
                                        <p class="term-item"><strong><?= h($item['term']) ?>:</strong> <?= h($item['desc']) ?></p>
                                    <?php endforeach; ?>

                                    <?php if ($sectionId === 'oduller-ve-etkinlikler'): ?>
                                        <hr class="my-4">
                                        <div class="bg-fyk-purple/10 p-4 rounded-lg border-l-4 border-fyk-purple text-gray-700 mb-4">
                                            <p class="font-semibold text-fyk-purple">
                                                <?= $isEn ? 'For more details, visit the official FRC Turkiye page:' : 'Daha fazla bilgi almak için FRC Türkiye Sayfasını ziyaret edin:' ?>
                                            </p>
                                            <a href="https://www.frcturkiye.org" target="_blank" class="text-fyk-purple font-bold hover:text-fyk-purple/80 underline transition duration-150 ease-in-out">
                                                <?= $isEn ? 'FRC Turkiye Official Website' : 'FRC Türkiye Resmi Web Sitesi' ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </main>

                </div>

            </div>

        </section>



    </div>

    

    <?php require_once 'footer.php'?>



    <script>

        document.addEventListener('DOMContentLoaded', () => {

            lucide.createIcons();



            // 1. ANCHOR VE SCROLL İŞLEVİ

            const menuLinks = document.querySelectorAll('#term-menu a');

            const sections = document.querySelectorAll('main > div[id]');



            const observer = new IntersectionObserver((entries) => {

                entries.forEach(entry => {

                    if (entry.isIntersecting) {

                        menuLinks.forEach(link => {

                            link.classList.remove('active');

                            if (link.getAttribute('href').substring(1) === entry.target.id) {

                                link.classList.add('active');

                            }

                        });

                    }

                });

            }, { rootMargin: "-50% 0px -50% 0px" });



            sections.forEach(section => observer.observe(section));



            // 2. ARAMA İŞLEVİ

            const searchInput = document.getElementById('term-search');

            const termItems = document.querySelectorAll('.term-item');

            

            searchInput.addEventListener('keyup', (e) => {

                const searchValue = e.target.value.toLowerCase().trim();



                termItems.forEach(item => {

    

                    const termText = item.querySelector('strong') ? item.querySelector('strong').textContent.toLowerCase() : item.textContent.toLowerCase();

                    

                    if (termText.includes(searchValue)) {

                        item.style.display = 'block'; // Göster

                    } else {

                        item.style.display = 'none'; // Gizle

                    }

                });





                sections.forEach(section => {

                    const categoryBox = section.querySelector('.term-category');

                    const categoryItems = categoryBox ? categoryBox.querySelectorAll('.term-item') : [];

                    let visibleItemsCount = 0;



                    categoryItems.forEach(item => {

                        if (item.style.display === 'block' || item.style.display === '') {

                            visibleItemsCount++;

                        }

                    });





                    if (visibleItemsCount > 0 || searchValue === '') { 

                        section.style.display = 'block';

                    } else {

                        section.style.display = 'none';

                    }

                });

            });

        });

    </script>



</body>

</html>