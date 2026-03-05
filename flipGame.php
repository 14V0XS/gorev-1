<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="<?= CURRENT_LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('fm.page_title') ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/rokiverse_icon.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/navbar.css">
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-EDSVL8LRCY"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-EDSVL8LRCY');
    </script>
    <style>
        :root {
            --custom-yellow: #E5AE32;
        }

        #game-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 1rem;
            perspective: 1000px;
            max-width: 600px;
        }

        .card {
            position: relative;
            width: 100%;
            height: 150px;
            cursor: pointer;
            transform-style: preserve-3d;
            transition: transform 0.6s ease-in-out;
        }

        .card.flipped {
            transform: rotateY(180deg);
        }

        .card-front, .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 8px;
            border: 3px solid var(--custom-yellow);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            color: #fff;
            padding: 0.5rem;
            transition: border-color 0.3s; /* Renk geçişini de yumuşatalım */
        }

        .card-front {
            background-color: #f7f7f7;
            transform: rotateY(180deg);
        }

        .card-front img {
            max-width: 90%;
            max-height: 70%;
            object-fit: contain;
        }

        .card-front .item-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            font-weight: bold;
            color: #333;
        }

        .card-back {
            background-color: #E5AE32;
        }

        /* DÜZELTİLDİ: Sadece eşleşen kartların iç yüzlerinin kenarlık rengi yeşil olsun */
        .card.matched .card-front,
        .card.matched .card-back {
            border-color: #16a34a; 
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= __('fm.title') ?></h1>
            <p class="text-gray-600 mb-4"><?= __('fm.subtitle') ?></p>

            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <div class="flex items-center space-x-2">
                    <i data-lucide="clock" class="text-custom-yellow"></i>
                    <span class="text-xl font-bold text-gray-800" id="timer-display">0</span><span class="text-gray-600 ml-1"><?= __('game.seconds') ?></span>
                </div>
            </div>

            <div id="game-board" class="mx-auto"></div>

            <div id="results-container" class="hidden mt-8 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4"><?= __('fm.congrats') ?></h2>
                <p class="text-xl text-gray-700 mb-6"><?= __('fm.your_time') ?> <span class="font-bold text-custom-yellow" id="final-time">0</span> <?= __("game.seconds") ?></p>
                <button onclick="window.location.reload()" class="px-6 py-3 border-2 border-custom-yellow text-custom-yellow font-semibold rounded-lg hover:bg-custom-yellow hover:text-white transition-colors duration-200"><?= __('game.play_again') ?></button>
            </div>
        </div>
    </div>
</div>
<?php require_once 'footer.php'?>

<script>
    lucide.createIcons();

    const gameBoard = document.getElementById('game-board');
    const timerDisplay = document.getElementById('timer-display');
    const resultsContainer = document.getElementById('results-container');
    const finalTimeDisplay = document.getElementById('final-time');

    const cardItems = [
        { name: "ALG", image: "assets/images/alg.webp" },
        { name: "APRIL", image: "assets/images/april.jpg" },
        { name: "BATTERY", image: "assets/images/battery.webp" },
        { name: "CAN", image: "assets/images/can.jpg" },
        { name: "CIM", image: "assets/images/cim.jpg" },
        { name: "CORAL", image: "assets/images/coral.webp" },
        { name: "CU60", image: "assets/images/cu60.png" },
        { name: "CUBE", image: "assets/images/cube.webp" },
        { name: "DP", image: "assets/images/dp.webp" },
        { name: "ELEVATOR", image: "assets/images/elevator.webp" },
        { name: "FIELD", image: "assets/images/field.jpg" },
        { name: "GEARBOX", image: "assets/images/gearbox.jpeg" },
        { name: "IMPACT", image: "assets/images/impact.png" },
        { name: "INTAKE", image: "assets/images/intake.webp" },
        { name: "KRAKEN", image: "assets/images/kraken.webp" },
        { name: "MAX", image: "assets/images/max.webp" },
        { name: "MODEM", image: "assets/images/modem.webp" },
        { name: "NEO", image: "assets/images/neo.jpg" },
        { name: "NITRATE", image: "assets/images/nitrate.webp" },
        { name: "NOTE", image: "assets/images/note.jpeg" },
        { name: "PDH", image: "assets/images/pdh.webp" },
        { name: "PNEUMATICS", image: "assets/images/pneumatics.webp" },
        { name: "REFIELD", image: "assets/images/refield.jpg" },
        { name: "ROBORIO", image: "assets/images/roborio.jpg" },
        { name: "SAFETY", image: "assets/images/safety.jpeg" },
        { name: "SPARK", image: "assets/images/spark.jpg" },
        { name: "SWERVE", image: "assets/images/swerve.webp" },
        { name: "VRM", image: "assets/images/vrm.jpg" }
    ];

    let cards = [];
    let flippedCards = [];
    let matchedPairs = 0;
    let time = 0;
    let timerInterval;
    let totalPairs = 0;

    function startGame() {
        const shuffledItems = [...cardItems].sort(() => 0.5 - Math.random());
        const selectedItems = shuffledItems.slice(0, 6);
        totalPairs = selectedItems.length;
        cards = [...selectedItems, ...selectedItems];
        
        cards.sort(() => Math.random() - 0.5);

        gameBoard.innerHTML = '';
        cards.forEach(item => {
            const cardElement = document.createElement('div');
            cardElement.classList.add('card');
            cardElement.dataset.name = item.name;

            const cardFront = document.createElement('div');
            cardFront.classList.add('card-front');
            const img = document.createElement('img');
            img.src = item.image;
            const nameText = document.createElement('div');
            nameText.classList.add('item-name');
            nameText.textContent = item.name;
            cardFront.appendChild(img);
            cardFront.appendChild(nameText);

            const cardBack = document.createElement('div');
            cardBack.classList.add('card-back');
            cardBack.innerHTML = '<i data-lucide="help-circle"></i>';

            cardElement.appendChild(cardFront);
            cardElement.appendChild(cardBack);

            cardElement.addEventListener('click', () => flipCard(cardElement));
            gameBoard.appendChild(cardElement);
        });

        lucide.createIcons();
        startTimer();
    }

    function startTimer() {
        clearInterval(timerInterval);
        time = 0;
        timerDisplay.textContent = time;
        timerInterval = setInterval(() => {
            time++;
            timerDisplay.textContent = time;
        }, 1000);
    }

    function flipCard(card) {
        if (flippedCards.length < 2 && !card.classList.contains('flipped') && !card.classList.contains('matched')) {
            card.classList.add('flipped');
            flippedCards.push(card);

            if (flippedCards.length === 2) {
                setTimeout(checkMatch, 1000);
            }
        }
    }

    function checkMatch() {
        const [card1, card2] = flippedCards;
        const name1 = card1.dataset.name;
        const name2 = card2.dataset.name;

        if (name1 === name2) {
            // DÜZELTİLDİ: Sadece ana karta 'matched' class'ı ekleniyor.
            card1.classList.add('matched');
            card2.classList.add('matched');
            matchedPairs++;

            if (matchedPairs === totalPairs) {
                endGame();
            }
        } else {
            card1.classList.remove('flipped');
            card2.classList.remove('flipped');
        }

        flippedCards = [];
    }

    function endGame() {
        clearInterval(timerInterval);
        finalTimeDisplay.textContent = time;
        resultsContainer.classList.remove('hidden');
        gameBoard.style.display = 'none';
    }

    startGame();
</script>

</body>
</html>