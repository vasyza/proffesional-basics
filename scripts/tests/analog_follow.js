import { sendData } from "../../scripts/data_manager.js";

document.addEventListener("DOMContentLoaded", () => {

    const timer2 = document.getElementById('timer2');
    const progressBar = document.querySelector('.progress-bar');
    const square = document.getElementById('square');
    const bar = document.getElementById('myBar');
    const verticalLine = document.getElementById('verticalLine');

    document.getElementById("backButton").addEventListener("click", function () {
        window.location.href = "tests.html";
    });

    document.getElementById('restartButton').addEventListener('click', function () {
        location.reload();
    });

    var attempts = 0;
    var averageTime = 0;
    var left = 650;
    var right = 0;
    var time;
    var flag = 0;
    var flashok = 0;
    document.getElementById('menu').style.display = 'block';

    var modal = document.getElementById("modal");
    var span = document.getElementsByClassName("close")[0];

    span.onclick = function () {
        modal.style.display = "none";
    }

    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function formatTime(time) {
        const minutes = Math.floor(time / 60);
        const seconds = time % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    document.getElementsByClassName('close')[0].addEventListener('click', function () {
        document.getElementById('menu').style.display = 'none';
    });

    var handleMenuInputb = document.getElementById("handleMenuInput");
    handleMenuInputb.addEventListener("click", handleMenuInput);

    function handleMenuInput() {
        const inputValue = document.getElementById('menu-input').value;
        if (inputValue < 2 || inputValue > 45) {
            alert('Вы ввели недопустимое значение.Введите числовое значение от 2 до 45.');
            return;
        }
        time = inputValue;
        document.getElementById('menu').style.display = 'none';
        updateProgressBar(time);
        moveSquare();
    }

    function updateProgressBar(time){
        var bar = document.getElementById("myBar");
        var width = 0;
        var percent = 1/(time*60)*100;
        var id = setInterval(frame,1000);
        var color = setInterval(check,50);
        var currentTime = time*60;
        document.addEventListener("keydown", handleKeydown);
        moveSquare();
        function check(){
            square.style.backgroundColor = 'red';
            if (isIntersecting(square, verticalLine)) {
                square.style.backgroundColor = 'green';
            }
        }
        function frame(){
            if (width >= 100 || currentTime===0){
                clearInterval(id);
                flashok = 1;
                stopAll();
                document.removeEventListener("keydown", handleKeydown);
            }else{
                width+=percent;
                bar.style.width = width + "%";
                currentTime--;
                const formattedTime = formatTime(currentTime);
                document.getElementById('timer').innerHTML = formattedTime;
            }
        }
    }

    const handleKeydown = function (event) {
        if (flag == 0) {
            attempts++;
            averageTime += new Date() - time;
            timer2.innerHTML = (averageTime / attempts).toFixed(3) + "ms";
        }
        flag = 1;
        switch (event.key) {
            case 'ArrowLeft':
                left -= 25;
                break;
            case 'ArrowRight':
                left += 25;
                break;
        }
        square.style.left = `${left}px`;
    }

    function isIntersecting(obj1, line) {
        const obj1Rect = obj1.getBoundingClientRect();
        const lineRect = line.getBoundingClientRect();

        return (
            obj1Rect.right >= lineRect.left &&
            obj1Rect.left <= lineRect.right
        );
    }

    function moveSquare() {
        document.removeEventListener("keydown", handleKeydown);
        const direction = Math.random() < 0.5 ? -1 : 1;
        const distance = Math.random() * 100;
        const screenWidth = window.innerWidth;
        const squareWidth = square.offsetWidth;

        if (left + direction * distance < 0) {
            left = 0;
        } else if (left + direction * distance + squareWidth > screenWidth) {
            left = screenWidth - squareWidth;
        } else {
            left += direction * distance;
        }

        square.style.left = `${left}px`;
        time = new Date();
        document.addEventListener("keydown", handleKeydown);
        if (flashok==0){
            setTimeout(moveSquare, Math.random() * 2000);
        }
        flag = 0;
    }

    function stopAll(){
        var stats = {
            reaction_time: averageTime/attempts
        }
        saveStats(stats,8);
        document.getElementById("resultText").innerHTML = "Среднее время: " + averageTime/attempts;
        modal.style.display = "block";
        // alert("Среднее время: " + averageTime/attempts);
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

});