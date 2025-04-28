import { sendData } from "../../scripts/data_manager.js";
document.addEventListener("DOMContentLoaded", () => {
  const rounds = [
    { level: "легкий", cups: 3, speed: 1000 },
    { level: "легкий", cups: 3, speed: 1000 },
    { level: "легкий", cups: 3, speed: 1000 },
    { level: "легкий", cups: 3, speed: 1000 },
    { level: "средний", cups: 4, speed: 750 },
    { level: "средний", cups: 4, speed: 750 },
    { level: "средний", cups: 4, speed: 750 },
    { level: "средний", cups: 4, speed: 750 },
    { level: "тяжелый", cups: 5, speed: 500 },
    { level: "тяжелый", cups: 5, speed: 500 },
    { level: "тяжелый", cups: 5, speed: 500 },
    { level: "тяжелый", cups: 5, speed: 500 },
  ];

  let currentRound = 0;
  let accuracy = 0;
  let reactionTimes = [];
  let startTime;
  let circles = [];
  let correctCircle;
  let userSelectedCircle;

  function startTest() {
    document.getElementById("start-button").style.display = "none";
    document.getElementById("instructions").innerText = "";
    document.getElementById("score-container").style.display = "block";
    document.getElementById("progress-container").style.display = "block";
    currentRound = 0;
    accuracy = 0;
    reactionTimes = [];
    updateScore();
    nextRound();
  }

  function nextRound() {
    if (currentRound >= rounds.length) {
      displayResults();
      return;
    }
    let round = rounds[currentRound];
    updateProgressBar();
    document.getElementById("instructions").innerText = `Раунд ${
      currentRound + 1
    }: Выберите кружочек.`;
    createGrid(round.cups);
    correctCircle = Math.floor(Math.random() * round.cups);
    for (let i = 0; i < round.cups; i++) {
      let circle = document.createElement("div");
      circle.classList.add("circle");
      circle.id = `circle-${i}`;
      document.getElementById("grid-container").appendChild(circle);
      circles.push(circle);
      circle.onclick = () => selectInitialCircle(i);
    }
    arrangeCirclesInRow(round.cups);
  }

  function createGrid(cups) {
    let gridSize = cups === 3 ? 3 : cups === 4 ? 4 : 5;
    document.getElementById(
      "grid-container"
    ).style.gridTemplateColumns = `repeat(${gridSize}, 100px)`;
    document.getElementById(
      "grid-container"
    ).style.gridTemplateRows = `repeat(${gridSize}, 100px)`;
    document.getElementById("grid-container").innerHTML = "";
  }

  function arrangeCirclesInRow(cups) {
    for (let i = 0; i < cups; i++) {
      circles[i].style.transform = `translate(${i * 110}px, 110px)`;
      circles[i].style.pointerEvents = "auto"; // Enable clicking for initial selection
    }
  }

  function selectInitialCircle(index) {
    userSelectedCircle = index;
    document.getElementById("instructions").innerText =
      "Следите за движением кружочков.";
    for (let circle of circles) {
      circle.style.pointerEvents = "none"; // Disable clicking during animation
    }
    moveCircles(rounds[currentRound].speed, rounds[currentRound].cups);
  }

  function moveCircles(speed, cups) {
    let gridSize = cups === 3 ? 3 : cups === 4 ? 4 : 5;
    let positions = [];
    for (let i = 0; i < gridSize; i++) {
      for (let j = 0; j < gridSize; j++) {
        positions.push({ x: i, y: j });
      }
    }
    let moveInterval = setInterval(() => {
      for (let i = 0; i < cups; i++) {
        let posIndex = Math.floor(Math.random() * positions.length);
        let pos = positions.splice(posIndex, 1)[0];
        circles[i].style.transform = `translate(${pos.y * 110}px, ${
          pos.x * 110
        }px)`;
      }
      positions = [];
      for (let i = 0; i < gridSize; i++) {
        for (let j = 0; j < gridSize; j++) {
          positions.push({ x: i, y: j });
        }
      }
    }, speed);
    setTimeout(() => {
      clearInterval(moveInterval);
      for (let circle of circles) {
        circle.style.pointerEvents = "auto"; // Enable clicking after animation
      }
      startTime = new Date().getTime();
      document.getElementById("instructions").innerText =
        "Выберите тот же кружочек.";
      circles.forEach((circle, index) => {
        circle.onclick = () => selectCircle(index);
      });
    }, 5000);
  }

  function selectCircle(index) {
    let endTime = new Date().getTime();
    let reactionTime = (endTime - startTime) / 1000;
    reactionTimes.push(reactionTime);
    let round = rounds[currentRound];
    if (index === userSelectedCircle) {
      accuracy +=
        round.level === "легкий" ? 1 : round.level === "средний" ? 2 : 3;
      updateScore();
    }
    currentRound++;
    updateProgressBar();
    circles = [];
    setTimeout(() => {
      if (currentRound < rounds.length) {
        nextRound();
      } else {
        displayResults();
      }
    }, 1000);
  }

  function updateProgressBar() {
    let progressBar = document.getElementById("progress-bar");
    let progress = (currentRound / rounds.length) * 100;
    progressBar.style.width = progress + "%";
  }

  function updateScore() {
    document.getElementById("score").innerText = accuracy;
  }

  function displayResults() {
    let meanReactionTime =
      reactionTimes.reduce((a, b) => a + b) / reactionTimes.length;
    let stdDeviation = Math.sqrt(
      reactionTimes
        .map((x) => Math.pow(x - meanReactionTime, 2))
        .reduce((a, b) => a + b) / reactionTimes.length
    );
    document.getElementById("test-container").style.display = "none";
    document.getElementById("progress-container").style.display = "none";
    document.getElementById("score-container").style.display = "none";
    document.getElementById("results").innerHTML = `
    <h2>Результаты</h2>
    <p>Точность: ${accuracy}</p>
    <p>Среднее время реакции: ${meanReactionTime.toFixed(2)} секунды</p>
    <p>Стандартное отклонение времени реакции: ${stdDeviation.toFixed(2)}</p>
    <button id="retry">Пройти тест заново</button>
`;
    var stats = {
      accuracy: accuracy,
      reaction_time: meanReactionTime.toFixed(2),
      standard_deviation: stdDeviation.toFixed(2),
    };
    var response = saveStats(stats, 11);
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
    document.getElementById("grid-container").innerHTML = "";
    document.getElementById("instructions").innerText =
      'Нажмите "Начать тест", чтобы начать.';
    document.getElementById("progress-bar").style.width = "0%";
    accuracy = 0;
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
});
