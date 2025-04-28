import { sendData } from "../../scripts/data_manager.js";

document.addEventListener("DOMContentLoaded", () => {
  const questions = [
    { question: "Атмосферный окурок", answer: "кактус", stage: 1 },
    {
      question: "Ваза с видом изо рта",
      answer: "труба",
      stage: 1,
    },
    { question: "Ёжик под наркозом", answer: "кактус", stage: 1 },
    { question: "Мост через ночь", answer: "кровать", stage: 1 },
    {
      question: "Шарикоподшипник с кубическими шариками",
      answer: "зубы",
      stage: 2,
    },
    { question: "Горстка вечных конфет", answer: "монеты", stage: 2 },
    { question: "Таблетки от безденежья", answer: "монеты", stage: 2 },
    { question: "Интеллектуальное ведро", answer: "каска", stage: 2 },
    { question: "Яма вверх дном", answer: "парашют", stage: 3 },
    {
      question:
        "Туземцы говорят, что на черный день красного вина не напасёшься",
      answer: "пессимизм",
      stage: 3,
    },
    { question: "Зеркало для души", answer: "книга", stage: 3 },
    {
      question: "Металлический голосок",
      answer: "колокольчик",
      stage: 3,
    },
    { question: "Светящийся сосуд", answer: "лампа", stage: 1 },
    { question: "Небесное тело с хвостом", answer: "комета", stage: 1 },
    { question: "Железный конь", answer: "велосипед", stage: 2 },
    { question: "Дом на колесах", answer: "автодом", stage: 2 },
    {
      question: "Перевёрнутая пирамидка",
      answer: "ветряная мельница",
      stage: 3,
    },
    { question: "Крылатый корабль", answer: "самолёт", stage: 3 },
  ];

  let selectedQuestions = [];
  let currentQuestionIndex = 0;
  let score = 0;
  let startTime;
  let reactionTimes = [];
  let userAnswers = [];

  function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [array[i], array[j]] = [array[j], array[i]];
    }
  }

  function startTest() {
    document.getElementById("start-button").style.display = "none";
    document.getElementById("test-container").style.display = "block";
    document.getElementById("progress-bar").style.width = "0%";
    document.getElementById("progress-container").style.display = "block";
    currentQuestionIndex = 0;
    score = 0;
    reactionTimes = [];
    userAnswers = [];
    selectedQuestions = [];

    // Shuffle and select questions
    shuffleArray(questions);
    let easyQuestions = questions
      .filter((q) => q.stage === 1)
      .slice(0, 3);
    let mediumQuestions = questions
      .filter((q) => q.stage === 2)
      .slice(0, 3);
    let hardQuestions = questions
      .filter((q) => q.stage === 3)
      .slice(0, 3);
    selectedQuestions = [
      ...easyQuestions,
      ...mediumQuestions,
      ...hardQuestions,
    ];

    nextQuestion();
  }

  function nextQuestion() {
    if (currentQuestionIndex >= selectedQuestions.length) {
      displayResults();
      return;
    }
    document.getElementById("question-container").innerText =
      selectedQuestions[currentQuestionIndex].question;
    document.getElementById("answer-input").value = "";
    document.getElementById("answer-input").focus();
    startTime = new Date().getTime();
  }

  function submitAnswer() {
    const answerInput = document
      .getElementById("answer-input")
      .value.trim()
      .toLowerCase();
    const correctAnswer =
      selectedQuestions[currentQuestionIndex].answer.toLowerCase();
    const stage = selectedQuestions[currentQuestionIndex].stage;

    if (answerInput === correctAnswer) {
      score += stage;
    }

    const endTime = new Date().getTime();
    const reactionTime = (endTime - startTime) / 1000;
    reactionTimes.push(reactionTime);
    userAnswers.push({
      question: selectedQuestions[currentQuestionIndex].question,
      userAnswer: answerInput,
      correctAnswer,
    });

    currentQuestionIndex++;
    updateProgressBar();
    nextQuestion();
  }

  function updateProgressBar() {
    const progress =
      (currentQuestionIndex / selectedQuestions.length) * 100;
    document.getElementById("progress-bar").style.width = progress + "%";
  }

  function displayResults() {
    const meanReactionTime =
      reactionTimes.reduce((a, b) => a + b) / reactionTimes.length;
    const stdDeviation = Math.sqrt(
      reactionTimes
        .map((x) => Math.pow(x - meanReactionTime, 2))
        .reduce((a, b) => a + b) / reactionTimes.length
    );

    document.getElementById("test-container").style.display = "none";
    document.getElementById("progress-container").style.display="none";
    document.getElementById("progress-bar").style.display="none";
    document.getElementById("results").innerHTML = `
        <h2>Результаты</h2>
        <p>Точность: ${score}</p>
        <p>Среднее время реакции: ${meanReactionTime.toFixed(
          2
        )} секунды</p>
        <p>Стандартное отклонение времени реакции: ${stdDeviation.toFixed(
          2
        )}</p>
        <table>
            <tr><th>Вопрос</th><th>Ваш ответ</th><th>Правильный ответ</th></tr>
            ${userAnswers
              .map(
                (answer) => `
                <tr>
                    <td>${answer.question}</td>
                    <td>${answer.userAnswer}</td>
                    <td>${answer.correctAnswer}</td>
                </tr>
            `
              )
              .join("")}
        </table>
        <button id = "retry";>Пройти тест заново</button>
    `;
    document.getElementById("results").style.display = "block";
    document.getElementById("question-container").innerText = ""; // Clear the question container
    var stats = {
      accuracy: score, 
      reaction_time: meanReactionTime.toFixed(2),
      standard_devision: stdDeviation.toFixed(2),
    };
    var response = saveStats(stats, 16);
    console.log(response);
    var retryBtn = document.getElementById("retry");
    if (retryBtn != null) {
      retryBtn.addEventListener("click", retryTest);
    }
  }

  function retryTest() {
    document.getElementById("results").style.display = "none";
    document.getElementById("test-container").style.display = "none";
    document.getElementById("start-button").style.display = "inline";
    document.getElementById("progress-bar").style.width = "0%";
    score = 0;
    document.getElementById("question-container").innerText = ""; // Clear the question container
  }
  const startButton = document.getElementById("start-button");
  startButton.addEventListener("click", startTest);
  const subButton = document.getElementById("submit-button");
  subButton.addEventListener("click", submitAnswer);
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
});