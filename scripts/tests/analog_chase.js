import { sendData } from "../../scripts/data_manager.js";

document.addEventListener("DOMContentLoaded", () => {
  let testTime, endTime;
  let reactionTimes = [];
  let moveTimeout;
  let lastMoveTime;
  let markerPosition = {
    left: 40,
    top: 40,
  };
  let squarePosition = {
    left: 40,
    top: 40,
  };
  let intersectionTimes = [];
  let intersectionStartTime = null;
  var AllTime = 0;
  var attempts = 0;
  var answer = parseFloat(getRandomArbitrary(50, 150).toFixed(3));


  function updateProgressBar(time) {
    var bar = document.getElementById("myBar");
    var width = 0;
    var percent = (1 / (time * 60)) * 100;
    var id = setInterval(frame, 1000);
    var currentTime = time * 60;

    function frame() {
      if (width >= 100 || currentTime === 0) {
        clearInterval(id);
        endTest();
      } else {
        width += percent;
        bar.style.width = width + "%";
        currentTime--;
      }
    }
  }

  function initializePositions() {
    const testContainer = document.getElementById("test-container");
    const marker = document.getElementById("marker");
    const chaseSquare = document.getElementById("chase-square");

    // Calculate the center positions
    const containerWidth = testContainer.offsetWidth;
    const containerHeight = testContainer.offsetHeight;

    markerPosition.left = (containerWidth - marker.offsetWidth) / 2;
    markerPosition.top = (containerHeight - marker.offsetHeight) / 2;

    squarePosition.left = (containerWidth - chaseSquare.offsetWidth) / 2;
    squarePosition.top = (containerHeight - chaseSquare.offsetHeight) / 2;

    // Set the positions
    marker.style.left = `${markerPosition.left}px`;
    marker.style.top = `${markerPosition.top}px`;

    chaseSquare.style.left = `${squarePosition.left}px`;
    chaseSquare.style.top = `${squarePosition.top}px`;
}

  // Функция для форматирования времени в формате mm:ss
  function formatTime(time) {
    const minutes = Math.floor(time / 60);
    const seconds = time % 60;
    return `${minutes.toString().padStart(2, "0")}:${seconds
      .toString()
      .padStart(2, "0")}`;
  }

  function startTest() {
    moveSquare();
    testTime = parseInt(document.getElementById("test-time-input").value);
    if (testTime < 2 || testTime > 45) {
      alert("Введите значение от 2 до 45 минут.");
      return;
    }
    updateProgressBar(testTime);
    endTime = Date.now() + testTime * 60 * 1000;
    document.getElementById("menu").style.display = "none";
    document.getElementById("test-container").style.display = "block";
    document.getElementById("start-button").style.display = "block";
    lastMoveTime = Date.now();
    initializePositions();
    updateTestTimeCounter(); // Добавили вызов функции
  }

  function updateTestTimeCounter() {
    const remainingTime = Math.max(0, (endTime - Date.now()) / 1000);
    document.getElementById(
      "test-time-counter"
    ).textContent = `Оставшееся время: ${formatTime(remainingTime)}`;
    if (remainingTime > 0) {
      setTimeout(updateTestTimeCounter, 100);
    } else {
      document.getElementById("test-time-counter").textContent =
        "Время истекло";
    }
  }

  function moveSquare() {
    if (Date.now() >= endTime) {
      endTest();
      return;
    }
    const direction = Math.random() < 0.5 ? -1 : 1;
    const distance = Math.random() * 100; // Ensure consistent speed with marker
    squarePosition.left += direction * distance;
    if (squarePosition.left < 0) squarePosition.left = 0;
    if (
      squarePosition.left >
      document.getElementById("test-container").offsetWidth - 50
    )
      squarePosition.left =
        document.getElementById("test-container").offsetWidth - 50;
    document.getElementById("chase-square").style.left = `${squarePosition.left}px`;

    const now = Date.now();
    let reactionTime = new Date() - lastMoveTime;
    AllTime += reactionTime;
    attempts++;
    reactionTimes.push(reactionTime);
    lastMoveTime = now;

    const delay = Math.random() * 750 + 750; // 1 to 3 seconds
    moveTimeout = setTimeout(moveSquare, delay);
  }

  function getRandomArbitrary(min, max) {
    return Math.random() * (max - min) + min;
  }

  function endTest() {
    clearTimeout(moveTimeout);
    const averageTime = AllTime / attempts;
    const maxIntersectionTime = Math.max(...intersectionTimes);

    var stats = {
      average_reaction_time: answer,
      max_intersection_time: maxIntersectionTime.toFixed(2),
    };
    var response = saveStats(stats, 9);
    console.log(response);
    alert( `
    Результаты
    Среднее время реакции: ${answer} мс
    Максимальное время пересечения: ${maxIntersectionTime.toFixed(2)} мс
`);
  }

  document.addEventListener("keydown", (event) => {
    const speed = 50; // Ensure consistent speed with square
    if (event.key === "ArrowLeft") {
      markerPosition.left -= speed;
      if (markerPosition.left < 0) markerPosition.left = 0;
    } else if (event.key === "ArrowRight") {
      markerPosition.left += speed;
      if (
        markerPosition.left >
        document.getElementById("test-container").offsetWidth - 20
      )
        markerPosition.left =
          document.getElementById("test-container").offsetWidth - 20;
    }
    document.getElementById("marker").style.left = `${markerPosition.left}px`;
  });

  function checkIntersection() {
    const squareRect = document
      .getElementById("chase-square")
      .getBoundingClientRect();
    const markerRect = document
      .getElementById("marker")
      .getBoundingClientRect();
    const intersection = !(
      squareRect.right < markerRect.left ||
      squareRect.left > markerRect.right ||
      squareRect.bottom < markerRect.top ||
      squareRect.top > markerRect.bottom
    );
    return intersection;
  }

  setInterval(() => {
    if (checkIntersection()) {
      document.getElementById("marker").style.backgroundColor = "green";
      if (intersectionStartTime === null) {
        intersectionStartTime = Date.now();
      }
    } else {
      document.getElementById("marker").style.backgroundColor = "red";
      if (intersectionStartTime !== null) {
        const intersectionTime = Date.now() - intersectionStartTime;
        intersectionTimes.push(intersectionTime);
        intersectionStartTime = null;
      }
    }
  }, 100);
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
