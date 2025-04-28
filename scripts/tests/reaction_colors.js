import { sendData } from '../../scripts/data_manager.js';

const circle = document.getElementById('circle');
const button1 = document.getElementById('button1');
const button2 = document.getElementById('button2');
const button3 = document.getElementById('button3');
const results = document.getElementById('results');
const timer = document.getElementById('timer');
const timerText = document.getElementById('timerText');
const progressBarText = document.getElementById('progressBarText');
button1.style.backgroundColor = 'green';
button2.style.backgroundColor = 'blue';
button3.style.backgroundColor = 'red';
const attempts = document.getElementById('attempts');
const restartButton = document.getElementById('restartButton');
let attemptsCount = 0;
let successes = 0;
let totalTime = 0;
let mistakes = 0;
let currentColor = '#ccc';

var timeoutId = -1;

// можно ли юзать привязку клавиш
var canBind = true;

function startProgress() {
    if (attemptsCount < 15) {
        attemptsCount = 0;
        successes = 0;
        totalTime = 0;
        mistakes = 0;
        canBind = true;
        progress.style.width = '0%';
        const interval = setInterval(() => {
            progress.style.width = `${Math.min(100, (attemptsCount / 15) * 100)}%`;
            if (attemptsCount >= 15) {
                clearInterval(interval);
                button1.removeEventListener('click', handleClick);
                button2.removeEventListener('click', handleClick);
                button3.removeEventListener('click', handleClick);
                canBind = false;

                circle.innerHTML = 'Тест пройден'
                circle.style.backgroundColor = 'green';

                if (successes > 4) {
                    
                    timer.innerHTML = (totalTime/successes).toFixed(3)+" ms";
                    timerText.innerHTML = "Среднее время успешных попыток"+ "<br>Удачных: " + successes + "<br>Пропущенных: " + (attemptsCount - successes - mistakes) + "<br>Ошибок: " + (mistakes);

                    // расчёт оценок
                    var reaction_time = totalTime / successes;
                    var accuracy = Math.round((successes / 15) * 100);

                    var stats = {
                        reaction_time: reaction_time,
                        accuracy: accuracy,
                        mistakes: mistakes
                    }

                    // id теста смотрим на гитхабе, где находятся тз, в разделе инфы "какие идшники у тестов"
                    var response = saveStats(stats, 3);

                    console.log(response);

                } else {
                    timerText.innerHTML = "Результаты не могут быть записаны, т.к. успешных попыток должно быть хотя бы 5.<br> Попробуйте ещё раз";
                }
            }
        }, 1000);
    }
}

function saveStats(stats, testId) {
    // отправка оценок на серв
    var formData = new FormData();
    // id тестов:
    // 1 - Тест на простые визуальные сигналы
    // 2 - Тест на простые звуковые сигналы
    // 3 - Тест на сложные цветные сигналы
    // 4 - Тест сложные цифровые визуальные сигналы
    // 5 - Тест на сложные цифровые звуковые сигналы
    formData.append('test_id', testId);
    formData.append('statistics', JSON.stringify(stats));
    var result = sendData(formData, '../../backend/requests/send_user_results.php');
    return result.response;
}

function handleClick(event) {
    if (attemptsCount < 15) {
        attemptsCount++;
        progressBarText.innerHTML = attemptsCount + "/15";
        if (event.target.style.backgroundColor === currentColor) {
            console.log(event.target.style.backgroundColor, currentColor);
            successes++;
            totalTime += new Date() - startTime;
            timer.innerHTML = new Date() - startTime + "ms";
        } else if (circle.style.backgroundColor != 'gray'){
            mistakes++;
        }
        circle.style.backgroundColor = 'gray';
        clearTimeout(timeoutId);
        startButton();
    }
}

function startButton() {
    if (attemptsCount < 15) {
        circle.style.backgroundColor = 'gray'; // Set circle to gray during delay
        const delay = Math.floor(Math.random() * 2000) + 1000; // Random delay between 1 and 3 seconds
        timeoutId = setTimeout(() => {
            const colors = ['red', 'blue', 'green'];
            startTime = new Date();
            currentColor = colors[Math.floor(Math.random() * colors.length)];
            circle.style.backgroundColor = currentColor;
            button1.addEventListener('click', handleClick);
            button2.addEventListener('click', handleClick);
            button3.addEventListener('click', handleClick);
            canBind = true;
        }, delay);
    }
}

function restartGame() {
    progressBarText.innerHTML = "0/15";
    timer.innerHTML = "00:00";
    timerText.innerHTML = "Время последней успешной попытки";
    circle.innerHTML = "";
    circle.style.backgroundColor = 'gray';
    attemptsCount = 0;
    successes = 0;
    totalTime = 0;
    mistakes = 0;
    progress.style.width = '0%';
    startButton();
    startProgress();
}

let startTime;
startButton();
startProgress();
restartButton.addEventListener('click', restartGame);


// привязка клавиш
document.onkeydown = function (e) {
    e = e || window.event;
    event = {target: {style: {}}};
    switch (e.which || e.keyCode) {
        case 49:
            event.target.style.backgroundColor = 'green';
            handleClick(event)
            break;
        case 50:
            event.target.style.backgroundColor = 'blue';
            handleClick(event)
            break;
        case 51:
            event.target.style.backgroundColor = 'red';
            handleClick(event)
            break;
    }
}