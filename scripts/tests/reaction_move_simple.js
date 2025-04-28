import {sendData} from '../../scripts/data_manager.js';

document.addEventListener("DOMContentLoaded", () => {


    const timer2 = document.getElementById('timer2');
    document.addEventListener('keydown', function (event) {
        if (event.key === ' ') {
            document.getElementById('button').click();
        }
    });
    const progressBar = document.querySelector('.progress-bar');
    const stopButton = document.getElementById('button');
    const square = document.getElementById('square');
    document.getElementById("backButton").addEventListener("click", function () {
        window.location.href = "tests.html";
    });
    const backButton = document.getElementById('backButton');
    document.getElementById('restartButton').addEventListener('click', function () {
        location.reload();
    });
    var attemptsCount = 0;
    var averageAbsTime = 0;
    var averageTime = 0;
    let left = square.style.left;
    let time;
    var width = 0;
    const values = [];
    const valuesAbs = [];
    document.getElementById('menu').style.display = 'block';

// Получаем ссылку на кнопку и всплывающее окно
    var modal = document.getElementById("modal");


    // Добавляем обработчик события клика на крестик закрытия окна
    var span = document.getElementsByClassName("close")[0];
    span.onclick = function () {
        modal.style.display = "none"; // Скрываем всплывающее окно
    }

    // Добавляем обработчик события клика на фон окна
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none"; // Скрываем всплывающее окно, если кликнули на фон окна
        }
    }

    // Функция для форматирования времени в формате mm:ss
    function formatTime(time) {
        const minutes = Math.floor(time / 60);
        const seconds = time % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    document.getElementsByClassName('close')[0].addEventListener('click', function () {
        document.getElementById('menu').style.display = 'none';
    });

    var handleMenuButton = document.getElementById("handleMenuInput");
    handleMenuButton.addEventListener('click', handleMenuInput);

    function handleMenuInput() {
        const inputValue = document.getElementById('menu-input').value;
        if (inputValue < 2 || inputValue > 45) {
            alert('Вы ввели недопустимое значение. Введите числовое значение от 2 до 45.');
            return;
        }
        time = inputValue;
        // Здесь можно вызвать необходимую функцию и передать значение inputValue
        document.getElementById('menu').style.display = 'none';
        updateProgressBar(time);
        moveSquare();
    }

    function updateProgressBar(time) {
        var bar = document.getElementById("myBar");
        var percent = 1 / (time * 60) * 100;
        var id = setInterval(frame, 1000);
        var currentTime = time * 60;

        function frame() {
            if (width >= 100 || currentTime === 0) {
                clearInterval(id);
                stopAll();
            } else {
                width += percent;
                bar.style.width = width + "%";
                currentTime--;
                const formattedTime = formatTime(currentTime);
                document.getElementById('timer').innerHTML = formattedTime;
            }
        }
    }

    var flag = 0;

    function stopAll() {
        flag = 1;
        stopButton.removeEventListener('click', stopSquare);
        var stDevitation = calculateStandardDeviation(values);
        var absDevitation = calculateStandardDeviation(valuesAbs);
        averageTime = (averageTime / attemptsCount).toFixed(3);
        averageAbsTime = (averageAbsTime / attemptsCount).toFixed(3);
        console.log(stats);
        var stats = {
            reaction_time: averageTime,
            reaction_time_module: averageAbsTime,
            standard_deviation: stDevitation,
            standard_deviation_module: absDevitation
        }
        document.getElementById("resultText").innerHTML = "Среднее время с учетом знака: " + averageTime + "<br>Среднее время без учета знака: " + averageAbsTime + "<br>Стандартное отклонение с учётом знака: " + stDevitation + "<br>Стандартное отклонение без учёта знака: " + absDevitation;
        modal.style.display = "block";
        saveStats(stats, 6);
        clearInterval(moveSquareInterval);
    }

    let moveSquareInterval;

    function moveSquare() {
        stopButton.addEventListener('click', stopSquare);
        if (left != '') left = parseInt(left);
        else left = 5;
        moveSquareInterval = setInterval(() => {
            if (width <= 30) {
                left += 20;
            } else if (width <= 66) {
                left += 30;
            } else {
                left += 40;
            }
            square.style.left = `${left}px`;
            if (left > window.innerWidth - square.offsetWidth) {
                left = 5;
            }
        }, 30);
    }

    function stopSquare() {
        attemptsCount++;
        stopButton.removeEventListener('click', stopSquare);
        clearInterval(moveSquareInterval);
        const parent = square.offsetParent;
        const distance = (window.innerWidth - square.offsetWidth) / 2 - (parent ? square.offsetLeft + parent.offsetLeft : square.offsetLeft);
        var timeStop = -distance / (20 / 30);
        values.push(timeStop);
        valuesAbs.push(Math.abs(timeStop));
        averageAbsTime += Math.abs(timeStop);
        averageTime += timeStop;
        timer2.innerHTML = (averageAbsTime / attemptsCount).toFixed(3) + "ms";
        left = 5;
        square.style.left = `${left}`;
        if (flag == 0) {
            setTimeout(moveSquare, 1000);
        }
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


    function saveStats(stats, testId) {
        // отправка оценок на серв
        var formData = new FormData();
        formData.append('test_id', testId);
        formData.append('statistics', JSON.stringify(stats));
        // этот метод sendData есть на серваке, локально работать не будет
        var result = sendData(formData, '../../backend/requests/send_user_results.php');
        return result.response;
    }


// Вызов функции startTimer с временем, которое нужно отсчитать

})