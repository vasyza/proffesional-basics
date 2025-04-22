<?php
session_start();
require_once '../../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Тест реакции на звуковой сигнал с арифметикой";
include_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест на сложную сенсомоторную реакцию: сложение в уме (звуковой сигнал)</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Этот тест измеряет скорость вашей реакции на <strong>звуковой арифметический стимул</strong>. Вы
                        <strong>услышите</strong> два числа, которые нужно сложить в уме и определить, четная или нечетная
                        получившаяся сумма. Тест рассчитан на аудиальное восприятие информации без визуальной подсказки.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Убедитесь, что звук на вашем устройстве включен</li>
                            <li>Нажмите кнопку "Начать тест"</li>
                            <li>Вы <strong>услышите</strong> два числа, произнесенных голосом</li>
                            <li>Сложите эти числа в уме</li>
                            <li>Если полученная сумма <strong>ЧЕТНАЯ</strong> - нажмите кнопку "Четное"</li>
                            <li>Если полученная сумма <strong>НЕЧЕТНАЯ</strong> - нажмите кнопку "Нечетное"</li>
                            <li>Тест включает 10 попыток</li>
                        </ol>
                    </div>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">Начать тест</button>
                    </div>

                    <div class="reaction-test-area mb-4">
                        <div id="stimulusArea" class="stimulus-area d-flex align-items-center justify-content-center">
                            <div id="numberDisplayWrapper" style="display: none;">
                                <i class="fas fa-volume-up fa-3x text-primary mb-3"></i>
                                <div id="audioInProgressText" class="audio-status">Слушайте внимательно...</div>
                            </div>
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
                                    <th>Числа</th>
                                    <th>Сумма</th>
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
        font-size: 3rem;
        font-weight: bold;
        margin-top: 10px;
        text-align: center;
    }

    .audio-status {
        font-size: 1.2rem;
        margin-top: 10px;
        color: #6c757d;
        text-align: center;
    }
</style>

<!-- Полифилл для поддержки синтеза речи в браузерах -->
<script src="https://code.responsivevoice.org/responsivevoice.js?key=YOUR_API_KEY"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startButton = document.getElementById('startButton');
        const evenButton = document.getElementById('evenButton');
        const oddButton = document.getElementById('oddButton');
        const numberDisplayWrapper = document.getElementById('numberDisplayWrapper');
        const audioInProgressText = document.getElementById('audioInProgressText');
        const resultsContainer = document.getElementById('resultsContainer');
        const resultsTable = document.getElementById('resultsTable');
        const averageTime = document.getElementById('averageTime');
        const accuracy = document.getElementById('accuracy');
        const saveResultsButton = document.getElementById('saveResultsButton');

        let currentTrial = 0;
        const totalTrials = 10;
        let startTime;
        let results = [];
        let testInProgress = false;
        let timeoutId;
        let currentNumber1;
        let currentNumber2;
        let isSpeaking = false;

        // Initialize our new progress bar
        const progressBar = TestProgress.initTrialProgressBar('progressContainer', totalTrials);

        // Проверка поддержки синтеза речи
        const speechSupported = 'speechSynthesis' in window || 'responsiveVoice' in window;

        startButton.addEventListener('click', startTest);
        evenButton.addEventListener('click', () => handleResponse('even'));
        oddButton.addEventListener('click', () => handleResponse('odd'));
        saveResultsButton.addEventListener('click', saveResults);

        function startTest() {
            if (!speechSupported) {
                alert('Ваш браузер не поддерживает синтез речи. Пожалуйста, используйте современный браузер.');
                return;
            }

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

            numberDisplayWrapper.style.display = 'none';

            // Случайная задержка от 1 до 3 секунд
            const delay = Math.floor(Math.random() * 2000) + 1000;

            timeoutId = setTimeout(() => {
                if (!testInProgress) return;

                // Генерация двух случайных чисел от 1 до 30
                currentNumber1 = Math.floor(Math.random() * 30) + 1;
                currentNumber2 = Math.floor(Math.random() * 30) + 1;

                // Показать иконку звука и статус
                numberDisplayWrapper.style.display = 'block';

                // Произнести числа
                speakNumbers(currentNumber1, currentNumber2);
            }, delay);
        }

        function speakNumbers(number1, number2) {
            isSpeaking = true;
            
            if ('responsiveVoice' in window) {
                // Используем ResponsiveVoice, если доступен
                responsiveVoice.speak(`${number1} плюс ${number2}`, "Russian Female", { 
                    rate: 1,
                    onend: function() {
                        isSpeaking = false;
                        audioInProgressText.textContent = "Оцените сумму!";
                        startTime = Date.now(); // Начинаем отсчет после завершения произношения
                    }
                });
            } else if ('speechSynthesis' in window) {
                // Используем встроенный Web Speech API
                const utterance = new SpeechSynthesisUtterance(`${number1} плюс ${number2}`);
                utterance.lang = 'ru-RU';
                utterance.rate = 1;
                utterance.onend = function() {
                    isSpeaking = false;
                    audioInProgressText.textContent = "Оцените сумму!";
                    startTime = Date.now(); // Начинаем отсчет после завершения произношения
                };
                window.speechSynthesis.speak(utterance);
            }
        }

        function handleResponse(response) {
            if (numberDisplayWrapper.style.display === 'none' || isSpeaking) {
                // Преждевременная реакция
                clearTimeout(timeoutId);
                results.push({
                    trial: currentTrial + 1,
                    number1: null,
                    number2: null,
                    sum: null,
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

                // Вычисление суммы и проверка правильности ответа
                const sum = currentNumber1 + currentNumber2;
                const isEven = sum % 2 === 0;
                const isCorrect = (response === 'even' && isEven) || (response === 'odd' && !isEven);

                results.push({
                    trial: currentTrial + 1,
                    number1: currentNumber1,
                    number2: currentNumber2,
                    sum: sum,
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

            // Вычисление точности (включая преждевременные реакции)
            // Делим количество правильных ответов на общее количество попыток
            const accuracyValue = results.length > 0
                ? ((correctResults.length / results.length) * 100).toFixed(1)
                : "0";
            accuracy.textContent = accuracyValue;

            // Заполнение таблицы результатов
            resultsTable.innerHTML = '';
            results.forEach(result => {
                const row = document.createElement('tr');
                const trialCell = document.createElement('td');
                const numbersCell = document.createElement('td');
                const sumCell = document.createElement('td');
                const timeCell = document.createElement('td');
                const correctCell = document.createElement('td');

                trialCell.textContent = result.trial;

                if (result.time < 0) {
                    numbersCell.textContent = 'N/A';
                    sumCell.textContent = 'N/A';
                    timeCell.textContent = 'Преждевременная реакция';
                    correctCell.textContent = 'Нет';
                    row.classList.add('table-danger');
                } else {
                    numbersCell.textContent = `${result.number1} + ${result.number2}`;
                    sumCell.textContent = result.sum;
                    timeCell.textContent = `${result.time} мс`;
                    correctCell.textContent = result.correct ? 'Да' : 'Нет';

                    if (!result.correct) {
                        row.classList.add('table-warning');
                    }
                }

                row.appendChild(trialCell);
                row.appendChild(numbersCell);
                row.appendChild(sumCell);
                row.appendChild(timeCell);
                row.appendChild(correctCell);
                resultsTable.appendChild(row);
            });

            resultsContainer.style.display = 'block';
        }

        function saveResults() {
            const testData = {
                test_type: 'sound_arithmetic',
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