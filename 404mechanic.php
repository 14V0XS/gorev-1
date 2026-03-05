<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>404 - Sayfa Bulunamadı</title>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">

    <script src="https://cdn.tailwindcss.com"></script>
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
    <style>
        :root {
            --primary-color: #E5AE32;
            --primary-hover: #c4952b;
            --dark-metal: #4b5563;
            --light-metal: #d1d5db;
            --text-dark: #1f2937;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            /* overflow: hidden; */ /* İstersen açık bırak; mobilde kesebilir. */
            background: #f3f4f6;
        }

        .container-404 {
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            min-height: 100vh; text-align: center; padding: 1rem;
        }

        .gear-scene {
            position: relative;
            width: min(520px, 92vw);
            height: min(220px, 40vw);
            margin-bottom: 2rem;
            display: flex; justify-content: center; align-items: center;
            overflow: hidden; /* taşmayı sahne içinde gizle */
        }

        .gear {
            position: absolute;
            width: 160px; height: 160px;
            fill: var(--light-metal);
            stroke: var(--dark-metal); stroke-width: 2;
            transform-origin: center;
            opacity: 0;
        }
        .gear.gear-1 {
            left: 0;
            animation: spin-and-settle 3.5s cubic-bezier(0.25,1,0.5,1) forwards;
        }
        .gear.gear-2 {
            width: 200px; height: 200px;
            fill: var(--primary-color);
            animation: spin-and-settle-rev 3.5s cubic-bezier(0.25,1,0.5,1) .2s forwards;
        }
        .gear.gear-3 {
            right: 0;
            animation: spin-and-settle 3.5s cubic-bezier(0.25,1,0.5,1) .4s forwards;
        }

        .gear-number {
            position: absolute; font-weight: 900;
            font-size: clamp(4rem, 14vw, 8rem);
            color: white;
            -webkit-text-stroke: 4px var(--text-dark);
            text-shadow: 0 2px 8px rgba(0,0,0,.15);
            opacity: 0; transform: scale(.5);
            animation: reveal-text .8s ease-out 2.5s forwards;
            line-height: 1;
        }
        /* Soldaki 4 */
        .gear-number.left { left: 6%; transform: translateY(50%) scale(.5); }
        /* Ortadaki 0 (tam merkez) */
        .gear-number.center {  transform: translate(-50%,50%) scale(.5); font-size: clamp(5rem, 17vw, 11rem); }
        /* Sağdaki 4 */
        .gear-number.right { right: 6%;  transform: translateY(50%) scale(.5); }

        .info-text {
            opacity: 0; transform: translateY(20px);
            animation: fade-in-up 1s ease-out 3s forwards;
        }

        @keyframes spin-and-settle {
            0% { opacity:0; transform: rotate(0deg) scale(.5); }
            50% { opacity:1; transform: rotate(1080deg) scale(1.1); }
            100% { opacity:1; transform: rotate(1080deg) scale(1); }
        }
        @keyframes spin-and-settle-rev {
            0% { opacity:0; transform: rotate(0deg) scale(.5); }
            50% { opacity:1; transform: rotate(-1080deg) scale(1.1); }
            100% { opacity:1; transform: rotate(-1080deg) scale(1); }
        }
        @keyframes reveal-text {
            to { opacity:1; transform: scale(1); }
        }
        @keyframes fade-in-up {
            to { opacity:1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<?php require_once 'navbar.php'; ?>

<div class="container-404">
    <div class="bg-white p-8 sm:p-12 rounded-lg shadow-2xl">
        <div class="gear-scene">
            <!-- Dişliler -->
            <svg class="gear gear-1" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M 50,10 A 40,40 0 1,1 49.99,10.01 M 50,10 L 50,18 A 32,32 0 1,1 49.99,18.01 M 50,10 L 58,10 A 40,40 0 0,0 79,21 L 73,26 A 32,32 0 0,1 56,18 M 50,10 L 79,21 L 90,50 L 79,79 L 50,90 L 21,79 L 10,50 L 21,21 Z"></path>
            </svg>
            <svg class="gear gear-2" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M 50,10 A 40,40 0 1,1 49.99,10.01 M 50,10 L 50,18 A 32,32 0 1,1 49.99,18.01 M 50,10 L 58,10 A 40,40 0 0,0 79,21 L 73,26 A 32,32 0 0,1 56,18 M 50,10 L 79,21 L 90,50 L 79,79 L 50,90 L 21,79 L 10,50 L 21,21 Z"></path>
            </svg>
            <svg class="gear gear-3" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M 50,10 A 40,40 0 1,1 49.99,10.01 M 50,10 L 50,18 A 32,32 0 1,1 49.99,18.01 M 50,10 L 58,10 A 40,40 0 0,0 79,21 L 73,26 A 32,32 0 0,1 56,18 M 50,10 L 79,21 L 90,50 L 79,79 L 50,90 L 21,79 L 10,50 L 21,21 Z"></path>
            </svg>

            <!-- 404 -->
            <div class="gear-number left">4</div>
            <div class="gear-number center">0</div>
            <div class="gear-number right">4</div>
        </div>

        <div class="info-text">
            <h1 class="text-3xl sm:text-4xl font-bold" style="color: var(--text-dark);">Sayfa Bulunamadı</h1>
            <p class="mt-4 text-lg text-gray-600">Aradığınız parça bu montajda yok gibi görünüyor.</p>
            <div class="mt-8">
                <a href="<?php echo BASE_URL; ?>/index.php"
                   class="inline-flex items-center gap-2 rounded-lg bg-[var(--primary-color)] hover:bg-[var(--primary-hover)] text-white px-5 py-2.5 font-semibold transition">
                    <i data-lucide="home" class="w-5 h-5"></i> Ana Sayfaya Dön
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
