<?php require_once 'config.php'; ?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= __('gg.page_title') ?></title>

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



    <style>

        :root {

            --custom-yellow: #E5AE32;

        }



        #team-name-display {

            min-height: 4rem;

        }



        .game-over-card {

            border: 2px dashed var(--custom-yellow);

            padding: 2rem;

            border-radius: 0.5rem;

            box-shadow: 0 4px 6px rgba(0,0,0,0.1);

            animation: pop-up 0.5s ease-out;

        }



        @keyframes pop-up {

            from { transform: scale(0.8); opacity: 0; }

            to { transform: scale(1); opacity: 1; }

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

    <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bg-white rounded-lg shadow-lg p-8 text-center">

            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= __('gg.title') ?></h1>

            <p class="text-gray-600 mb-4"><?= __('gg.subtitle') ?></p>



            <div id="game-options" class="mb-6 border-b pb-4">

                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">

                    <select id="country-select" class="p-2 border rounded-md">

                        <option value="all"><?= __('gg.all_teams') ?></option>

                        <option value="global"><?= __('gg.global') ?></option>

                        <option value="turkish"><?= __('gg.turkish') ?></option>

                    </select>

                    <select id="difficulty-select" class="p-2 border rounded-md">

                        <option value="easy"><?= __('gg.easy') ?></option>

                        <option value="medium"><?= __('gg.medium') ?></option>

                        <option value="hard"><?= __('gg.hard') ?></option>

                    </select>

                    <button id="start-button" class="px-6 py-2 bg-custom-yellow text-white font-semibold rounded-lg hover:bg-custom-yellow/80 transition-colors"><?= __('game.start_game') ?></button>

                </div>

            </div>



            <div id="game-container" class="hidden">

                <div class="flex justify-between items-center mb-6">

                    <div class="flex items-center space-x-2">

                        <i data-lucide="award" class="text-custom-yellow"></i>

                        <span class="text-xl font-bold text-gray-800" id="score-display">0</span><span class="text-gray-600 ml-1"><?= __('game.score') ?></span>

                    </div>

                    <div class="flex items-center space-x-2">

                        <i data-lucide="clock" class="text-custom-yellow"></i>

                        <span class="text-xl font-bold text-gray-800" id="timer-display">60</span><span class="text-gray-600 ml-1"><?= __('game.seconds') ?></span>

                    </div>

                </div>



                <p class="text-xl font-semibold text-gray-700 mb-6" id="team-name-display"><?= __('gg.preparing') ?></p>

                <div class="w-full flex justify-center mb-6">

                    <input type="number" id="team-number-input" class="p-4 text-center text-4xl font-bold border-2 border-custom-yellow rounded-lg w-48 transition-colors focus:outline-none focus:ring-2 focus:ring-custom-yellow focus:border-transparent" placeholder="????">

                </div>

                <p id="feedback-display" class="text-lg font-semibold h-8"></p>

                <div class="flex justify-center gap-4 mt-6">

                    <button id="guess-button" class="px-6 py-3 bg-custom-yellow text-white font-semibold rounded-lg hover:bg-custom-yellow/80 transition-colors"><?= __('gg.guess') ?></button>

                    <button id="skip-button" class="px-6 py-3 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400"><?= __('game.skip') ?></button>

                </div>

            </div>



            <div id="results-container" class="hidden mt-8 text-center">

                <div class="game-over-card">

                    <h2 class="text-3xl font-bold text-gray-900 mb-4"><?= __('game.game_over') ?></h2>

                    <p class="text-xl text-gray-700 mb-6"><?= __('game.total_score_3') ?> <span class="font-bold text-custom-yellow" id="final-score">0</span></p>

                    <div class="flex flex-col sm:flex-row justify-center gap-4">

                        <button onclick="window.location.reload()" class="px-6 py-3 border-2 border-custom-yellow text-custom-yellow font-semibold rounded-lg hover:bg-custom-yellow hover:text-white transition-colors duration-200"><?= __('game.restart') ?></button>

                        <a href="index.php" class="px-6 py-3 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400 flex items-center justify-center"><?= __('game.back_home') ?></a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<script>

    lucide.createIcons();

    const I18N = <?= json_encode([
        'noTeams' => __('gg.no_teams'),
        'timeUp' => __('gg.time_up'),
        'allDone' => __('gg.all_done'),
        'enterNumber' => __('gg.enter_number'),
        'correct' => __('gg.correct'),
        'wrong' => __('gg.wrong'),
        'skipped' => __('gg.skipped'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;



    const teamNameDisplay = document.getElementById('team-name-display');

    const teamNumberInput = document.getElementById('team-number-input');

    const guessButton = document.getElementById('guess-button');

    const skipButton = document.getElementById('skip-button');

    const feedbackDisplay = document.getElementById('feedback-display');

    const startButton = document.getElementById('start-button');

    const countrySelect = document.getElementById('country-select');

    const difficultySelect = document.getElementById('difficulty-select');

    const gameOptions = document.getElementById('game-options');

    const gameContainer = document.getElementById('game-container');

    const resultsContainer = document.getElementById('results-container');

    const finalScoreDisplay = document.getElementById('final-score');

    const scoreDisplay = document.getElementById('score-display');

    const timerDisplay = document.getElementById('timer-display');



    // Takım verileri

    const allTeams = [

       { number: 2905, name: "Sultans of Türkiye", country: "turkish", difficulty: "easy" },

        { number: 5665, name: "FENERBAHÇE Doğuş Sparc", country: "turkish", difficulty: "easy" },

        { number: 6014, name: "ARC", country: "turkish", difficulty: "easy" },

        { number: 7748, name: "Techtolia Robotics", country: "turkish", difficulty: "easy" },

        { number: 6228, name: "Mat Robotics", country: "turkish", difficulty: "easy" },

        { number: 4972, name: "Borusan Robotics", country: "turkish", difficulty: "easy" },

        { number: 10396, name: "Team Monstra", country: "turkish", difficulty: "easy" },

        { number: 8159, name: "Golden Horn", country: "turkish", difficulty: "easy" },

        { number: 254, name: "The Cheesy Poofs", country: "global", difficulty: "easy" },

        { number: 1114, name: "Simbotics", country: "global", difficulty: "easy" },

        { number: 1678, name: "Citrus Circuits", country: "global", difficulty: "easy" },

        { number: 148, name: "Robowranglers", country: "global", difficulty: "easy" },



        // MEDIUM

        { number: 6989, name: "Kaiser Robotics", country: "turkish", difficulty: "medium" },

        { number: 8182, name: "Loth Robotics", country: "turkish", difficulty: "medium" },

        { number: 9545, name: "Caracal Robotics", country: "turkish", difficulty: "medium" },

        { number: 10246, name: "Infınıtech", country: "turkish", difficulty: "medium" },

        { number: 6038, name: "İTOBOT", country: "turkish", difficulty: "medium" },

        { number: 9468, name: "Team Sirius", country: "turkish", difficulty: "medium" },

        { number: 8084, name: "Alfa Robotics", country: "turkish", difficulty: "medium" },

        { number: 6948, name: "EAGLES", country: "turkish", difficulty: "medium" },

        { number: 6985, name: "ENKA TECH", country: "turkish", difficulty: "medium" },

        { number: 9020, name: "Galatasaray Robotics", country: "turkish", difficulty: "medium" },

        { number: 9523, name: "Archers", country: "turkish", difficulty: "medium" },

        { number: 5655, name: "Kelrot", country: "turkish", difficulty: "medium" },

        { number: 6064, name: "Istanbulls", country: "turkish", difficulty: "medium" },

        { number: 6838, name: "X sharc", country: "turkish", difficulty: "medium" },

        { number: 9026, name: "Aero", country: "turkish", difficulty: "medium" },

        { number: 9441, name: "Aero Jr.", country: "turkish", difficulty: "medium" },

        { number: 9077, name: "The Crown", country: "turkish", difficulty: "medium" },

        { number: 8795, name: "Chaotics", country: "turkish", difficulty: "medium" },

        { number: 9160, name: "YAL Thunders", country: "turkish", difficulty: "medium" },

        { number: 8058, name: "İel Robotics", country: "turkish", difficulty: "medium" },

        { number: 8042, name: "Fenrir", country: "turkish", difficulty: "medium" },

        { number: 7576, name: "Fmwill", country: "turkish", difficulty: "medium" },

        { number: 9483, name: "Istanbul Wildcats", country: "turkish", difficulty: "medium" },

        { number: 6429, name: "4th Dimensions", country: "turkish", difficulty: "medium" },

        { number: 6459, name: "Team AG", country: "turkish", difficulty: "medium" },

        { number: 971, name: "Spartan Robotics", country: "global", difficulty: "medium" },

        { number: 195, name: "CyberKnights", country: "global", difficulty: "medium" },

        { number: 2056, name: "OP Robotics", country: "global", difficulty: "medium" },



        // HARD

        { number: 6415, name: "Gültepe Robotics", country: "turkish", difficulty: "hard" },

        { number: 7086, name: "İo Robotics", country: "turkish", difficulty: "hard" },

        { number: 10576, name: "Ashina", country: "turkish", difficulty: "hard" },

        { number: 6402, name: "Göktürkler", country: "turkish", difficulty: "hard" },

        { number: 10308, name: "Hive Mind", country: "turkish", difficulty: "hard" },

        { number: 7742, name: "Cosmos Robot Works", country: "turkish", difficulty: "hard" },

        { number: 9247, name: "SainTech Robotics", country: "turkish", difficulty: "hard" },

        { number: 10064, name: "HanniBAL", country: "turkish", difficulty: "hard" },

        { number: 8263, name: "Robin", country: "turkish", difficulty: "hard" },

        { number: 10920, name: "F²", country: "turkish", difficulty: "hard" },

        { number: 10213, name: "Balta", country: "turkish", difficulty: "hard" },

        { number: 10244, name: "Wolfs", country: "turkish", difficulty: "hard" },

        { number: 6431, name: "Nokta Parantez", country: "turkish", difficulty: "hard" },

        { number: 10337, name: "Technoka Robotics", country: "turkish", difficulty: "hard" },

        { number: 10202, name: "Novatron", country: "turkish", difficulty: "hard" },

        { number: 10043, name: "Conscius Robotics", country: "turkish", difficulty: "hard" },

        { number: 9264, name: "Hunters Robotics", country: "turkish", difficulty: "hard" },

        { number: 8209, name: "Sezmech", country: "turkish", difficulty: "hard" },

        { number: 8759, name: "Arpex", country: "turkish", difficulty: "hard" },

        { number: 9601, name: "BageTech", country: "turkish", difficulty: "hard" },

        { number: 9021, name: "Uselesscase", country: "turkish", difficulty: "hard" },

        { number: 9422, name: "Forza", country: "turkish", difficulty: "hard" },

        { number: 6232, name: "Florya Bisons", country: "turkish", difficulty: "hard" },

        { number: 6025, name: "Androit Androids", country: "turkish", difficulty: "hard" },

        { number: 9783, name: "Apeiron", country: "turkish", difficulty: "hard" },

        { number: 10502, name: "Harpia Robotics", country: "turkish", difficulty: "hard" },

        { number: 8158, name: "Bosphorus Robotics", country: "turkish", difficulty: "hard" },

        { number: 1241, name: "Theoretically Possible", country: "global", difficulty: "hard" },

        { number: 1538, name: "The Holy Cows", country: "global", difficulty: "hard" }

    ];



    let availableTeams = [];

    let currentTeam = null;

    let score = 0;

    let timeLeft = 60;

    let timerInterval;



    function startGame() {

        const selectedCountry = countrySelect.value;

        const selectedDifficulty = difficultySelect.value;



        availableTeams = allTeams.filter(team => {

            const countryMatch = selectedCountry === 'all' || team.country === selectedCountry;

            const difficultyMatch = team.difficulty === selectedDifficulty;

            return countryMatch && difficultyMatch;

        });



        if (availableTeams.length === 0) {

            alert(I18N.noTeams);

            return;

        }



        gameOptions.classList.add('hidden');

        gameContainer.classList.remove('hidden');



        startTimer();

        loadNewTeam();

    }



    function startTimer() {

        timerInterval = setInterval(() => {

            timeLeft--;

            timerDisplay.textContent = timeLeft;

            if (timeLeft <= 0) {

                endGame(I18N.timeUp);

            }

        }, 1000);

    }



    function loadNewTeam() {

        if (availableTeams.length === 0) {

            endGame(I18N.allDone);

            return;

        }



        const randomIndex = Math.floor(Math.random() * availableTeams.length);

        currentTeam = availableTeams.splice(randomIndex, 1)[0];



        teamNameDisplay.textContent = currentTeam.name;

        teamNumberInput.value = '';

        feedbackDisplay.textContent = '';

        teamNumberInput.focus();

    }



    function checkAnswer() {

        const inputNumber = parseInt(teamNumberInput.value, 10);



        if (isNaN(inputNumber)) {

            feedbackDisplay.textContent = I18N.enterNumber;

            feedbackDisplay.classList.remove('text-green-600');

            feedbackDisplay.classList.add('text-red-600');

            return;

        }



        if (inputNumber === currentTeam.number) {

            feedbackDisplay.textContent = I18N.correct;

            feedbackDisplay.classList.remove('text-red-600');

            feedbackDisplay.classList.add('text-green-600');

            score += 10;

            scoreDisplay.textContent = score;

            loadNewTeam();

        } else {

            feedbackDisplay.textContent = I18N.wrong + ' ' + currentTeam.number;

            feedbackDisplay.classList.remove('text-green-600');

            feedbackDisplay.classList.add('text-red-600');

            setTimeout(loadNewTeam, 1000);

        }

    }



    function skipQuestion() {

        feedbackDisplay.textContent = I18N.skipped + ' ' + currentTeam.number;

        feedbackDisplay.classList.remove('text-green-600');

        feedbackDisplay.classList.add('text-red-600');

        setTimeout(loadNewTeam, 1000);

    }



    function endGame(message) {

        clearInterval(timerInterval);

        gameContainer.classList.add('hidden');

        resultsContainer.classList.remove('hidden');

        resultsContainer.querySelector('h2').textContent = message;

        finalScoreDisplay.textContent = score;

    }



    startButton.addEventListener('click', startGame);

    guessButton.addEventListener('click', checkAnswer);

    skipButton.addEventListener('click', skipQuestion);

    teamNumberInput.addEventListener('keyup', (event) => {

        if (event.key === 'Enter') {

            checkAnswer();

        }

    });



</script>

<?php require_once 'footer.php'?>



</body>

</html>