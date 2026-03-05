<?php

require_once 'config.php';

session_start();
$isEn = CURRENT_LANG === 'en';



?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <title><?= $isEn ? 'Cookie Policy • RookieVerse' : 'Çerez Politikası • RookieVerse' ?></title>

    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">



    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/index.css">

    <!-- Google tag (gtag.js) -->

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>

    <script>

        window.dataLayer = window.dataLayer || [];

        function gtag(){dataLayer.push(arguments);}

        gtag('js', new Date());



        gtag('config', 'G-EDSVL8LRCY');

    </script>

</head>

<body class="bg-white text-gray-800">



<?php require_once 'navbar.php'; ?>



<main class="max-w-4xl mx-auto px-4 py-20 space-y-8">

    <h1 class="text-4xl font-bold text-center mb-8"><?= $isEn ? 'Cookie Policy' : 'Çerez Politikası' ?></h1>



    <p>

        <?= $isEn
            ? 'Rookieverse uses cookies to improve user experience and site functionality. This page explains what cookies are, how we use them, and how you can manage them.'
            : 'Rookieverse, kullanıcı deneyiminizi geliştirmek ve site işlevselliğini artırmak için çerezleri kullanır. Bu sayfa çerezlerin ne olduğunu, nasıl kullanıldığını ve nasıl yönetileceğini açıklar.' ?>

    </p>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '1. What Is a Cookie?' : '1. Çerez Nedir?' ?></h2>

    <p>

        <?= $isEn
            ? 'Cookies are small data files stored in your browser by websites you visit. They are used to remember preferences and collect statistical information.'
            : 'Çerezler, ziyaret ettiğiniz web siteleri tarafından tarayıcınıza kaydedilen küçük veri dosyalarıdır. Bu dosyalar, site tercihlerinizi hatırlamak ve istatistiksel bilgi toplamak için kullanılır.' ?>

    </p>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '2. Types of Cookies We Use' : '2. Kullandığımız Çerez Türleri' ?></h2>

    <ul class="list-disc list-inside space-y-2">

        <?php if ($isEn): ?>
            <li><b>Essential cookies:</b> Required for core site functionality.</li>
            <li><b>Functional cookies:</b> Save your preferences (e.g. language).</li>
            <li><b>Analytics cookies:</b> Help analyze usage and site performance.</li>
        <?php else: ?>
            <li><b>Zorunlu çerezler:</b> Sitenin temel işlevleri için gereklidir.</li>
            <li><b>İşlevsel çerezler:</b> Tercihlerinizi kaydeder (ör. dil seçimi).</li>
            <li><b>Analitik çerezler:</b> Site performansını ve kullanım istatistiklerini analiz eder.</li>
        <?php endif; ?>

    </ul>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '3. Managing Cookies' : '3. Çerezleri Yönetme' ?></h2>

    <p>

        <?= $isEn
            ? 'You can delete or block cookies from your browser settings. However, some site features may not work properly without cookies.'
            : 'Tarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz. Ancak bazı site özellikleri çerezsiz doğru çalışmayabilir.' ?>

    </p>



    <p class="mt-8 text-sm text-gray-500"><?= $isEn ? 'Last updated: September 30, 2025' : 'Son güncelleme: 30 Eylül 2025' ?></p>

</main>



<?php require_once 'footer.php'; ?>



<script>lucide.createIcons();</script>

</body>

</html>

