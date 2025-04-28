import { sendData } from '../../scripts/data_manager.js';

const button1 = document.getElementById("number1");
const progress = document.getElementById("progress");
const progressBarText = document.getElementById("progressBarText");
const timer = document.getElementById("timer");
const timerText = document.getElementById("timerText");
const button2 = document.getElementById("number2");
const buttonEven = document.getElementById('button-even');
const buttonOdd = document.getElementById('button-odd');
const restartButton = document.getElementById('restartButton');
let attemptsCount = 0;
let successes = 0;
let totalTime = 0;
let task;

var timeoutId = -1;
var canBind = true;

function startProgress() {
    attemptsCount = 0;
    successes = 0;
    totalTime = 0;
    progress.style.width = '0%';
    canBind = true;
    const interval = setInterval(() => {
        progress.style.width = `${Math.min(100, (attemptsCount / 15) * 100)}%`;
        if (attemptsCount >= 15) {
            clearInterval(interval);
            buttonEven.removeEventListener('click', handleAnswer);
            buttonOdd.removeEventListener('click', handleAnswer);
            canBind = false;
            
            if (successes > 4) {
                    
                timer.innerHTML = (totalTime/successes).toFixed(3)+" ms";
                timerText.innerHTML = "Среднее время успешных попыток"+ "<br>Удачных: " + successes + "<br>Пропущенных: " + (attemptsCount - successes);

                // расчёт оценок
                var reaction_time = totalTime / successes;
                var accuracy = Math.round((successes / 15) * 100);

                var stats = {
                    reaction_time: reaction_time,
                    accuracy: accuracy
                }

                // id теста смотрим на гитхабе, где находятся тз, в разделе инфы "какие идшники у тестов"
                var response = saveStats(stats, 4);

                console.log(response);

            } else {
                timerText.innerHTML = "Результаты не могут быть записаны, т.к. успешных попыток должно быть хотя бы 5.<br> Попробуйте ещё раз";
                console.log(successes, totalTime);
            }
        }
    }, 1000);
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

function generateTask() {
    const num1 = Math.floor(Math.random() * 100);
    const num2 = Math.floor(Math.random() * 100);
    const operator = '+';
    button1.innerHTML = num1;
    button2.innerHTML = num2;
    task = `${num1} ${operator} ${num2}`;
    // Set the delay to a random value between 2000 and 3500 milliseconds
    const delay = Math.floor(Math.random() * 1501) + 2000;

    // Call the startButton function after the delay
    startTime = new Date();
    timeoutId = setTimeout(() => {
        if (!task) {
            generateTask();
        }
        buttonEven.addEventListener('click', handleAnswer);
        buttonOdd.addEventListener('click', handleAnswer);
        canBind = true;
    }, delay);
}

function handleAnswer(event) {
    if (attemptsCount < 15) {
        attemptsCount++;
        progressBarText.innerHTML = attemptsCount + "/15";
        const result = eval(task);
        if (event.target.id === 'button-even' && result % 2 === 0) {
            successes++;
            totalTime += new Date() - startTime;
            timer.innerHTML = (new Date() - startTime) + "ms";
            task = null;
            generateTask();
        } else if (event.target.id === 'button-odd' && result % 2 !== 0) {
            successes++;
            totalTime += new Date() - startTime;
            timer.innerHTML = (new Date() - startTime) + "ms";
            task = null;
            generateTask();
        } else {
            task = null;
            generateTask();
        }
        clearTimeout(timeoutId);
    }
}


function restartGame() {
    timer.innerHTML = "0 ms";
    timerText.innerHTML = "Время последней успешной попытки";
    progressBarText.innerHTML = "0/15";
    attemptsCount = 0;
    successes = 0;
    totalTime = 0;
    progress.style.width = '0%';
    results.textContent = '';
    attempts.textContent = '';
    task = null;
    generateTask();
    startProgress();
}

let startTime;
generateTask();
startProgress();
restartButton.addEventListener('click', restartGame);



// привязка клавиш
document.onkeydown = function (e) {
    e = e || window.event;
    event = {target: {id : {}}};
    switch (e.which || e.keyCode) {
        case 49:
            event.target.id = 'button-even';
            handleAnswer(event);
            break;
        case 50:
            event.target.id = 'button-odd';
            handleAnswer(event);
            break;
    }
}