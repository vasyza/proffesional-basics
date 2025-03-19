<?php
session_start();
require_once '../../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Тест реакции на цвета";
include_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест на сложную сенсомоторную реакцию на разные цвета</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Этот тест измеряет скорость вашей реакции на разные цвета. В зависимости от цвета
                        круга, вам нужно нажать соответствующую кнопку:</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите кнопку "Начать тест"</li>
                            <li>На экране будут появляться круги разных цветов</li>
                            <li>Если появится <span class="text-danger fw-bold">КРАСНЫЙ</span> круг - нажмите кнопку
                                "Красный"</li>
                            <li>Если появится <span class="text-success fw-bold">ЗЕЛЕНЫЙ</span> круг - нажмите кнопку
                                "Зеленый"</li>
                            <li>Если появится <span class="text-primary fw-bold">СИНИЙ</span> круг - нажмите кнопку
                                "Синий"</li>
                            <li>Тест включает 15 попыток</li>
                        </ol>
                    </div>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">Начать тест</button>
                    </div>

                    <div class="reaction-test-area mb-4">
                        <div id="stimulusArea" class="stimulus-area">
                            <div id="colorStimulus" class="color-stimulus"></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <button id="redButton" class="btn btn-danger btn-lg w-100" disabled>Красный</button>
                            </div>
                            <div class="col-4">
                                <button id="greenButton" class="btn btn-success btn-lg w-100" disabled>Зеленый</button>
                            </div>
                            <div class="col-4">
                                <button id="blueButton" class="btn btn-primary btn-lg w-100" disabled>Синий</button>
                            </div>
                        </div>
                    </div>

                    <div id="progressContainer" class="progress mb-3" style="display: none;">
                        <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>

                    <div id="resultsContainer" style="display: none;">
                        <h5>Результаты:</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Попытка</th>
                                    <th>Цвет</th>
                                    <th>Время реакции (мс)</th>
                                    <th>Правильно</th>
                                </tr>
                            </thead>
                            <tbody id="resultsTable">
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <strong>Среднее время реакции:</strong> <span id="averageTime">0</span> мс
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Точность:</strong> <span id="accuracy">0</span>%
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button id="saveResultsButton" class="btn btn-success">Сохранить результаты</button>
                            <a href="/tests/index.php" class="btn btn-outline-primary">Вернуться к списку тестов</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stimulus-area {
        width: 100%;
        height: 300px;
        background-color: #f8f9fa;
        border-radius: 8px;
        position: relative;
        margin-bottom: 15px;
    }

    .color-stimulus {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startButton = document.getElementById('startButton');
        const redButton = document.getElementById('redButton');
        const greenButton = document.getElementById('greenButton');
        const blueButton = document.getElementById('blueButton');
        const colorStimulus = document.getElementById('colorStimulus');
        const progressBar = document.getElementById('progressBar');
        const progressContainer = document.getElementById('progressContainer');
        const resultsContainer = document.getElementById('resultsContainer');
        const resultsTable = document.getElementById('resultsTable');
        const averageTime = document.getElementById('averageTime');
        const accuracy = document.getElementById('accuracy');
        const saveResultsButton = document.getElementById('saveResultsButton');

        const colors = [
            { name: 'red', hex: '#ff0000', button: redButton },
            { name: 'green', hex: '#00aa00', button: greenButton },
            { name: 'blue', hex: '#0000ff', button: blueButton }
        ];

        let currentTrial = 0;
        const totalTrials = 15;
        let startTime;
        let results = [];
        let testInProgress = false;
        let timeoutId;
        let currentColor;

        startButton.addEventListener('click', startTest);
        redButton.addEventListener('click', () => handleColorClick('red'));
        greenButton.addEventListener('click', () => handleColorClick('green'));
        blueButton.addEventListener('click', () => handleColorClick('blue'));
        saveResultsButton.addEventListener('click', saveResults);

        function startTest() {
            startButton.style.display = 'none';
            redButton.disabled = false;
            greenButton.disabled = false;
            blueButton.disabled = false;
            progressContainer.style.display = 'block';
            results = [];
            currentTrial = 0;
            testInProgress = true;

            nextTrial();
        }

        function nextTrial() {
            if (currentTrial >= totalTrials) {
                endTest();
                return;
            }

            colorStimulus.style.display = 'none';

            // Случайная задержка от 1 до 3 секунд
            const delay = Math.floor(Math.random() * 2000) + 1000;

            timeoutId = setTimeout(() => {
                if (!testInProgress) return;

                // Выбор случайного цвета
                currentColor = colors[Math.floor(Math.random() * colors.length)];
                colorStimulus.style.backgroundColor = currentColor.hex;

                // Показать стимул
                colorStimulus.style.display = 'block';
                startTime = Date.now();
            }, delay);
        }

        function handleColorClick(color) {
            if (colorStimulus.style.display === 'none') {
                // Преждевременная реакция
                results.push({
                    trial: currentTrial + 1,
                    color: 'none',
                    response: color,
                    time: -1,
                    correct: false
                });

                clearTimeout(timeoutId);
                currentTrial++;
                updateProgress();
                nextTrial();
            } else {
                // Реакция на стимул
                const endTime = Date.now();
                const reactionTime = endTime - startTime;
                const isCorrect = color === currentColor.name;

                results.push({
                    trial: currentTrial + 1,
                    color: currentColor.name,
                    response: color,
                    time: reactionTime,
                    correct: isCorrect
                });

                currentTrial++;
                updateProgress();
                nextTrial();
            }
        }

        function updateProgress() {
            const progress = (currentTrial / totalTrials) * 100;
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);
        }

        function endTest() {
            testInProgress = false;
            redButton.disabled = true;
            greenButton.disabled = true;
            blueButton.disabled = true;

            // Вычисление среднего времени реакции (только для корректных ответов)
            const correctResults = results.filter(r => r.correct && r.time > 0);
            let totalTime = 0;
            let correctCount = correctResults.length;

            correctResults.forEach(result => {
                totalTime += result.time;
            });

            const avgTime = correctCount > 0 ? (totalTime / correctCount).toFixed(1) : "N/A";
            averageTime.textContent = avgTime;

            // Вычисление точности (исключая преждевременные реакции)
            const validResults = results.filter(r => r.time > 0);
            const accuracyValue = validResults.length > 0
                ? ((correctResults.length / validResults.length) * 100).toFixed(1)
                : "0";
            accuracy.textContent = accuracyValue;

            // Заполнение таблицы результатов
            resultsTable.innerHTML = '';
            results.forEach(result => {
                const row = document.createElement('tr');
                const trialCell = document.createElement('td');
                const colorCell = document.createElement('td');
                const timeCell = document.createElement('td');
                const correctCell = document.createElement('td');

                trialCell.textContent = result.trial;

                if (result.time < 0) {
                    colorCell.textContent = 'N/A';
                    timeCell.textContent = 'Преждевременная реакция';
                    correctCell.textContent = 'Нет';
                    row.classList.add('table-danger');
                } else {
                    // Отображение цвета
                    const colorSpan = document.createElement('span');
                    colorSpan.textContent = getColorName(result.color);
                    colorSpan.style.color = getColorHex(result.color);
                    colorSpan.style.fontWeight = 'bold';
                    colorCell.appendChild(colorSpan);

                    timeCell.textContent = `${result.time} мс`;
                    correctCell.textContent = result.correct ? 'Да' : 'Нет';

                    if (!result.correct) {
                        row.classList.add('table-warning');
                    }
                }

                row.appendChild(trialCell);
                row.appendChild(colorCell);
                row.appendChild(timeCell);
                row.appendChild(correctCell);
                resultsTable.appendChild(row);
            });

            resultsContainer.style.display = 'block';
        }

        function getColorName(color) {
            switch (color) {
                case 'red': return 'Красный';
                case 'green': return 'Зеленый';
                case 'blue': return 'Синий';
                default: return color;
            }
        }

        function getColorHex(color) {
            switch (color) {
                case 'red': return '#ff0000';
                case 'green': return '#00aa00';
                case 'blue': return '#0000ff';
                default: return '#000000';
            }
        }

        function saveResults() {
            const testData = {
                test_type: 'color_reaction',
                results: results,
                average_time: parseFloat(averageTime.textContent),
                accuracy: parseFloat(accuracy.textContent)
            };

            fetch('/api/save_test_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(testData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Результаты успешно сохранены!');
                        saveResultsButton.disabled = true;
                        saveResultsButton.textContent = 'Результаты сохранены';
                    } else {
                        alert('Ошибка при сохранении результатов: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при сохранении результатов');
                });
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>