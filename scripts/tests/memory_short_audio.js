import { sendData } from "../../scripts/data_manager.js";
document.addEventListener("DOMContentLoaded", () => {
  const notes = [261.63, 293.66, 329.63, 349.23, 392.0]; // C4, D4, E4, F4, G4 frequencies
  let currentStage = 1;
  let accuracy = 0;
  let reactionTimes = [];
  let startTime;
  let noteFrequencies = [];
  let correctAnswer;
  let condition;

  function playSound(frequency) {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime);
    oscillator.connect(audioCtx.destination);
    oscillator.start();
    oscillator.stop(audioCtx.currentTime + 1);
  }

  function startTest() {
    document.getElementById("start-button").style.display = "none";
    document.getElementById("instructions").innerText = "";
    document.getElementById("progress-container").style.display = "block";
    updateProgressBar();
    currentStage = 1;
    accuracy = 0;
    reactionTimes = [];
    nextStage();
  }

  function nextStage() {
    document.getElementById("instructions").innerText = "";
    document.getElementById("buttons-container").innerHTML = "";
    noteFrequencies = [];
    let numNotes = currentStage + 2; // 3 notes for stage 1, 4 for stage 2, 5 for stage 3
    condition = Math.random() < 0.5 ? "самую низкую" : "самую высокую";

    let availableNotes = [...notes];
    for (let i = 0; i < numNotes; i++) {
      let index = Math.floor(Math.random() * availableNotes.length);
      let freq = availableNotes.splice(index, 1)[0];
      noteFrequencies.push(freq);
      setTimeout(() => {
        playSound(freq);
        addButton(i + 1);
      }, i * 1500);
    }

    correctAnswer =
      condition === "самую низкую"
        ? Math.min(...noteFrequencies)
        : Math.max(...noteFrequencies);

    // Enable buttons and display condition text after all notes have been played
    setTimeout(() => {
      document.getElementById(
        "instructions"
      ).innerText = `Выберите ${condition} ноту.`;
      enableButtons();
      startTime = new Date().getTime(); // Start timing when input is enabled
    }, numNotes * 1500);
  }

  function addButton(id) {
    const button = document.createElement("button");
    button.innerHTML = `Нота ${id}`;
    button.onclick = () => checkAnswer(noteFrequencies[id - 1]);
    button.disabled = true;
    document.getElementById("buttons-container").appendChild(button);
  }

  function enableButtons() {
    const buttons = document
      .getElementById("buttons-container")
      .getElementsByTagName("button");
    for (let button of buttons) {
      button.disabled = false;
    }
  }

  function checkAnswer(selectedNote) {
    let endTime = new Date().getTime();
    let reactionTime = (endTime - startTime) / 1000;
    reactionTimes.push(reactionTime);
    if (selectedNote === correctAnswer) {
      accuracy += currentStage;
    }
    console.log(accuracy, currentStage);
    if (currentStage < 3) {
      currentStage++;
      updateProgressBar();
      nextStage();
    } else {
      displayResults();
    }
  }

  function updateProgressBar() {
    const progressBar = document.getElementById("progress-bar");
    progressBar.style.width = `${(currentStage / 3) * 100}%`;
  }

  function displayResults() {
    document.getElementById("progress-container").style.display = "none";
    let mean = reactionTimes.reduce((a, b) => a + b) / reactionTimes.length;
    let stdDeviation = Math.sqrt(
      reactionTimes.map((x) => Math.pow(x - mean, 2)).reduce((a, b) => a + b) /
        reactionTimes.length
    );
    document.getElementById("test-container").style.display = "none";
    document.getElementById("results").innerHTML = `
                <h2>Результаты</h2>
                <p>Точность: ${(accuracy / 6 * 100).toFixed(3)}</p>
                <p>Среднее время реакции: ${mean.toFixed(2)} секунды</p>
                <p>Стандартное отклонение времени реакции: ${stdDeviation.toFixed(
                  2
                )}</п>
                <button id="retry">Пройти тест заново</button>
            `;
    var stats = {
      accuracy: (accuracy / 6 * 100).toFixed(3),
      reaction_time: mean.toFixed(2),
      standard_deviation: stdDeviation.toFixed(2),
    };
    var response = saveStats(stats, 12);
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
    document.getElementById("buttons-container").innerHTML = "";
    document.getElementById("instructions").innerText =
      'Нажмите "Начать тест", чтобы начать.';
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
