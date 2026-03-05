<?php

require_once 'config.php';

session_start();
$isEn = CURRENT_LANG === 'en';



?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <title><?= $isEn ? 'Privacy Policy • RookieVerse' : 'Gizlilik Politikası • RookieVerse' ?></title>

    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">



    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/index.css">

    <script src="https://unpkg.com/lucide@latest"></script>

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

    <h1 class="text-4xl font-bold text-center mb-8"><?= $isEn ? 'Privacy Policy' : 'Gizlilik Politikası' ?></h1>



    <p>

        <?= $isEn
            ? 'At Rookieverse, we care about your privacy. This policy explains how personal data is collected, used, and protected.'
            : 'Rookieverse olarak gizliliğinize önem veriyoruz. Bu politika, kişisel verilerinizin nasıl toplandığını, kullanıldığını ve korunduğunu açıklar.' ?>

    </p>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '1. Data We Collect' : '1. Topladığımız Veriler' ?></h2>

    <ul class="list-disc list-inside space-y-2">

        <?php if ($isEn): ?>
            <li>Email address (through newsletter subscriptions or contact forms)</li>
            <li>IP and browser information (for security and analytics)</li>
            <li>Cookie data (to remember preferences)</li>
        <?php else: ?>
            <li>E-posta adresi (bülten aboneliği veya iletişim formları aracılığıyla)</li>
            <li>IP adresi ve tarayıcı bilgileri (güvenlik ve istatistiksel analiz amacıyla)</li>
            <li>Çerez verileri (tercihleri hatırlamak için)</li>
        <?php endif; ?>

    </ul>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '2. How We Use Data' : '2. Verilerin Kullanımı' ?></h2>

    <p>

        <?= $isEn
            ? 'Collected data is used only to improve platform services, enhance user experience, and contact you when needed. Data is not shared with third parties without permission.'
            : 'Topladığımız veriler yalnızca platform hizmetlerini geliştirmek, kullanıcı deneyimini iyileştirmek ve gerektiğinde sizinle iletişime geçmek için kullanılır. Veriler üçüncü taraflarla izinsiz paylaşılmaz.' ?>

    </p>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '3. Your Rights' : '3. Haklarınız' ?></h2>

    <p>

        <?php if ($isEn): ?>
            You have the right to access, correct, or delete your personal data.
            To exercise these rights, <a href="<?php echo BASE_URL; ?>/contact.php" class="text-custom-yellow underline">contact us</a>.
        <?php else: ?>
            Kişisel verilerinize erişme, düzeltme veya silme hakkına sahipsiniz.
            Bu haklarınızı kullanmak için bizimle <a href="<?php echo BASE_URL; ?>/contact.php" class="text-custom-yellow underline">iletişime geçebilirsiniz</a>.
        <?php endif; ?>

    </p>



    <p class="mt-8 text-sm text-gray-500"><?= $isEn ? 'Last updated: September 30, 2025' : 'Son güncelleme: 30 Eylül 2025' ?></p>

</main>



<?php require_once 'footer.php'; ?>



<script>lucide.createIcons();</script>

</body>

</html>

