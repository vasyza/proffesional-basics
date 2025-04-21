<?php
session_start();
require_once '../../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Тест реакции на визуальную арифметику";
include_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест на сложную сенсомоторную реакцию: определение четности (визуально)</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Этот тест измеряет скорость вашей реакции на визуальный арифметический стимул. На
                        экране будут появляться числа, и вы должны определить, четное оно или нечетное.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите кнопку "Начать тест"</li>
                            <li>На экране будут появляться числа</li>
                            <li>Если число <strong>ЧЕТНОЕ</strong> - нажмите кнопку "Четное"</li>
                            <li>Если число <strong>НЕЧЕТНОЕ</strong> - нажмите кнопку "Нечетное"</li>
                            <li>Тест включает 15 попыток</li>
                        </ol>
                    </div>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">Начать тест</button>
                    </div>

                    <div class="reaction-test-area mb-4">
                        <div id="stimulusArea" class="stimulus-area d-flex align-items-center justify-content-center">
                            <div id="numberDisplay" class="number-display" style="display: none;"></div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button id="evenButton" class="btn btn-success btn-lg w-100" disabled>Четное</button>
                            </div>
                            <div class="col-6">
                                <button id="oddButton" class="btn btn-danger btn-lg w-100" disabled>Нечетное</button>
                            </div>
                        </div>
                    </div>

                    <div id="progressContainer" class="mb-3"></div>

                    <div id="resultsContainer" style="display: none;">
                        <h5>Результаты:</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Попытка</th>
                                    <th>Число</th>
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

    .number-display {
        font-size: 6rem;
        font-weight: bold;
        color: #333;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startButton = document.getElementById('startButton');
        const evenButton = document.getElementById('evenButton');
        const oddButton = document.getElementById('oddButton');
        const numberDisplay = document.getElementById('numberDisplay');
        const resultsContainer = document.getElementById('resultsContainer');
        const resultsTable = document.getElementById('resultsTable');
        const averageTime = document.getElementById('averageTime');
        const accuracy = document.getElementById('accuracy');
        const saveResultsButton = document.getElementById('saveResultsButton');

        let currentTrial = 0;
        const totalTrials = 15;
        let startTime;
        let results = [];
        let testInProgress = false;
        let timeoutId;
        let currentNumber;

        // Initialize our new progress bar
        const progressBar = TestProgress.initTrialProgressBar('progressContainer', totalTrials);

        startButton.addEventListener('click', startTest);
        evenButton.addEventListener('click', () => handleResponse('even'));
        oddButton.addEventListener('click', () => handleResponse('odd'));
        saveResultsButton.addEventListener('click', saveResults);

        function startTest() {
            startButton.style.display = 'none';
            evenButton.disabled = false;
            oddButton.disabled = false;
            progressBar.setVisible(true);
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

            numberDisplay.style.display = 'none';

            // Случайная задержка от 1 до 3 секунд
            const delay = Math.floor(Math.random() * 2000) + 1000;

            timeoutId = setTimeout(() => {
                if (!testInProgress) return;

                // Генерация случайного числа от 10 до 999
                // Используем больший диапазон для повышения сложности
                currentNumber = Math.floor(Math.random() * 990) + 10;

                // Отображение числа
                numberDisplay.textContent = currentNumber;
                numberDisplay.style.display = 'block';

                startTime = Date.now();
            }, delay);
        }

        function handleResponse(response) {
            if (numberDisplay.style.display === 'none') {
                // Преждевременная реакция
                clearTimeout(timeoutId);
                results.push({
                    trial: currentTrial + 1,
                    number: null,
                    response: response,
                    time: -1,
                    correct: false
                });

                currentTrial++;
                progressBar.updateTrial(currentTrial);
                nextTrial();
            } else {
                // Правильная реакция
                const endTime = Date.now();
                const reactionTime = endTime - startTime;

                // Проверяем правильность ответа
                const isEven = currentNumber % 2 === 0;
                const isCorrect = (response === 'even' && isEven) || (response === 'odd' && !isEven);

                results.push({
                    trial: currentTrial + 1,
                    number: currentNumber,
                    response: response,
                    time: reactionTime,
                    correct: isCorrect
                });

                currentTrial++;
                progressBar.updateTrial(currentTrial);
                nextTrial();
            }
        }

        function endTest() {
            testInProgress = false;
            evenButton.disabled = true;
            oddButton.disabled = true;

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
                const numberCell = document.createElement('td');
                const timeCell = document.createElement('td');
                const correctCell = document.createElement('td');

                trialCell.textContent = result.trial;

                if (result.time < 0) {
                    numberCell.textContent = 'N/A';
                    timeCell.textContent = 'Преждевременная реакция';
                    correctCell.textContent = 'Нет';
                    row.classList.add('table-danger');
                } else {
                    numberCell.textContent = result.number;
                    timeCell.textContent = `${result.time} мс`;
                    correctCell.textContent = result.correct ? 'Да' : 'Нет';

                    if (!result.correct) {
                        row.classList.add('table-warning');
                    }
                }

                row.appendChild(trialCell);
                row.appendChild(numberCell);
                row.appendChild(timeCell);
                row.appendChild(correctCell);
                resultsTable.appendChild(row);
            });

            resultsContainer.style.display = 'block';
        }

        function saveResults() {
            const testData = {
                test_type: 'visual_arithmetic',
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