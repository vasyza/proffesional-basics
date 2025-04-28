import {sendData} from '../../scripts/data_manager.js';

document.addEventListener("DOMContentLoaded", () => {


    const timer2 = document.getElementById('timer2');
    document.addEventListener('keydown', function(event) {
        if (event.key === ' ') {
            document.getElementById('button').click();
        }
    });
    const progressBar = document.querySelector('.progress-bar');
    const square1 = document.getElementById('square3');
    const square2 = document.getElementById('square2');
    const square3 = document.getElementById('square1');
    document.getElementById("backButton").addEventListener("click", function () {
        window.location.href = "tests.html";
    });
    const backButton = document.getElementById('backButton');
    document.getElementById('restartButton').addEventListener('click', function() {
        location.reload();
    });
    var attemptsCount = 0;
    var averageAbsTime = 0;
    var averageTime = 0;
    var left1 = square1.style.left;
    var left2 = square2.style.left;
    var left3 = square3.style.left;
    var time1;
    var time2;
    var time3;
    var width = 0;
    const values = [];
    const valuesAbs = [];
    document.getElementById('menu').style.display = 'block';

    const pickButton1 = function(event) {
        if (event.key === "1") { // или любая другая клавиша, например "a", "Shift", etc.
            stopSquare1(); // вызываем функцию
        }
    };
    const pickButton2 = function(event) {
        if (event.key === "2") { // или любая другая клавиша, например "a", "Shift", etc.
            stopSquare2(); // вызываем функцию
        }
    };
    const pickButton3 = function(event) {
        if (event.key === "3") { // или любая другая клавиша, например "a", "Shift", etc.
            stopSquare3(); // вызываем функцию
        }
    };


    // Получаем ссылку на кнопку и всплывающее окно
    var modal = document.getElementById("modal");


    // Добавляем обработчик события клика на фон окна
    window.onclick = function(event) {
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

    document.getElementsByClassName('close')[0].addEventListener('click', function() {
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
        var time = inputValue;
        // Здесь можно вызвать необходимую функцию и передать значение inputValue
        document.getElementById('menu').style.display = 'none';
        updateProgressBar(time);
        moveSquare1();
        moveSquare2();
        moveSquare3();
    }

    function updateProgressBar(time){
        var bar = document.getElementById("myBar");
        var percent = 1/(time*60)*100;
        var id = setInterval(frame,1000);
        var currentTime = time*60;
        function frame(){
            if (width >= 100 || currentTime===0){
                clearInterval(id);
                stopAll();
            }else{
                width+=percent;
                bar.style.width = width + "%";
                currentTime--;
                const formattedTime = formatTime(currentTime);
                document.getElementById('timer').innerHTML = formattedTime;
            }
        }
    }


    function stopAll(){
        var stDevitation = calculateStandardDeviation(values).toFixed(3);
        var absDevitation = calculateStandardDeviation(valuesAbs).toFixed(3);

        averageTime = (averageTime / attemptsCount).toFixed(3);
        averageAbsTime = (averageAbsTime / attemptsCount).toFixed(3);
        var stats = {
            reaction_time: averageTime,
            reaction_time_module: averageAbsTime,
            standard_deviation: stDevitation,
            standard_deviation_module: absDevitation
        }
        document.getElementById("resultText").innerHTML = "Среднее время с учетом знака: " + averageTime + "<br>Среднее время без учета знака: " + averageAbsTime + "<br>Стандартное отклонение с учётом знака: " + stDevitation + "<br>Стандартное отклонение без учёта знака: " + absDevitation;
        modal.style.display = "block";
        saveStats(stats,7);
        clearInterval(moveSquareInterval1);
        clearInterval(moveSquareInterval2);
        clearInterval(moveSquareInterval3);
    }

    let moveSquareInterval1;
    function moveSquare1(){
        document.addEventListener("keydown", pickButton1);
        if (left1!='') left1 = parseInt(left1);
        else left1 = 5;
        moveSquareInterval1 = setInterval(() =>{
            if (width<25){
                left1+=3;
            }else {
                left1+=5;
            }
            square1.style.left = `${left1}px`;
            if (left1>window.innerWidth - square1.offsetWidth){
                left1 = 5;
            }
        },5);
    }

    let moveSquareInterval2;
    function moveSquare2(){
        document.addEventListener("keydown", pickButton2);
        if (left2!='') left2 = parseInt(left2);
        else left2 = 5;
        moveSquareInterval2 = setInterval(() =>{
            if (width<25){
                left2+=2;
            }else {
                left2+=6;
            }
            square2.style.left = `${left2}px`;
            if (left2>window.innerWidth - square2.offsetWidth){
                left2 = 5;
            }
        },5,5);
    }

    let moveSquareInterval3;
    function moveSquare3(){
        document.addEventListener("keydown", pickButton3);
        if (left3!='') left3 = parseInt(left3);
        else left3 = 5;
        moveSquareInterval3 = setInterval(() =>{
            left3+=3;
            square3.style.left = `${left3}px`;
            if (left3>window.innerWidth - square3.offsetWidth){
                left3 = 5;
            }
        },4.5);
    }

    function stopSquare1(){
        attemptsCount++;
        document.removeEventListener("keydown", pickButton1);
        clearInterval(moveSquareInterval1);
        const parent1 = square1.offsetParent;
        const distance = (window.innerWidth - square1.offsetWidth) / 2 - (parent1 ? square1.offsetLeft + parent1.offsetLeft : square1.offsetLeft);
        let timeStop = -distance/(20/55);
        values.push(timeStop);
        valuesAbs.push(Math.abs(timeStop));
        averageAbsTime+=Math.abs(timeStop);
        averageTime+=timeStop;
        timer2.innerHTML = (averageAbsTime/attemptsCount).toFixed(3) + "ms";
        left1 = 5;
        square1.style.left = `${left1}`;
        setTimeout(moveSquare1,1000);
    }

    function stopSquare2(){
        attemptsCount++;
        document.removeEventListener("keydown", pickButton2);
        clearInterval(moveSquareInterval2);
        const parent2 = square2.offsetParent;
        const distance = (window.innerWidth - square2.offsetWidth) / 2 - (parent2 ? square2.offsetLeft + parent2.offsetLeft : square2.offsetLeft);
        let timeStop = -distance/(20/45);
        values.push(timeStop);
        valuesAbs.push(Math.abs(timeStop));
        averageAbsTime+=Math.abs(timeStop);
        averageTime+=timeStop;
        timer2.innerHTML = (averageAbsTime/attemptsCount).toFixed(3) + "ms";
        left2 = 5;
        square2.style.left = `${left2}`;
        setTimeout(moveSquare2,1000);
    }
    function stopSquare3(){
        attemptsCount++;
        document.removeEventListener("keydown", pickButton3);
        clearInterval(moveSquareInterval3);
        const parent3 = square3.offsetParent;
        const distance = (window.innerWidth - square3.offsetWidth) / 2 - (parent3 ? square3.offsetLeft + parent3.offsetLeft : square3.offsetLeft);
        let timeStop = -distance/(20/30);
        values.push(timeStop);
        valuesAbs.push(Math.abs(timeStop));
        averageAbsTime+=Math.abs(timeStop);
        averageTime+=timeStop;
        timer2.innerHTML = (averageAbsTime/attemptsCount).toFixed(3) + "ms";
        left3 = 5;
        square3.style.left = `${left3}`;
        setTimeout(moveSquare3,1000);
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