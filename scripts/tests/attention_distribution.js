import {sendData} from '../../scripts/data_manager.js';

document.addEventListener("DOMContentLoaded", () => {


    const progressBarText = document.getElementById('progressBarText');
    const progress = document.getElementById('progress');
    const timer = document.getElementById('timer');
    const restartButton = document.getElementById('restartButton');
    var totalTime = 0;
    var startTime = 0;
    var mistakes = 0;
    var attentionTimes = {};

    const table = document.getElementById("attention-table");
    const instructions = document.getElementById("attention-instructions");
    const startButton = document.getElementById("attention-start-button");
    let currentStage = 0;

    var chosenButtons = [];

    const COLOR_PAIRS = [
        {base: "#FF5733", adjusted: "#FF6A4D"},
        {base: "#33FF57", adjusted: "#44de5f"},
        {base: "#3357FF", adjusted: "#4D6AFF"},
        {base: "#FF33A1", adjusted: "#d23f92"},
        {base: "#A133FF", adjusted: "#B34DFF"},
        {base: "#33FFA7", adjusted: "#42e09b"},
        {base: "#FFA733", adjusted: "#FFB34D"},
        {base: "#A7FF33", adjusted: "#a0e346"},
        {base: "#33A1FF", adjusted: "#4DB3FF"},
        {base: "#FF5733", adjusted: "#FF6A4D"}
    ];

    const LETTER_PAIRS = [
        {base: 'O', similar: 'Q'},
        {base: 'B', similar: '8'},
        {base: 'I', similar: '1'},
        {base: 'S', similar: '5'},
        {base: 'G', similar: '6'},
        {base: 'Z', similar: '2'},
        {base: 'M', similar: 'W'},
        {base: 'C', similar: 'G'},
        {base: 'K', similar: 'X'},
        {base: 'U', similar: 'V'},
        {base: 'D', similar: 'O'},
        {base: 'H', similar: 'N'},
        {base: 'A', similar: 'R'},
        {base: 'P', similar: 'R'},
        {base: 'Y', similar: 'V'}
    ];

    const SHAPE_SYMBOLS = ['▲', '▼', '◆', '●', '■', '★', '⬤', '⬥', '✦'];

    function getRandomElement(pairs) {
        const randomIndex = Math.floor(Math.random() * pairs.length);
        return pairs[randomIndex];
    }

    function generateStages() {
        chosenButtons = [];
        for (let i = 0; i < 15; i++) {
            const row = Math.floor(Math.random() * 10) + 1;
            const col = Math.floor(Math.random() * 10) + 1;
            chosenButtons.push({row, col});
        }
    }

    function generateTable() {
        if (currentStage <= 5) {
            var color_pair = getRandomElement(COLOR_PAIRS);
        } else if (currentStage <= 10) {
            var letter_pair = getRandomElement(LETTER_PAIRS);
        } else {
            var symbol = getRandomElement(SHAPE_SYMBOLS);
        }

        table.innerHTML = "";

        for (let i = 0; i < 10; i++) {
            const row = table.insertRow();
            for (let j = 0; j < 10; j++) {
                const cell = row.insertCell();
                const btn = document.createElement("button");
                btn.classList.add("attention-table-cell");

                if (currentStage <= 5) {
                    if (chosenButtons[currentStage].row === i + 1 && chosenButtons[currentStage].col === j + 1) {
                        btn.style.backgroundColor = color_pair.adjusted;
                    } else {
                        btn.style.backgroundColor = color_pair.base;
                    }
                } else if (currentStage <= 10) {
                    btn.style.fontSize = "24px";
                    if (chosenButtons[currentStage].row === i + 1 && chosenButtons[currentStage].col === j + 1) {
                        btn.textContent = letter_pair.similar;
                    } else {
                        btn.textContent = letter_pair.base;
                    }
                } else {
                    btn.textContent = symbol;
                    if (chosenButtons[currentStage].row === i + 1 && chosenButtons[currentStage].col === j + 1) {
                        btn.style.fontSize = "16px";
                    } else {
                        btn.style.fontSize = Math.floor(Math.random() * 8) + 20 + "px";
                    }
                }

                btn.addEventListener("click", () => handleCellClick(i + 1, j + 1));
                cell.appendChild(btn);
            }
        }
    }

    function handleCellClick(row, col) {
        if (chosenButtons[currentStage].row === row && chosenButtons[currentStage].col === col) {
            currentStage++;
            if (currentStage < chosenButtons.length) {
                setInstruction();
                generateTable();
                let attTime = new Date() - startTime;
                totalTime += attTime;
                attentionTimes[currentStage] = attTime;
                timer.innerHTML = `${attTime}ms`;
                startTime = new Date();
            } else {
                instructions.textContent = "Тест завершен!";
                table.style.display = "none";
                console.log(Object.values(attentionTimes));

                let attentionTime = (totalTime / 15).toFixed(2);
                let stdDeviation = calculateStandardDeviation(Object.values(attentionTimes)).toFixed(2);

                instructions.innerHTML = `Среднее время успешных попыток: ${attentionTime}ms<br>Стандартное отклонение: ${stdDeviation}<br>Ошибок: ${mistakes}`;

                var stats = {
                    attention_time: attentionTime,
                    standard_deviation: stdDeviation,
                    mistakes: mistakes
                }

                var response = saveStats(stats, 10);

                console.log(response);

            }
            progressBarText.textContent = `${currentStage}/15`;
            progress.style.width = `${Math.min(100, (currentStage / 15) * 100)}%`;
        } else {
            let str = "<br>Неверная ячейка, попробуйте ещё раз";
            mistakes++;
            if (!instructions.innerHTML.includes(str)) {
                instructions.innerHTML += str;
            }
        }
    }

    function setInstruction() {
        if (currentStage <= 5) {
            instructions.textContent = `Нажмите на ячейку с отличающимся от других цветом`;
        } else if (currentStage <= 10) {
            instructions.textContent = `Нажмите на ячейку с отличающейся буквы`;
        } else {
            instructions.textContent = `Нажмите на ячейку с минимальным размером фигуры`;
        }
    }

    startButton.addEventListener("click", startTest);

    function startTest() {
        currentStage = 0;
        progressBarText.textContent = `0/15`;
        progress.style.width = `0%`;
        timer.innerHTML = "0ms";
        totalTime = 0;
        mistakes = 0;
        attentionTimes = [];
        generateStages();
        console.log(chosenButtons);
        setInstruction();
        table.style.display = "table";
        startButton.style.display = "none";
        generateTable();
        startTime = new Date();
    }

    table.style.display = "none";

    function restartGame() {
        startTest();
    }

    restartButton.addEventListener('click', restartGame);


    function saveStats(stats, testId) {
        // отправка оценок на серв
        var formData = new FormData();
        formData.append('test_id', testId);
        formData.append('statistics', JSON.stringify(stats));
            // этот метод sendData есть на серваке, локально работать не будет
        var result = sendData(formData, '../../backend/requests/send_user_results.php');
        return result.response;
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
});
