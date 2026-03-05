<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="<?= CURRENT_LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('tb.page_title') ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">

    <style>
        :root {
            --custom-yellow: #E5AE32;
        }
    </style>

    <script>
        tailwind.config = {
            theme: { extend: { colors: { 'custom-yellow': '#E5AE32' } } }
        }
    </script>
</head>
<body class="bg-gray-100">

<?php require_once 'navbar.php'; ?>

<div class="min-h-screen pt-20 pb-10 flex flex-col items-center">
    <div class="max-w-xl w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= __('tb.title') ?></h1>
            <p class="text-gray-600 mb-4"><?= __('tb.subtitle') ?></p>

            <div id="game-options" class="mb-6 border-b pb-4">
                <button id="start-button" class="px-6 py-2 bg-custom-yellow text-white font-semibold rounded-lg hover:bg-custom-yellow/80 transition-colors"><?= __('game.start_game') ?></button>
            </div>

            <div id="game-container" class="hidden">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="award" class="text-custom-yellow"></i>
                        <span class="text-xl font-bold text-gray-800" id="score-display">0</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i data-lucide="clock" class="text-custom-yellow"></i>
                        <span class="text-xl font-bold text-gray-800" id="timer-display">60</span><span class="text-gray-600 ml-1"><?= __('game.seconds') ?></span>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= __('tb.word_label') ?></h3>
                    <p id="main-word" class="text-4xl font-extrabold text-custom-yellow uppercase tracking-wider min-h-[3rem]">...</p>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= __('tb.taboo_label') ?></h3>
                    <ul id="taboo-words" class="list-disc list-inside text-gray-700 space-y-1">
                        <li>...</li>
                        <li>...</li>
                        <li>...</li>
                        <li>...</li>
                        <li>...</li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button id="correct-button" class="px-6 py-3 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors"><?= __('tb.correct') ?></button>
                    <button id="taboo-button" class="px-6 py-3 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors"><?= __('tb.taboo_done') ?></button>
                    <button id="pass-button" class="px-6 py-3 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400"><?= __('tb.pass') ?></button>
                </div>
            </div>

            <div id="results-container" class="hidden mt-8 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4"><?= __('game.game_over') ?></h2>
                <p class="text-xl text-gray-700 mb-6"><?= __('game.total_score_2') ?> <span class="font-bold text-custom-yellow" id="final-score">0</span></p>
                <button onclick="window.location.reload()" class="px-6 py-3 border-2 border-custom-yellow text-custom-yellow font-semibold rounded-lg hover:bg-custom-yellow hover:text-white transition-colors duration-200"><?= __('game.play_again') ?></button>
            </div>
        </div>
    </div>
</div>
<?php
require_once 'footer.php';
?>
<script>
    lucide.createIcons();

    const startButton = document.getElementById('start-button');
    const gameOptions = document.getElementById('game-options');
    const gameContainer = document.getElementById('game-container');
    const resultsContainer = document.getElementById('results-container');

    const scoreDisplay = document.getElementById('score-display');
    const timerDisplay = document.getElementById('timer-display');
    const mainWordDisplay = document.getElementById('main-word');
    const tabooWordsList = document.getElementById('taboo-words');
    const finalScoreDisplay = document.getElementById('final-score');

    const correctButton = document.getElementById('correct-button');
    const tabooButton = document.getElementById('taboo-button');
    const passButton = document.getElementById('pass-button');

    // FRC Temalı Kelime Verileri
    const LANG = '<?= CURRENT_LANG ?>';
    const tabooWords_tr = [
        { word: "Şasi", taboos: ["Robot", "Tekerlek", "Gövde", "Mekanizma", "Hareket"] },
        { word: "Motor", taboos: ["Güç", "Dönme", "Hareket", "Elektrik", "Alet"] },
        { word: "Otonom", taboos: ["Kendi kendine", "Kodlama", "Robot", "Sürücü", "Yapay zeka"] },
        { word: "Sürücü", taboos: ["İnsan", "Kontrol", "Oynamak", "Joystick", "Kumanda"] },
        { word: "Joystick", taboos: ["Kumanda", "Kontrol", "El", "Oyun", "Robot"] },
        { word: "Dişli", taboos: ["Tekerlek", "Metal", "Çark", "Zincir", "Güç"] },
        { word: "Sensör", taboos: ["Algılamak", "Bilgi", "Mesafe", "Işık", "Kamera"] },
        { word: "Alliance", taboos: ["Takım", "İttifak", "Maç", "Birlik", "Mavi", "Kırmızı"] },
        { word: "Pit Alanı", taboos: ["Garaj", "Robot", "Takım", "Tamir", "Alan"] },
        { word: "Cube", taboos: ["Küp", "Oyun", "Taşımak", "Nesne", "Kutu"] }
    ];

    const tabooWords_en = [
        { word: "Chassis", taboos: ["Robot", "Wheel", "Body", "Mechanism", "Movement"] },
        { word: "Motor", taboos: ["Power", "Rotation", "Movement", "Electric", "Tool"] },
        { word: "Autonomous", taboos: ["Self", "Coding", "Robot", "Driver", "AI"] },
        { word: "Driver", taboos: ["Human", "Control", "Play", "Joystick", "Remote"] },
        { word: "Joystick", taboos: ["Remote", "Control", "Hand", "Game", "Robot"] },
        { word: "Gear", taboos: ["Wheel", "Metal", "Cog", "Chain", "Power"] },
        { word: "Sensor", taboos: ["Detect", "Data", "Distance", "Light", "Camera"] },
        { word: "Alliance", taboos: ["Team", "Coalition", "Match", "Union", "Blue", "Red"] },
        { word: "Pit Area", taboos: ["Garage", "Robot", "Team", "Repair", "Area"] },
        { word: "Cube", taboos: ["Box", "Game", "Carry", "Object", "Square"] }
    ];
    const tabooWords = (LANG === 'en') ? tabooWords_en : tabooWords_tr;

    let gameWords = [...tabooWords];
    let score = 0;
    let timeLeft = 60;
    let timerInterval;

    function startGame() {
        gameOptions.classList.add('hidden');
        gameContainer.classList.remove('hidden');
        score = 0;
        timeLeft = 60;
        scoreDisplay.textContent = score;
        timerDisplay.textContent = timeLeft;
        startTimer();
        loadNewWord();
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            timeLeft--;
            timerDisplay.textContent = timeLeft;
            if (timeLeft <= 0) {
                endGame();
            }
        }, 1000);
    }

    function loadNewWord() {
        if (gameWords.length === 0) {
            // Tüm kelimeler kullanıldıysa, havuzu yenile
            gameWords = [...tabooWords];
        }

        const randomIndex = Math.floor(Math.random() * gameWords.length);
        const currentWord = gameWords.splice(randomIndex, 1)[0];

        mainWordDisplay.textContent = currentWord.word;
        tabooWordsList.innerHTML = '';
        currentWord.taboos.forEach(taboo => {
            const li = document.createElement('li');
            li.textContent = taboo;
            tabooWordsList.appendChild(li);
        });
    }

    function handleCorrect() {
        score += 10;
        scoreDisplay.textContent = score;
        loadNewWord();
    }

    function handleTaboo() {
        score = Math.max(0, score - 5);
        scoreDisplay.textContent = score;
        loadNewWord();
    }

    function handlePass() {
        loadNewWord();
    }

    function endGame() {
        clearInterval(timerInterval);
        gameContainer.classList.add('hidden');
        resultsContainer.classList.remove('hidden');
        finalScoreDisplay.textContent = score;
    }

    // Event listener'lar
    startButton.addEventListener('click', startGame);
    correctButton.addEventListener('click', handleCorrect);
    tabooButton.addEventListener('click', handleTaboo);
    passButton.addEventListener('click', handlePass);

</script>

</body>
</html>