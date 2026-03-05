<?php $projectRoot = dirname(__DIR__); // C:\xampp\htdocs\projeadi
require_once($projectRoot . '/config.php'); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Erişim Reddedildi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <style>
        :root {
            --primary-color: #E5AE32;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.8rem 2rem;
            background-color: var(--primary-color); color: white; border: none;
            border-radius: 0.5rem; cursor: pointer; font-size: 1.1rem;
            font-weight: 600; text-decoration: none; transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #c4952b;
        }
        
        /* --- YENİ ANİMASYONLU KİLİT STİLLERİ --- */
        .animated-lock {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem auto;
        }
        .animated-lock .lock-shackle {
            stroke: var(--primary-color);
            stroke-width: 8;
            fill: none;
            stroke-linecap: round;
            /* Animasyonun başlangıç pozisyonu (açık kilit) */
            transform-origin: 50% 100%;
            transform: translateY(-15px) rotate(20deg);
            /* Animasyonu tanımla */
            animation: close-lock 0.8s ease-in-out forwards;
        }
        .animated-lock .lock-body {
            fill: var(--primary-color);
        }

        /* Kapanma Animasyonu */
        @keyframes close-lock {
            0% {
                transform: translateY(-15px) rotate(20deg);
            }
            50% {
                transform: translateY(-15px) rotate(0deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
<?php require_once '../navbar.php'; ?>
<div class="max-w-4xl mx-auto py-20 px-4 text-center">
    <div class="bg-white p-12 rounded-lg shadow-lg">
        
        <svg class="animated-lock" viewBox="0 0 100 125">
            <path class="lock-shackle" d="M25,55 V30 C25,16.2 36.2,5 50,5 C63.8,5 75,16.2 75,30 V55"/>
            <rect class="lock-body" x="10" y="50" width="80" height="60" rx="10"/>
        </svg>

        <h1 class="mt-6 text-4xl font-bold text-gray-800">Erişim Reddedildi</h1>
        <p class="mt-4 text-lg text-gray-600">
            Bu kursu görüntüleme veya düzenleme yetkiniz bulunmamaktadır. <br>
            Lütfen doğru sayfada olduğunuzdan emin olun.
        </p>
        <div class="mt-8">
            <a href="panel.php" class="btn">
                <i data-lucide="arrow-left" class="mr-2"></i> Panele Geri Dön
            </a>
        </div>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>