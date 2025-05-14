<?php
session_start();
require_once '../../api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Тест: Простая реакция на движущийся объект";
include_once '../../includes/header.php';

// Получение списка респондентов
$respondents = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT user_name, test_date FROM moving_object_simple_respondents WHERE isPublic = TRUE ORDER BY test_date DESC LIMIT 20");
    $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка при получении списка участников (simple_reaction): " . $e->getMessage());
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $pageTitle; ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Этот тест оценивает вашу способность быстро
                        реагировать на одиночный движущийся объект.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Настройте параметры теста ниже.</li>
                            <li>Нажмите "Начать тест".</li>
                            <li>Когда на экране появится движущийся объект,
                                кликните по нему (или по кнопке "Реагировать")
                                как можно быстрее.
                            </li>
                            <li>Тест будет длиться указанное вами время.</li>
                        </ol>
                    </div>

                    <div id="settingsContainer" class="mb-4">
                        <h5 class="mb-3">Настройки теста:</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="testDuration" class="form-label">Время
                                    выполнения (секунд):</label>
                                <select id="testDuration" class="form-select">
                                    <option value="10" selected>10 секунд
                                    </option>
                                    <option value="120" selected>120 секунд (2
                                        минуты)
                                    </option>
                                    <option value="180">180 секунд (3 минуты)
                                    </option>
                                    <option value="300">300 секунд (5 минут)
                                    </option>
                                    <option value="600">600 секунд (10 минут)
                                    </option>
                                    <option value="1800">1800 секунд (30
                                        минут)
                                    </option>
                                    <option value="2700">2700 секунд (45
                                        минут)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="initialSpeed" class="form-label">Начальная
                                    скорость (пикс/сек):</label>
                                <input type="number" id="initialSpeed"
                                       class="form-control" value="100" min="50"
                                       max="500">
                            </div>
                            <div class="col-md-4">
                                <label for="accelerationValue"
                                       class="form-label">Ускорение
                                    (пикс/сек²):</label>
                                <input type="number" id="accelerationValue"
                                       class="form-control" value="10" min="0"
                                       max="50">
                            </div>
                            <div class="col-md-6">
                                <label for="accelerationInterval"
                                       class="form-label">Интервал ускорения
                                    (сек):</label>
                                <input type="number" id="accelerationInterval"
                                       class="form-control" value="10" min="1">
                            </div>
                            <div class="col-md-6">
                                <label for="accelerationFrequency"
                                       class="form-label">Частота ускорения
                                    (каждый N-й интервал):</label>
                                <input type="number" id="accelerationFrequency"
                                       class="form-control" value="1" min="1">
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-auto">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox" id="showTime"
                                           checked>
                                    <label class="form-check-label"
                                           for="showTime">Отображать время
                                        выполнения</label>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="showResultsPerMinute">
                                    <label class="form-check-label"
                                           for="showResultsPerMinute">Отображать
                                        результат за минуту</label>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="showOverallResults" checked>
                                    <label class="form-check-label"
                                           for="showOverallResults">Отображать
                                        общий результат в конце</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mb-3">
                        <button id="startButton"
                                class="btn btn-primary btn-lg px-4">Начать тест
                        </button>
                    </div>

                    <div id="testAreaContainer" style="display:none;">
                        <div id="testInfo"
                             class="mb-2 d-flex justify-content-between">
                            <span id="timerDisplay">Время: 0с</span>
                            <span id="hitsDisplay">Попаданий: 0</span>
                            <span id="missesDisplay">Промахов: 0</span>
                        </div>
                        <div id="testArea" class="border bg-light mx-auto"
                             style="width: 100%; max-width:600px; height: 400px; position: relative; overflow: hidden; cursor: crosshair;">
                            <div id="movingObject"
                                 style="width: 30px; height: 30px; background-color: red; border-radius: 50%; position: absolute; display:none;"></div>
                        </div>
                        <div id="progressContainer" class="mt-3"></div>
                        <div id="resultsPerMinuteDisplay" class="mt-2"
                             style="display:none;"></div>
                    </div>


                    <div id="resultsDisplay" class="mt-4" style="display:none;">
                        <h4>Результаты теста</h4>
                        <p>Общее время реакции: <span
                                    id="avgReactionTimeDisplay">-</span> мс</p>
                        <p>Всего попаданий: <span id="totalHitsDisplay">-</span>
                        </p>
                        <p>Всего промахов: <span
                                    id="totalMissesDisplay">-</span></p>
                        <button id="saveResultsButton" class="btn btn-success">
                            Сохранить результаты
                        </button>
                        <button id="restartTestButton"
                                class="btn btn-info ms-2">Пройти еще раз
                        </button>
                        <a href="/tests/index.php"
                           class="btn btn-outline-secondary ms-2">К списку
                            тестов</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Участники этого теста</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($respondents)): ?>
                        <div class="table-responsive"
                             style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-striped table-sm">
                                <thead>
                                <tr>
                                    <th>Имя</th>
                                    <th>Дата прохождения</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($respondents as $respondent): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($respondent['user_name']) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($respondent['test_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">Пока никто не
                            проходил этот тест. Будьте первым!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startButton = document.getElementById('startButton');
        const settingsContainer = document.getElementById('settingsContainer');
        const testAreaContainer = document.getElementById('testAreaContainer');
        const testArea = document.getElementById('testArea');
        const movingObject = document.getElementById('movingObject');
        const resultsDisplay = document.getElementById('resultsDisplay');
        const saveResultsButton = document.getElementById('saveResultsButton');
        const restartTestButton = document.getElementById('restartTestButton');

        const timerDisplay = document.getElementById('timerDisplay');
        const hitsDisplay = document.getElementById('hitsDisplay');
        const missesDisplay = document.getElementById('missesDisplay');
        const resultsPerMinuteDisplay = document.getElementById('resultsPerMinuteDisplay');
        const avgReactionTimeDisplay = document.getElementById('avgReactionTimeDisplay');
        const totalHitsDisplay = document.getElementById('totalHitsDisplay');
        const totalMissesDisplay = document.getElementById('totalMissesDisplay');

        let testSettings = {};
        let testTimerId, objectAppearTimerId, accelerationTimerId;
        let remainingTime, currentSpeed, hits, misses, accelerationCounter;
        let reactionTimes = [];
        let attemptsData = [];
        let lastReactionTime = 0;
        let currentObjectX, currentObjectY, directionX, directionY;
        let animationFrameId;
        let isObjectVisible = false;
        let totalTestDuration = 0;

        let progressBar; // Для TestProgress

        startButton.addEventListener('click', startTest);
        restartTestButton.addEventListener('click', startTest);
        saveResultsButton.addEventListener('click', saveResults);
        testArea.addEventListener('click', handleTestAreaClick);


        function collectSettings() {
            testSettings = {
                duration: parseInt(document.getElementById('testDuration').value),
                showTime: document.getElementById('showTime').checked,
                showResultsPerMinute: document.getElementById('showResultsPerMinute').checked,
                showOverallResults: document.getElementById('showOverallResults').checked,
                initialSpeed: parseFloat(document.getElementById('initialSpeed').value),
                accelerationValue: parseFloat(document.getElementById('accelerationValue').value),
                accelerationInterval: parseInt(document.getElementById('accelerationInterval').value) * 1000, // в мс
                accelerationFrequency: parseInt(document.getElementById('accelerationFrequency').value),
            };
            totalTestDuration = testSettings.duration;
        }

        function startTest() {
            collectSettings();
            settingsContainer.style.display = 'none';
            resultsDisplay.style.display = 'none';
            testAreaContainer.style.display = 'block';
            movingObject.style.display = 'none';
            startButton.style.display = 'none';

            hits = 0;
            misses = 0;
            reactionTimes = [];
            attemptsData = [];
            remainingTime = testSettings.duration;
            currentSpeed = testSettings.initialSpeed;
            accelerationCounter = 0;
            isObjectVisible = false;

            hitsDisplay.textContent = `Попаданий: ${hits}`;
            missesDisplay.textContent = `Промахов: ${misses}`;
            timerDisplay.style.display = testSettings.showTime ? 'inline' : 'none';
            resultsPerMinuteDisplay.style.display = testSettings.showResultsPerMinute ? 'block' : 'none';
            resultsPerMinuteDisplay.innerHTML = '';

            // Инициализация прогресс-бара
            if (progressBar) progressBar.updateTrial(0); // Сброс прогресс-бара
            else progressBar = TestProgress.initTrialProgressBar('progressContainer', totalTestDuration);
            progressBar.setVisible(true);


            updateTimerDisplay();
            clearTimeout(testTimerId);
            clearTimeout(objectAppearTimerId);
            clearTimeout(accelerationTimerId);
            cancelAnimationFrame(animationFrameId);

            testTimerId = setInterval(testLoop, 1000);
            if (testSettings.accelerationValue > 0 && testSettings.accelerationInterval > 0) {
                accelerationTimerId = setInterval(applyAcceleration, testSettings.accelerationInterval);
            }
            scheduleObjectAppearance();
        }

        function testLoop() {
            remainingTime--;
            progressBar.updateTrial(totalTestDuration - remainingTime); // Обновляем прогресс
            updateTimerDisplay();

            if (testSettings.showResultsPerMinute && (testSettings.duration - remainingTime) % 60 === 0 && (testSettings.duration - remainingTime) > 0) {
                displayResultsPerMinute();
            }

            if (remainingTime <= 0) {
                endTest();
            }
        }

        function updateTimerDisplay() {
            if (testSettings.showTime) {
                timerDisplay.textContent = `Время: ${remainingTime}с`;
            }
        }

        function scheduleObjectAppearance() {
            if (remainingTime <= 0) return;
            clearTimeout(objectAppearTimerId);
            const delay = Math.random() * 2000 + 1000; // от 1 до 3 секунд
            objectAppearTimerId = setTimeout(() => {
                if (remainingTime > 0) {
                    showObject();
                }
            }, delay);
        }

        function showObject() {
            if (isObjectVisible || remainingTime <= 0) return; // Не показывать, если уже виден или тест окончен
            isObjectVisible = true;
            const testAreaRect = testArea.getBoundingClientRect();
            const objectSize = 30;

            currentObjectX = Math.random() * (testAreaRect.width - objectSize);
            currentObjectY = Math.random() * (testAreaRect.height - objectSize);

            // Случайное направление
            let angle = Math.random() * 2 * Math.PI;
            directionX = Math.cos(angle);
            directionY = Math.sin(angle);

            movingObject.style.left = `${currentObjectX}px`;
            movingObject.style.top = `${currentObjectY}px`;
            movingObject.style.backgroundColor = getRandomColor();
            movingObject.style.display = 'block';
            lastReactionTime = Date.now();

            animateObject(); // Запуск анимации
        }

        function animateObject() {
            if (!isObjectVisible || remainingTime <= 0) {
                cancelAnimationFrame(animationFrameId);
                return;
            }

            const testAreaRect = testArea.getBoundingClientRect();
            const objectSize = 30; // Размер объекта

            // Скорость в пикселях за кадр (при 60 FPS)
            const speedPerFrame = currentSpeed / 60;

            currentObjectX += directionX * speedPerFrame;
            currentObjectY += directionY * speedPerFrame;

            // Отскок от границ
            if (currentObjectX <= 0 || currentObjectX >= testAreaRect.width - objectSize) {
                directionX *= -1;
                currentObjectX = Math.max(0, Math.min(currentObjectX, testAreaRect.width - objectSize)); // Коррекция положения
            }
            if (currentObjectY <= 0 || currentObjectY >= testAreaRect.height - objectSize) {
                directionY *= -1;
                currentObjectY = Math.max(0, Math.min(currentObjectY, testAreaRect.height - objectSize)); // Коррекция положения
            }

            movingObject.style.left = `${currentObjectX}px`;
            movingObject.style.top = `${currentObjectY}px`;

            animationFrameId = requestAnimationFrame(animateObject);
        }


        function handleTestAreaClick(event) {
            if (!isObjectVisible || remainingTime <= 0) {
                if (remainingTime > 0 && !isObjectVisible) { // Клик мимо, когда объекта нет
                    misses++;
                    missesDisplay.textContent = `Промахов: ${misses}`;
                    attemptsData.push({
                        reaction_time: null,
                        is_hit: false,
                        object_speed_at_reaction: currentSpeed
                    });
                }
                return;
            }

            const reactionTime = Date.now() - lastReactionTime;
            const rect = movingObject.getBoundingClientRect();
            const clickX = event.clientX;
            const clickY = event.clientY;

            let currentHit = false;
            if (clickX >= rect.left && clickX <= rect.right && clickY >= rect.top && clickY <= rect.bottom) {
                hits++;
                reactionTimes.push(reactionTime);
                currentHit = true;
                hitsDisplay.textContent = `Попаданий: ${hits}`;
            } else {
                misses++;
                missesDisplay.textContent = `Промахов: ${misses}`;
            }

            attemptsData.push({
                reaction_time: reactionTime,
                is_hit: currentHit,
                object_speed_at_reaction: currentSpeed
            });

            isObjectVisible = false;
            movingObject.style.display = 'none';
            cancelAnimationFrame(animationFrameId); // Остановить анимацию текущего объекта
            scheduleObjectAppearance(); // Показать следующий
        }


        function applyAcceleration() {
            if (remainingTime <= 0) return;
            accelerationCounter++;
            if (accelerationCounter % testSettings.accelerationFrequency === 0) {
                currentSpeed += testSettings.accelerationValue;
            }
        }

        function displayResultsPerMinute() {
            const minutesPassed = (testSettings.duration - remainingTime) / 60;
            const currentHitsPerMinute = hits / minutesPassed;
            const currentMissesPerMinute = misses / minutesPassed;
            resultsPerMinuteDisplay.innerHTML += `<p>Минута ${minutesPassed}: Попаданий - ${currentHitsPerMinute.toFixed(1)}, Промахов - ${currentMissesPerMinute.toFixed(1)}</p>`;
        }

        function endTest() {
            clearTimeout(testTimerId);
            clearTimeout(objectAppearTimerId);
            clearTimeout(accelerationTimerId);
            cancelAnimationFrame(animationFrameId);
            isObjectVisible = false;
            movingObject.style.display = 'none';
            testAreaContainer.style.display = 'none';
            settingsContainer.style.display = 'block'; // Показать настройки снова
            progressBar.setVisible(false);


            const avgReaction = reactionTimes.length > 0 ? (reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length).toFixed(0) : 'N/A';

            if (testSettings.showOverallResults) {
                avgReactionTimeDisplay.textContent = avgReaction;
                totalHitsDisplay.textContent = hits;
                totalMissesDisplay.textContent = misses;
                resultsDisplay.style.display = 'block';
            } else {
                resultsDisplay.style.display = 'none';
            }
            // Если результаты за минуту не отображались во время теста, но опция включена
            if (testSettings.showResultsPerMinute && resultsPerMinuteDisplay.innerHTML === '') {
                displayResultsPerMinute(); // Показать итоговый результат за все время
            }
        }

        function saveResults() {
            const avgReaction = reactionTimes.length > 0 ? parseFloat((reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length).toFixed(0)) : null;
            const totalInteractions = hits + misses;
            const calculatedAccuracy = totalInteractions > 0 ? parseFloat(((hits / totalInteractions) * 100).toFixed(1)) : 0;

            const currentTestType = 'moving_object_simple';

            const dataToSend = {
                test_type: currentTestType,
                average_time: avgReaction,
                accuracy: calculatedAccuracy,
                results: attemptsData.map((attempt, index) => {
                    let stimulusDetails = {
                        speed: attempt.object_speed_at_reaction
                    };
                    if (currentTestType === 'moving_object_complex' && attempt.target_object_details) {
                        stimulusDetails.target = attempt.target_object_details;
                    }
                    if (currentTestType === 'moving_object_complex' && testSettings.numberOfObjects) {
                        stimulusDetails.numberOfObjects = testSettings.numberOfObjects;
                    }

                    return {
                        trial_number: index + 1,
                        stimulus_value: JSON.stringify(stimulusDetails),
                        response_value: 'click',
                        reaction_time: attempt.reaction_time,
                        is_correct: attempt.is_hit
                    };
                }),
                batch_id: new URLSearchParams(window.location.search).get('batch_id')
            };

            fetch('/api/save_test_results.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dataToSend)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Результаты успешно сохранены!');
                        resultsDisplay.style.display = 'none';
                        const batchId = new URLSearchParams(window.location.search).get('batch_id');
                        if (batchId) {
                            window.location.href = `/tests/test_batch.php?batch_id=${batchId}`;
                        } else {
                            window.location.href = '/tests/results.php';
                        }
                    } else {
                        alert('Ошибка сохранения: ' + (data.message || 'Неизвестная ошибка'));
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при отправке результатов.');
                });
        }

        function getRandomColor() {
            const r = Math.floor(Math.random() * 256);
            const g = Math.floor(Math.random() * 256);
            const b = Math.floor(Math.random() * 256);
            return `rgb(${r},${g},${b})`;
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>
