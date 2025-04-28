import {sendData} from '../../scripts/data_manager.js';

document.addEventListener("DOMContentLoaded", () => {


    const progressBarText = document.getElementById('progressBarText');
    const progress = document.getElementById('progress');
    const timer = document.getElementById('timer');
    const restartButton = document.getElementById('restartButton');

    const instructions = document.getElementById("memory-instructions");
    const startButton = document.getElementById("memory-start-button");
    const memoryPanel = document.getElementById('memoryPanel');

    const stages = [
        { images: 3, duration: 1000 },
        { images: 3, duration: 1000 },
        { images: 3, duration: 1000 },
        { images: 5, duration: 1000 },
        { images: 5, duration: 1000 },
        { images: 5, duration: 1000 },
        { images: 5, duration: 750 },
        { images: 5, duration: 750 },
        { images: 5, duration: 750 }
    ];

    const EMOJIS = ['☀️', '⭐️', '❄️', '⛈️', '🌈', '🌊', '🌻', '🍁', '🌸', '🍕', '🎈', '🚀', '🌺', '🍦', '🎸', '🎨', '🐱', '🚲', '⚽'];
    let currentStage = 0;
    let displayedCount = 0;
    let shownImages = [];
    let correctClicks = 0;
    let totalClicks = 0;
    let startTime = 0;
    let reactionTimes = [];


    function startTest() {
        var mustClick = false;
        var clicked = false;
        var lastEmoji;
        var cooldownImageId = -1;

        var curEmojis = EMOJIS.slice();

        startButton.style.display = 'none';
        memoryPanel.style.display = 'block';
        instructions.textContent = "Как только увидите картинку, появившуюся второй раз, нажмите на неё";
        if (currentStage >= 9) {
            endTest();
            return;
        }

        var { images, duration } = stages[currentStage];
        shownImages = [];

        function showNextImage() {

            if (clicked && mustClick) {
                const timeTaken = (Date.now() - startTime) / 1000;
                correctClicks += Math.floor(currentStage / 3) + 1;
                reactionTimes.push(timeTaken);
                instructions.textContent = "Верно!";
                memoryPanel.textContent = "✔️";
                timer.innerHTML = timeTaken + "ms";
                currentStage++;
                setTimeout(startTest, 2000);
                return;
            } else if (mustClick && !clicked || clicked && !mustClick) {
                instructions.textContent = "Вы не нажали на повторяющуюся картинку!";
                memoryPanel.textContent = "❌";
                currentStage++;
                setTimeout(startTest, 2000);
                return;
            }

            let emoji = curEmojis[Math.floor(Math.random() * curEmojis.length)];
            if ((Math.random() < 0.2 && shownImages.length > 2) || (shownImages.length > images)) {
                emoji = shownImages[Math.floor(Math.random() * (shownImages.length - 1))];
                mustClick = true;
                console.log(emoji, currentStage);
            } else {
                shownImages.push(emoji);
                let ind = curEmojis.indexOf(emoji);
                if (ind > -1) {
                    curEmojis.splice(ind, 1);
                }
            }
            lastEmoji = EMOJIS.indexOf(emoji);

            memoryPanel.textContent = emoji;
            startTime = Date.now();

            displayedCount++;
            progressBarText.textContent = Math.min(currentStage + 1, 9) + "/9";
            progress.style.width = `${Math.min(100, ((currentStage+1) / 9) * 100)}%`;
            cooldownImageId = setTimeout(showNextImage, duration);
        }

        memoryPanel.onclick = function () {
            clicked = true;
            totalClicks++;
            if (cooldownImageId != -1) {
                clearTimeout(cooldownImageId);
                showNextImage();
            }
        };

        showNextImage();
    }

    function endTest() {
        const maxPoints = 3+6+9;
        const accuracy = (correctClicks / maxPoints) * 100;
        var averageReactionTime = reactionTimes.reduce((sum, time) => sum + time, 0) / reactionTimes.length;
        if (isNaN(averageReactionTime)) {
            averageReactionTime = 0;
        }
        console.log(accuracy, maxPoints, averageReactionTime);

        if (accuracy > 10) {


            var stats = {
                accuracy: accuracy.toFixed(2),
                reaction_time: averageReactionTime.toFixed(2)
            }

            saveStats(stats, 13);
        }

        instructions.innerHTML = `Точность: ${accuracy.toFixed(2)}`;
        instructions.innerHTML += "<br>";
        instructions.innerHTML += `Время реакции: ${averageReactionTime.toFixed(2)}`;
    }

    startButton.addEventListener('click', startTest);
    restartButton.addEventListener('click', ()=> location.reload());

    function saveStats(stats, testId) {
        // отправка оценок на серв
        var formData = new FormData();
        formData.append('test_id', testId);
        formData.append('statistics', JSON.stringify(stats));
        // этот метод sendData есть на серваке, локально работать не будет
        var result = sendData(formData, '../../backend/requests/send_user_results.php');
        return result.response;
    }

    function calculateStandardDeviation(data) {
        if (!data || data.length === 0) {
            return 0;
        }

        const n = data.length;
        const mean = data.reduce((acc, val) => acc + val, 0) / n;

        // Вычисляем сумму квадратов разностей от среднего значения
        const sumOfSquares = data.reduce((acc, val) => {
            if (typeof val === 'number') {
                return acc + Math.pow(val - mean, 2);
            } else {
                return acc;
            }
        }, 0);

        // Вычисляем стандартное отклонение
        const variance = sumOfSquares / n;
        return Math.sqrt(variance);
    }
});
