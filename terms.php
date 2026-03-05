<?php

require_once 'config.php';

session_start();
$isEn = CURRENT_LANG === 'en';

?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <title><?= $isEn ? 'Terms of Use • RookieVerse' : 'Kullanım Şartları • RookieVerse' ?></title>

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

    <h1 class="text-4xl font-bold text-center mb-8"><?= $isEn ? 'Terms of Use' : 'Kullanım Şartları' ?></h1>



    <p>

        <?= $isEn
            ? 'By using Rookieverse, you agree to the terms below. If you do not agree, please do not use the platform.'
            : 'Rookieverse platformunu kullanarak aşağıdaki şartları kabul etmiş sayılırsınız. Bu şartları kabul etmiyorsanız lütfen platformu kullanmayınız.' ?>

    </p>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '1. Purpose of the Service' : '1. Hizmetin Amacı' ?></h2>

    <p>

        <?= $isEn
            ? 'Rookieverse provides free educational resources, documents, and games for the FIRST community (FRC, FTC, FLL). The services are for educational purposes only.'
            : 'Rookieverse, FIRST topluluğuna (FRC, FTC, FLL) yönelik ücretsiz eğitim kaynakları, dokümanlar ve oyunlar sunan bir platformdur. Hizmetler tamamen eğitim amaçlıdır.' ?>

    </p>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '2. User Responsibilities' : '2. Kullanıcı Sorumlulukları' ?></h2>

    <ul class="list-disc list-inside space-y-2">

        <?php if ($isEn): ?>
            <li>You agree to use the platform only for lawful purposes.</li>
            <li>You are responsible for the accuracy of your content and compliance with copyright rules.</li>
            <li>Your access may be restricted if the platform is abused.</li>
        <?php else: ?>
            <li>Platformu yalnızca yasal amaçlarla kullanmayı kabul edersiniz.</li>
            <li>Paylaştığınız içeriklerin doğruluğundan ve telif haklarına uygunluğundan siz sorumlusunuz.</li>
            <li>Platformu kötüye kullanmanız durumunda erişiminiz engellenebilir.</li>
        <?php endif; ?>

    </ul>



    <h2 class="text-2xl font-semibold mt-6"><?= $isEn ? '3. Right to Modify' : '3. Değişiklik Hakkı' ?></h2>

    <p>

        <?= $isEn
            ? 'Rookieverse reserves the right to modify these terms without prior notice. Updated terms become effective once published on this page.'
            : 'Rookieverse, kullanım şartlarını önceden bildirmeksizin değiştirme hakkını saklı tutar. Güncel şartlar bu sayfada yayımlandığı andan itibaren geçerlidir.' ?>

    </p>



    <p class="mt-8 text-sm text-gray-500"><?= $isEn ? 'Last updated: September 30, 2025' : 'Son güncelleme: 30 Eylül 2025' ?></p>

</main>



<?php require_once 'footer.php'; ?>



<script>lucide.createIcons();</script>

</body>

</html>

