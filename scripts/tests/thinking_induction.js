import { sendData } from "../../scripts/data_manager.js";

document.addEventListener("DOMContentLoaded", () => {
  const stageWords = [
    ["собака", "человек", "кот", "мышь", "стол", "вилка"],
    ["телефон", "розетка", "ковёр", "ноутбук", "наушники", "батарейка"],
    ["нейросеть", "клавиатура", "турник", "велосипед", "пылесос", "гидрант"],
  ];

  let currentStage = 1;
  let wordsToGuess = [];
  let currentWordIndex = 0;
  let currentWord = "";
  let attempts = [];
  let reactionTimes = [];
  let startTime;
  let guessCount = 0;

  function startTest() {
    document.getElementById("start-button").style.display = "none";
    document.getElementById("instructions").innerText = "";
    currentStage = 1;
    wordsToGuess = [];
    attempts = [];
    reactionTimes = [];
    currentWordIndex = 0;
    guessCount = 0;
    selectWords();
    nextWord();
  }

  function selectWords() {
    for (let stage = 0; stage < 3; stage++) {
      const shuffled = stageWords[stage].sort(() => 0.5 - Math.random());
      wordsToGuess.push(...shuffled.slice(0, 3));
    }
  }

  function nextWord() {
    if (currentWordIndex >= wordsToGuess.length) {
      displayResults();
      return;
    }
    currentWord = wordsToGuess[currentWordIndex];
    document.getElementById("grid").innerHTML = "";
    document.getElementById(
      "grid"
    ).style.gridTemplateColumns = `repeat(${currentWord.length}, 50px)`;

    for (let i = 0; i < 7; i++) {
      for (let j = 0; j < currentWord.length; j++) {
        let cell = document.createElement("div");
        cell.classList.add("cell");
        cell.setAttribute("id", `cell-${i}-${j}`);
        document.getElementById("grid").appendChild(cell);
      }
    }
    document.getElementById("word-input").value = "";
    document.getElementById("word-input").disabled = false;
    document.getElementById("word-input").maxLength = currentWord.length;
    document.getElementById("wordle-container").style.display = "block";
    startTime = new Date().getTime();
    guessCount = 0;
    document.getElementById("instructions").innerText = `Этап ${
      Math.floor(currentWordIndex / 3) + 1
    }, слово ${(currentWordIndex % 3) + 1}`;
  }

  function submitGuess() {
    const guess = document.getElementById("word-input").value.toLowerCase();
    if (!guess || guess.length !== currentWord.length) {
      alert("Введите слово правильной длины");
      return;
    }
    guessCount++;
    for (let i = 0; i < guess.length; i++) {
      let cell = document.getElementById(`cell-${guessCount - 1}-${i}`);
      cell.innerText = guess[i];
      if (guess[i] === currentWord[i]) {
        cell.classList.add("correct");
      } else if (currentWord.includes(guess[i])) {
        cell.classList.add("present");
      } else {
        cell.classList.add("absent");
      }
    }
    document.getElementById("word-input").value = "";
    if (guess === currentWord) {
      let endTime = new Date().getTime();
      reactionTimes.push((endTime - startTime) / 1000);
      attempts.push(guessCount);
      currentWordIndex++;
      setTimeout(nextWord, 1000);
    } else if (guessCount >= 7) {
      alert(
        `Вы не угадали слово "${currentWord}" за 7 попыток. Следующее слово.`
      );
      currentWordIndex++;
      setTimeout(nextWord, 1000);
    }
  }

  function displayResults() {
    let meanAttempts = attempts.reduce((a, b) => a + b, 0) / attempts.length;
    let meanReactionTime =
      reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length;
    let stdDeviationAttempts = Math.sqrt(
      attempts
        .map((x) => Math.pow(x - meanAttempts, 2))
        .reduce((a, b) => a + b) / attempts.length
    );

    document.getElementById("test-container").style.display = "none";
    document.getElementById("results").innerHTML = `
        <h2>Результаты</h2>
        <p>Среднее количество попыток: ${meanAttempts.toFixed(2)}</p>
        <p>Стандартное отклонение количества попыток: ${stdDeviationAttempts.toFixed(
          2
        )}</p>
        <p>Среднее время реакции: ${meanReactionTime.toFixed(2)} секунды</p>
        <button id="retry">Пройти тест заново</button>
    `;

    var stats = {
      attempts: meanAttempts.toFixed(2),
      standard_devision_attempts: stdDeviationAttempts.toFixed(2),
      reaction_time: meanReactionTime.toFixed(2)
    };

    // id теста смотрим на гитхабе, где находятся тз, в разделе инфы "какие идшники у тестов"
    var response = saveStats(stats, 15);
    console.log(response);
    
    var retryBtn = document.getElementById("retry");
    if (retryBtn != null) {
        retryBtn.addEventListener('click', retryTest);
    }
  }

  function retryTest() {
    document.getElementById("results").innerHTML = "";
    document.getElementById("test-container").style.display = "block";
    document.getElementById("start-button").style.display = "inline";
    document.getElementById("wordle-container").style.display = "none";
    document.getElementById("instructions").innerText =
      'Нажмите "Начать тест", чтобы начать.';
  }

  const guessButton = document.getElementById("guess");
  guessButton.addEventListener("click", submitGuess);

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
});
