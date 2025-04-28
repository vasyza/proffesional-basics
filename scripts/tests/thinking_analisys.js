import {sendData} from '../../scripts/data_manager.js';

document.addEventListener("DOMContentLoaded", () => {
    const stages = [
        { difficulty: "легкий", questions: 4, points: 1 },
        { difficulty: "средний", questions: 4, points: 2 },
        { difficulty: "сложный", questions: 4, points: 3 },
    ];
    const dayNames = [
        "Воскресенье",
        "Понедельник",
        "Вторник",
        "Среда",
        "Четверг",
        "Пятница",
        "Суббота",
    ];

    let currentStage = 0;
    let currentQuestion = 0;
    let score = 0;
    let reactionTimes = [];
    let startTime;
    let correctAnswer;

    function startTest() {
        document.getElementById("start-button").style.display = "none";
        document.getElementById("question-container").innerText = "";
        document.getElementById("score-container").style.display = "block";
        document.getElementById("progress-container").style.display = "block";
        document.getElementById("button-container").style.display = "flex";
        score = 0;
        reactionTimes = [];
        currentStage = 0;
        currentQuestion = 0;
        updateScore();
        nextQuestion();
    }

    function nextQuestion() {
        if (currentStage >= stages.length) {
            displayResults();
            return;
        }
        if (currentQuestion >= stages[currentStage].questions) {
            currentStage++;
            currentQuestion = 0;
            if (currentStage >= stages.length) {
                displayResults();
                return;
            }
        }
        updateProgressBar();
        generateQuestion();
        startTime = new Date().getTime();
    }

    function generateQuestion() {
        let randomDate = new Date();
        randomDate.setDate(randomDate.getDate() + Math.floor(Math.random() * 100) - 50);
        let question = "";
        let daysOffset = 0;
        const randomChoice = Math.floor(Math.random() * 5);
        const todayDay = randomDate.getDay();

        if (stages[currentStage].difficulty === "легкий") {
            daysOffset = 2 + Math.floor(Math.random() * 7);
            correctAnswer = dayNames[(todayDay + daysOffset) % 7];
            if (randomChoice === 0) {
                question = `День недели: ${dayNames[todayDay]}, какой день будет через ${daysOffset} дней?`;
            } else if (randomChoice === 1) {
                question = `Какой день недели будет через ${daysOffset} дней от нынешнего дня?`;
                correctAnswer = dayNames[((new Date()).getDay() + daysOffset) % 7];
            } else if (randomChoice === 2) {
                question = `Сегодня ${dayNames[todayDay]}, какой день будет через ${daysOffset} дней?`;
            } else if (randomChoice === 3) {
                question = `Какой день недели будет через ${daysOffset} дней, если сегодня ${dayNames[todayDay]}?`;
            } else {
                question = `Если сегодня ${dayNames[todayDay]}, какой день будет через ${daysOffset} дней?`;
            }
        } else if (stages[currentStage].difficulty === "средний") {
            daysOffset = Math.round(2+Math.random()*3);
            let pastDay = new Date(randomDate);
            pastDay.setDate(randomDate.getDate() - 2);
            const pastDayIndex = pastDay.getDay();
            correctAnswer = dayNames[(pastDayIndex + daysOffset) % 7];
            if (randomChoice === 0) {
                question = `Два дня назад был ${dayNames[mod(pastDayIndex-2,7)]}, какой день будет через ${daysOffset} дней?`;
            } else if (randomChoice === 1) {
                question = `Какой день будет через ${daysOffset} дней, если два дня назад был ${dayNames[mod(pastDayIndex-2,7)]}?`;
            } else if (randomChoice === 2) {
                question = `Два дня назад был ${dayNames[mod(pastDayIndex-2,7)]}, какой день будет через ${daysOffset} дней?`;
            } else if (randomChoice === 3) {
                question = `Какой день будет через ${daysOffset} дней, если два дня назад был ${dayNames[mod(pastDayIndex-2,7)]}?`;
            } else {
                question = `Два дня назад был ${dayNames[mod(pastDayIndex-2,7)]}, какой день через ${daysOffset} дней?`;
            }
        } else if (stages[currentStage].difficulty === "сложный") {
            daysOffset = Math.round(3+Math.random()*3);
            let futureDay = new Date(randomDate);
            futureDay.setDate(randomDate.getDate() + daysOffset);
            const futureDayIndex = futureDay.getDay();
            correctAnswer = dayNames[mod(futureDayIndex - 2,  7)]; // Ensure proper wrapping
            if (randomChoice === 0) {
                question = `Через ${daysOffset} дней я скажу, что завтра будет ${dayNames[mod(futureDayIndex + daysOffset + 1,  7)]}. Какой день был позавчера?`;
            } else if (randomChoice === 1) {
                question = `Если через ${daysOffset} дней я скажу, что завтра будет ${dayNames[mod(futureDayIndex + daysOffset+1,  7)]}, какой день был два дня назад?`;
            } else if (randomChoice === 2) {
                question = `Через ${daysOffset} дней будет ${dayNames[mod(futureDayIndex + daysOffset,  7)]}, какой день был два дня назад?`;
            } else if (randomChoice === 3) {
                question = `Какой день был два дня назад, если через ${daysOffset} дней будет ${dayNames[mod(futureDayIndex + daysOffset,  7)]}?`;
            } else {
                question = `Если через ${daysOffset} дней будет ${dayNames[mod(futureDayIndex + daysOffset,  7)]}, какой день был два дня назад?`;
            }
            console.log(daysOffset, "Выбрана " + dayNames[futureDay.getDay()], correctAnswer);
        }
        document.getElementById("question-container").innerText = question;
    }

    document.getElementById("day_0").onclick = submitAnswer;
    document.getElementById("day_1").onclick = submitAnswer;
    document.getElementById("day_2").onclick = submitAnswer;
    document.getElementById("day_3").onclick = submitAnswer;
    document.getElementById("day_4").onclick = submitAnswer;
    document.getElementById("day_5").onclick = submitAnswer;
    document.getElementById("day_6").onclick = submitAnswer;
    function submitAnswer(answer) {
        if (this.id === "day_0") {
            answer = 'Понедельник';
        }
        if (this.id === "day_1") {
            answer = 'Вторник';
        }
        if (this.id === "day_2") {
            answer = 'Среда';
        }
        if (this.id === "day_3") {
            answer = 'Четверг';
        }
        if (this.id === "day_4") {
            answer = 'Пятница';
        }
        if (this.id === "day_5") {
            answer = 'Суббота';
        }
        if (this.id === "day_6") {
            answer = 'Воскресенье';
        }
        let endTime = new Date().getTime();
        let reactionTime = (endTime - startTime) / 1000;
        reactionTimes.push(reactionTime);
        if (answer === correctAnswer) {
            score += stages[currentStage].points;
            updateScore();
        }
        currentQuestion++;
        nextQuestion();
    }

    function updateProgressBar() {
        let progressBar = document.getElementById("progress-bar");
        let progress = ((currentStage * stages[0].questions + currentQuestion) / (stages.length * stages[0].questions)) * 100;
        progressBar.style.width = progress + "%";
    }

    function updateScore() {
        document.getElementById("score").innerText = score;
    }

    function displayResults() {
        let meanReactionTime = reactionTimes.reduce((a, b) => a + b) / reactionTimes.length;
        let stdDeviation = Math.sqrt(reactionTimes.map((x) => Math.pow(x - meanReactionTime, 2)).reduce((a, b) => a + b) / reactionTimes.length);
        document.getElementById("test-container").style.display = "none";
        document.getElementById("progress-container").style.display = "none";
        document.getElementById("score-container").style.display = "none";
        document.getElementById("results").innerHTML = `
            <h2>Результаты</h2>
            <p>Точность: ${score}</p>
            <p>Среднее время реакции: ${meanReactionTime.toFixed(2)} секунды</p>
            <p>Стандартное отклонение времени реакции: ${stdDeviation.toFixed(2)}</p>
            
            <button id="retry" class="button">Пройти тест заново</button>
        `;
        var stats = {
            accuracy: (score/28*100).toFixed(2),
            reaction_time: meanReactionTime.toFixed(2),
            standart_deviation: stdDeviation.toFixed(2)
        }
        var response = saveStats(stats, 14);
    console.log(response);
    var retryBtn = document.getElementById("retry");
    if (retryBtn != null) {
      retryBtn.addEventListener("click", retryTest);
    }
    }

    function retryTest() {
        document.getElementById("results").innerHTML = "";
        document.getElementById("test-container").style.display = "block";
        document.getElementById("start-button").style.display = "inline";
        document.getElementById("progress-container").style.display = "none";
        document.getElementById("score-container").style.display = "none";
        document.getElementById("button-container").style.display = "none";
        document.getElementById("question-container").innerText = 'Нажмите "Начать тест", чтобы начать.';
        score = 0;
        updateScore();
    }
    const startButton = document.getElementById("start-button");
    startButton.addEventListener("click", startTest);
    function saveStats(stats, testId) {
      // отправка оценок на серв
      var formData = new FormData();
      formData.append("test_id", testId);
      formData.append("statistics", JSON.stringify(stats));
      // этот метод sendData есть на серваке, локально работать не будет
      var result = sendData(
        formData,
        "../../backend/requests/send_user_results.php"
      );
      return result.response;
    }

    function mod(n,m) {
        return (n % m + m) % m;
    }

});