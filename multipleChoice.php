<?php require_once 'config.php'; ?>

<!DOCTYPE html>

<html lang="<?= CURRENT_LANG ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= __('mc.page_title') ?></title>

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

        /* Tailwind CSS'e özel renk tanımı */

        :root {

            --custom-yellow: #E5AE32;

        }



        /* Geri sayım çubuğu animasyonu */

        #timer-bar {

            transition: width 1s linear;

            background-color: var(--custom-yellow);

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

        <div class="bg-white rounded-lg shadow-lg p-8">

            <div class="text-center mb-8">

                <h1 class="text-3xl font-bold text-gray-900"><?= __('mc.title') ?></h1>

                <p class="text-gray-600 mt-2"><?= __('mc.subtitle') ?></p>

            </div>



            <div class="flex justify-between items-center mb-6 border-b pb-4">

                <div class="flex items-center space-x-2">

                    <i data-lucide="clock" class="text-custom-yellow"></i>

                    <span class="text-xl font-bold text-gray-800" id="timer-display">30</span><span class="text-gray-600 ml-1"><?= __('game.seconds') ?></span>

                </div>

                <div class="flex items-center space-x-2">

                    <i data-lucide="award" class="text-custom-yellow"></i>

                    <span class="text-xl font-bold text-gray-800" id="score-display">0</span><span class="text-gray-600 ml-1"><?= __('game.score') ?></span>

                </div>

            </div>



            <div class="w-full bg-gray-200 h-2 rounded-full mb-8 overflow-hidden">

                <div id="timer-bar" class="h-full" style="width: 100%;"></div>

            </div>



            <div id="quiz-container">

                <div class="text-center">

                    <h2 id="question-text" class="text-2xl font-semibold mb-6"><?= __('mc.starting') ?></h2>

                    <div id="options-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    </div>

                    <button id="next-button" class="mt-8 px-6 py-3 bg-custom-yellow text-white font-semibold rounded-lg hover:bg-custom-yellow/90 hidden"><?= __('mc.next') ?></button>

                </div>

            </div>



            <div id="results-container" class="text-center hidden">

                <h2 class="text-3xl font-bold text-gray-900 mb-4"><?= __('game.competition_done') ?></h2>

                <p class="text-xl text-gray-700 mb-6"><?= __('game.total_score') ?> <span class="font-bold text-custom-yellow" id="final-score">0</span></p>

                <button onclick="window.location.reload()" class="px-6 py-3 border-2 border-custom-yellow text-custom-yellow font-semibold rounded-lg hover:bg-custom-yellow hover:text-white"><?= __('game.play_again') ?></button>

            </div>

        </div>

    </div>

</div>

<?php require_once 'footer.php'?>



<script>

    lucide.createIcons();



    const quizContainer = document.getElementById('quiz-container');

    const questionText = document.getElementById('question-text');

    const optionsContainer = document.getElementById('options-container');

    const nextButton = document.getElementById('next-button');

    const timerDisplay = document.getElementById('timer-display');

    const scoreDisplay = document.getElementById('score-display');

    const timerBar = document.getElementById('timer-bar');

    const resultsContainer = document.getElementById('results-container');



    // Örnek sorular
    const LANG = '<?= CURRENT_LANG ?>';
    const questions_tr = [

        {

            question: "FRC'de robotların maçı kazandığı skor alanı nedir?",

            options: ["Pit", "Alliance Station", "Driver Station", "Alliance Score"],

            answer: "Alliance Score"

        },

        {

            question: "Türkiye'deki en popüler FRC yarışması nerede düzenlenir?",

            options: ["Ankara", "İzmir", "İstanbul", "Bursa"],

            answer: "İstanbul"

        },

        {

            question: "Robotun sürücüsünün kullandığı alanın adı nedir?",

            options: ["Pit", "Driver Station", "Control Hub", "Pad"],

            answer: "Driver Station"

        },

        {

            question: "FRC'de takımlara verilen bütçe sınırı nedir?",

            options: ["10.000$", "25.000$", "5.000$", "15.000$"],

            answer: "5.000$"

        }

    ];



    const questions_en = [
        {
            question: "What is the scoring area where robots win the match in FRC?",
            options: ["Pit", "Alliance Station", "Driver Station", "Alliance Score"],
            answer: "Alliance Score"
        },
        {
            question: "How many teams are usually in one alliance during an FRC match?",
            options: ["2", "3", "4", "6"],
            answer: "3"
        },
        {
            question: "What is the name of the area where the robot driver operates?",
            options: ["Pit", "Driver Station", "Control Hub", "Pad"],
            answer: "Driver Station"
        },
        {
            question: "What is the first 15-second period of an FRC match called?",
            options: ["Tele-Op", "Autonomous", "Endgame", "Timeout"],
            answer: "Autonomous"
        }
    ];
    const questions = (LANG === 'en') ? questions_en : questions_tr;

    let currentQuestionIndex = 0;

    let score = 0;

    let timeLeft = 30;

    let timerInterval;

    let gameActive = true;



    // Geri sayım çubuğunu ve zamanı günceller

    function startTimer() {

        timerBar.style.width = '100%';

        timerInterval = setInterval(() => {

            timeLeft--;

            timerDisplay.textContent = timeLeft;

            timerBar.style.width = (timeLeft / 30) * 100 + '%';

            if (timeLeft <= 0) {

                endGame();

            }

        }, 1000);

    }



    // Soruyu ekrana yükler

    function loadQuestion() {

        if (!gameActive) return;



        const currentQuestion = questions[currentQuestionIndex];

        questionText.textContent = currentQuestion.question;

        optionsContainer.innerHTML = '';

        nextButton.classList.add('hidden');



        currentQuestion.options.forEach(option => {

            const button = document.createElement('button');

            button.textContent = option;

            button.classList.add('px-4', 'py-3', 'bg-gray-200', 'text-gray-800', 'font-medium', 'rounded-lg', 'hover:bg-custom-yellow/20', 'transition-colors', 'duration-200', 'w-full', 'text-left', 'quiz-option');

            button.onclick = () => selectOption(option, currentQuestion.answer);

            optionsContainer.appendChild(button);

        });

    }



    // Seçeneğe tıklanınca çalışır

    function selectOption(selectedOption, correctAnswer) {

        if (!gameActive) return;



        const options = document.querySelectorAll('.quiz-option');

        options.forEach(option => {

            option.disabled = true; // Tüm seçenekleri deaktive et

            if (option.textContent === correctAnswer) {

                option.classList.remove('bg-gray-200');

                option.classList.add('bg-green-500', 'text-white');

            } else if (option.textContent === selectedOption) {

                option.classList.remove('bg-gray-200');

                option.classList.add('bg-red-500', 'text-white');

            }

        });



        if (selectedOption === correctAnswer) {

            score += 10; // Her doğru cevap için 10 puan

            scoreDisplay.textContent = score;

        }



        nextButton.classList.remove('hidden');

    }



    // Bir sonraki soruya geçer

    function nextQuestion() {

        currentQuestionIndex++;

        if (currentQuestionIndex < questions.length) {

            loadQuestion();

        } else {

            endGame();

        }

    }



    // Oyunu sonlandırır ve sonuçları gösterir

    function endGame() {

        gameActive = false;

        clearInterval(timerInterval);

        quizContainer.classList.add('hidden');

        resultsContainer.classList.remove('hidden');

        document.getElementById('final-score').textContent = score;

    }



    // Buton event listener'ları

    nextButton.addEventListener('click', nextQuestion);



    // Yarışmayı başlat

    startTimer();

    loadQuestion();

</script>



</body>

</html>