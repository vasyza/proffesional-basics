<?php
session_start();
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Тест: Аналоговое слежение";
include_once '../../includes/header.php';

$respondents = [];
try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT login FROM users WHERE ispublic = TRUE");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $placeholders = implode(', ', array_fill(0, count($users), '?'));

    $stmt = $pdo->prepare("SELECT user_name, test_date FROM analog_tracking_respondents WHERE user_name IN (" . $placeholders . ")  ORDER BY test_date DESC LIMIT 20");
    $stmt->execute($users);
    $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка при получении списка участников (analog_tracking): " . $e->getMessage());
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
                    <p class="mb-3">Этот тест оценивает вашу способность
                        удерживать курсор на движущейся цели как можно точнее и
                        дольше.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Настройте параметры теста ниже.</li>
                            <li>Нажмите "Начать тест".</li>
                            <li>На экране появится цель (например, круг).
                                Старайтесь удерживать курсор мыши на этой цели.
                            </li>
                            <li>Цель будет двигаться по экрану. Ваша задача -
                                минимизировать отклонение курсора от центра
                                цели.
                            </li>
                            <li>Тест будет длиться указанное вами время.</li>
                        </ol>
                    </div>

                    <div id="settingsContainer" class="mb-4">
                        <h5 class="mb-3">Настройки теста:</h5>
                        <div class="row g-3">
                            <div>
                                <label for="testDuration" class="form-label">Время
                                    выполнения (секунд):</label>
                                <select id="testDuration" class="form-select">
                                    <option value="10" selected>10 секунд (2
                                        минуты)
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
                                    <option value="900">900 секунд (15 минут)
                                    </option>
                                    <option value="1800">1800 секунд (30
                                        минут)
                                    </option>
                                    <option value="2700">2700 секунд (45
                                        минут)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label for="accelerationValue"
                                       class="form-label">Ускорение (пикс/сек
                                    каждые N сек):</label>
                                <input type="number" id="accelerationValue"
                                       class="form-control" value="5" min="0"
                                       max="50">
                            </div>
                            <div class="col-md-4">
                                <label for="accelerationInterval"
                                       class="form-label">Интервал ускорения
                                    (сек):</label>
                                <input type="number" id="accelerationInterval"
                                       class="form-control" value="10" min="1"
                                       max="60">
                            </div>
                            <div class="col-md-4">
                                <label for="accelerationType"
                                       class="form-label">Тип ускорения:</label>
                                <select id="accelerationType"
                                        class="form-select">
                                    <option value="discrete" selected>Дискретно
                                        по времени
                                    </option>
                                    <option value="random">Случайно</option>
                                </select>
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

                    <div id="testAreaContainer"
                         style="display:none; flex-direction: column">
                        <div id="testInfo"
                             class="mb-2 d-flex justify-content-between">
                            <span id="timerDisplay">Время: 0с</span>
                            <span id="currentAccuracyDisplay">Текущее отклонение: - px</span>
                            <span id="currentSpeedDisplay">Скорость цели: - px/s</span>
                        </div>
                        <canvas id="trackingCanvas"
                                class="border bg-light mx-auto"
                                style="width: 100%; max-width:700px; height: 450px; cursor: none; margin: auto 0"></canvas>
                        <div id="progressContainer" class="mt-3"></div>
                        <div id="resultsPerMinuteContainer" class="mt-2"
                             style="display:none;">
                            <h6>Результаты по минутам (среднее отклонение):</h6>
                            <ul id="resultsPerMinuteList"
                                class="list-unstyled"></ul>
                        </div>
                    </div>

                    <div id="resultsDisplay" class="mt-4" style="display:none;">
                        <h4>Результаты теста "Аналоговое слежение"</h4>
                        <p>Общее время выполнения: <span
                                    id="totalTestTimeDisplay">-</span> секунд
                        </p>
                        <p>Среднее отклонение от цели: <span
                                    id="avgDeviationDisplay">-</span> px</p>
                        <p>Процент времени "на цели" (отклонение < 15px): <span
                                    id="timeOnTargetDisplay">-</span> %</p>
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
        const canvas = document.getElementById('trackingCanvas');
        const ctx = canvas.getContext('2d');

        const startButton = document.getElementById('startButton');
        const settingsContainer = document.getElementById('settingsContainer');
        const testAreaContainer = document.getElementById('testAreaContainer');
        const resultsDisplay = document.getElementById('resultsDisplay');
        const saveResultsButton = document.getElementById('saveResultsButton');
        const restartTestButton = document.getElementById('restartTestButton');

        const timerDisplay = document.getElementById('timerDisplay');
        const currentAccuracyDisplay = document.getElementById('currentAccuracyDisplay');
        const currentSpeedDisplay = document.getElementById('currentSpeedDisplay');
        const resultsPerMinuteContainer = document.getElementById('resultsPerMinuteContainer');
        const resultsPerMinuteList = document.getElementById('resultsPerMinuteList');

        const avgDeviationDisplay = document.getElementById('avgDeviationDisplay');
        const timeOnTargetDisplay = document.getElementById('timeOnTargetDisplay');
        const totalTestTimeDisplay = document.getElementById('totalTestTimeDisplay');

        let testSettings = {};
        let testTimerId, accelerationTimerId, gameLoopId;
        let remainingTime, target, cursor, deviations, timeOnTargetMs;
        let startTime, lastFrameTime, totalTestDurationMs,
            elapsedMsSinceLastMinuteCheck;
        let perMinuteDeviations = [];
        let allAttemptsData = [];
        let progressBar;
        let mouseInsideCanvas = false;

        const TARGET_RADIUS = 10;
        const CURSOR_RADIUS = 5;
        const ON_TARGET_THRESHOLD = 15;
        const DEBUG = true;

        function logDebug(message) {
            if (DEBUG) {
                console.log(`[AnalogTracking] ${message}`);
            }
        }

        function Target(x, y, radius, speed, color = 'red') {
            this.x = x;
            this.y = y;
            this.radius = radius;
            this.initialSpeed = speed;
            this.speed = speed;
            this.dx = (Math.random() < 0.5 ? -1 : 1);
            this.dy = (Math.random() < 0.5 ? -1 : 1);
            let magnitude = Math.sqrt(this.dx * this.dx + this.dy * this.dy);
            if (magnitude === 0 || isNaN(magnitude)) {
                this.dx = this.speed / Math.sqrt(2);
                this.dy = this.speed / Math.sqrt(2);
            } else {
                this.dx = (this.dx / magnitude) * this.speed;
                this.dy = (this.dy / magnitude) * this.speed;
            }
            this.color = color;

            this.update = function (deltaTime) {
                this.x += this.dx * deltaTime;
                this.y += this.dy * deltaTime;

                if (this.x + this.radius > canvas.width || this.x - this.radius < 0) {
                    this.dx *= -1;
                    this.x = Math.max(this.radius, Math.min(this.x, canvas.width - this.radius));
                }
                if (this.y + this.radius > canvas.height || this.y - this.radius < 0) {
                    this.dy *= -1;
                    this.y = Math.max(this.radius, Math.min(this.y, canvas.height - this.radius));
                }
                // logDebug(`Target updated: Old(${prevX.toFixed(1)},${prevY.toFixed(1)}) New(${this.x.toFixed(1)}, ${this.y.toFixed(1)}), dX:${this.dx.toFixed(1)}, dY:${this.dy.toFixed(1)}, Speed:${this.speed.toFixed(1)}, dT:${deltaTime.toFixed(3)}`);
            };

            this.draw = function () {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
                ctx.closePath();
                // logDebug(`Target drawn at (${this.x.toFixed(1)}, ${this.y.toFixed(1)})`);
            };
            this.resetSpeed = function () {
                this.speed = this.initialSpeed;
                let magnitude = Math.sqrt(this.dx * this.dx + this.dy * this.dy);
                if (magnitude === 0 || isNaN(magnitude)) {
                    this.dx = this.speed / Math.sqrt(2);
                    this.dy = this.speed / Math.sqrt(2);
                } else {
                    this.dx = (this.dx / magnitude) * this.speed;
                    this.dy = (this.dy / magnitude) * this.speed;
                }
            }
        }

        function Cursor(x, y, radius, color = 'blue') {
            this.x = x;
            this.y = y;
            this.radius = radius;
            this.color = color;
            this.isVisible = false;

            this.draw = function () {
                if (!this.isVisible) return;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
                ctx.closePath();
                // logDebug(`Cursor drawn at (${this.x.toFixed(1)}, ${this.y.toFixed(1)})`);
            };
        }

        function collectSettings() {
            testSettings = {
                duration: parseInt(document.getElementById('testDuration').value),
                initialSpeed: 50,
                accelerationValue: parseInt(document.getElementById('accelerationValue').value),
                accelerationInterval: parseInt(document.getElementById('accelerationInterval').value) * 1000,
                accelerationType: document.getElementById('accelerationType').value,
                showTime: document.getElementById('showTime').checked,
                showResultsPerMinute: document.getElementById('showResultsPerMinute').checked,
                showOverallResults: document.getElementById('showOverallResults').checked,
            };
            totalTestDurationMs = testSettings.duration * 1000;
            logDebug("Settings collected: " + JSON.stringify(testSettings));
        }

        function startTest() {
            logDebug("startTest(): Initializing test...");
            collectSettings();
            settingsContainer.style.display = 'none';
            resultsDisplay.style.display = 'none';
            testAreaContainer.style.display = 'block'; // Сначала делаем контейнер видимым

            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            logDebug(`Canvas dimensions set: ${canvas.width}x${canvas.height}`);

            startButton.style.display = 'none';
            resultsPerMinuteList.innerHTML = '';
            resultsPerMinuteContainer.style.display = testSettings.showResultsPerMinute ? 'block' : 'none';

            deviations = [];
            allAttemptsData = [];
            timeOnTargetMs = 0;
            elapsedMsSinceLastMinuteCheck = 0;
            perMinuteDeviations = [];
            mouseInsideCanvas = false;

            remainingTime = testSettings.duration;
            target = new Target(canvas.width / 2, canvas.height / 2, TARGET_RADIUS, testSettings.initialSpeed);
            cursor = new Cursor(-CURSOR_RADIUS * 2, -CURSOR_RADIUS * 2, CURSOR_RADIUS); // Изначально за пределами

            timerDisplay.style.display = testSettings.showTime ? 'inline' : 'none';
            currentSpeedDisplay.textContent = `Скорость цели: ${target.speed.toFixed(0)} px/s`;

            if (progressBar) progressBar.updateTrial(0);
            else progressBar = TestProgress.initTrialProgressBar('progressContainer', testSettings.duration);
            progressBar.setVisible(true);

            updateTimerDisplay();
            clearTimeout(testTimerId);
            clearTimeout(accelerationTimerId);
            cancelAnimationFrame(gameLoopId);

            startTime = Date.now();
            lastFrameTime = startTime;

            testTimerId = setInterval(testTick, 1000);
            if (testSettings.accelerationValue > 0 && testSettings.accelerationInterval > 0 && testSettings.accelerationType === 'discrete') {
                accelerationTimerId = setInterval(applyDiscreteAcceleration, testSettings.accelerationInterval);
            }

            canvas.addEventListener('mousemove', updateCursorPosition);
            canvas.addEventListener('mouseenter', () => {
                mouseInsideCanvas = true;
                cursor.isVisible = true; // Показываем курсор, когда мышь входит в канвас
                logDebug("Mouse entered canvas.");
            });
            canvas.addEventListener('mouseleave', () => {
                mouseInsideCanvas = false;
                cursor.isVisible = false; // Скрываем курсор, когда мышь покидает канвас
                if (remainingTime > 0) {
                    currentAccuracyDisplay.textContent = `Текущее отклонение: N/A (курсор вне зоны)`;
                }
                logDebug("Mouse left canvas.");
            });
            gameLoopId = requestAnimationFrame(gameLoop);
            logDebug("startTest(): Test initialized and game loop started.");
        }

        function testTick() {
            remainingTime--;
            progressBar.updateTrial(testSettings.duration - remainingTime);
            updateTimerDisplay();

            if (remainingTime <= 0) {
                endTest();
            }
        }

        function updateTimerDisplay() {
            if (testSettings.showTime) {
                timerDisplay.textContent = `Время: ${remainingTime}с`;
            }
        }

        function updateCursorPosition(event) {
            const rect = canvas.getBoundingClientRect();
            cursor.x = event.clientX - rect.left;
            cursor.y = event.clientY - rect.top;
            if (!cursor.isVisible && mouseInsideCanvas) { // Если мышь внутри, но курсор еще не видим (первое движение)
                cursor.isVisible = true;
            }
            // logDebug(`Cursor moved to (${cursor.x.toFixed(1)}, ${cursor.y.toFixed(1)})`);
        }

        function gameLoop() {
            if (remainingTime <= 0) {
                cancelAnimationFrame(gameLoopId);
                return;
            }

            const now = Date.now();
            const deltaTime = (now - lastFrameTime) / 1000;
            lastFrameTime = now;

            if (testSettings.accelerationType === 'random' && testSettings.accelerationValue > 0 && testSettings.accelerationInterval > 0) {
                if (Math.random() < (deltaTime / (testSettings.accelerationInterval / 1000))) {
                    target.speed += testSettings.accelerationValue;
                    let magnitude = Math.sqrt(target.dx * target.dx + target.dy * target.dy);
                    if (magnitude === 0 || isNaN(magnitude)) {
                        let angle = Math.random() * 2 * Math.PI;
                        target.dx = Math.cos(angle) * target.speed;
                        target.dy = Math.sin(angle) * target.speed;
                    } else {
                        target.dx = (target.dx / magnitude) * target.speed;
                        target.dy = (target.dy / magnitude) * target.speed;
                    }
                    currentSpeedDisplay.textContent = `Скорость цели: ${target.speed.toFixed(0)} px/s`;
                    logDebug(`Random acceleration. New speed: ${target.speed.toFixed(1)}`);
                }
            }

            target.update(deltaTime);

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            target.draw();
            if (mouseInsideCanvas) { // Рисуем курсор только если мышь внутри и курсор должен быть видим
                cursor.draw();
            }

            let distance = Infinity; // По умолчанию большое отклонение
            if (mouseInsideCanvas && cursor.isVisible) {
                const dx = target.x - cursor.x;
                const dy = target.y - cursor.y;
                distance = Math.sqrt(dx * dx + dy * dy);
                currentAccuracyDisplay.textContent = `Текущее отклонение: ${distance.toFixed(1)} px`;
                if (distance <= ON_TARGET_THRESHOLD) {
                    timeOnTargetMs += deltaTime * 1000;
                }
            } else {
                currentAccuracyDisplay.textContent = `Текущее отклонение: N/A`;
            }

            deviations.push(distance);
            allAttemptsData.push({
                timestamp: now - startTime,
                target_x: target.x,
                target_y: target.y,
                cursor_x: (mouseInsideCanvas && cursor.isVisible) ? cursor.x : null,
                cursor_y: (mouseInsideCanvas && cursor.isVisible) ? cursor.y : null,
                deviation: distance,
                target_speed: target.speed
            });

            elapsedMsSinceLastMinuteCheck += deltaTime * 1000;
            if (mouseInsideCanvas && cursor.isVisible) { // Собираем только если курсор активен
                perMinuteDeviations.push(distance);
            }


            if (elapsedMsSinceLastMinuteCheck >= 60000) {
                displayResultsPerMinute();
                perMinuteDeviations = [];
                elapsedMsSinceLastMinuteCheck = 0;
            }

            gameLoopId = requestAnimationFrame(gameLoop);
        }

        function applyDiscreteAcceleration() {
            if (remainingTime <= 0) return;
            target.speed += testSettings.accelerationValue;
            const currentMagnitude = Math.sqrt(target.dx * target.dx + target.dy * target.dy);
            if (currentMagnitude > 0 && !isNaN(currentMagnitude)) {
                target.dx = (target.dx / currentMagnitude) * target.speed;
                target.dy = (target.dy / currentMagnitude) * target.speed;
            } else {
                let angle = Math.random() * 2 * Math.PI;
                target.dx = Math.cos(angle) * target.speed;
                target.dy = Math.sin(angle) * target.speed;
            }
            currentSpeedDisplay.textContent = `Скорость цели: ${target.speed.toFixed(0)} px/s`;
            logDebug(`Discrete acceleration. New speed: ${target.speed.toFixed(1)}`);
        }

        function displayResultsPerMinute() {
            if (!testSettings.showResultsPerMinute) return;
            const validMinuteDeviations = perMinuteDeviations.filter(d => isFinite(d));
            if (validMinuteDeviations.length === 0) return;

            const minuteNumber = Math.floor((totalTestDurationMs - (remainingTime * 1000) - elapsedMsSinceLastMinuteCheck + (elapsedMsSinceLastMinuteCheck >= 60000 ? 60000 : 0)) / 60000);
            if (minuteNumber < 1) return;

            const avgMinuteDeviation = validMinuteDeviations.reduce((a, b) => a + b, 0) / validMinuteDeviations.length;
            const listItem = document.createElement('li');
            listItem.textContent = `Минута ${minuteNumber}: ${avgMinuteDeviation.toFixed(1)} px`;
            resultsPerMinuteList.appendChild(listItem);
            logDebug(`Results for minute ${minuteNumber}: Avg Deviation - ${avgMinuteDeviation.toFixed(1)} px`);
        }

        function endTest() {
            logDebug("endTest(): Test ending...");
            clearTimeout(testTimerId);
            clearTimeout(accelerationTimerId);
            cancelAnimationFrame(gameLoopId);
            canvas.removeEventListener('mousemove', updateCursorPosition);
            canvas.removeEventListener('mouseenter', () => {
                mouseInsideCanvas = true;
                cursor.isVisible = true;
            });
            canvas.removeEventListener('mouseleave', () => {
                mouseInsideCanvas = false;
                cursor.isVisible = false;
            });
            progressBar.setVisible(false);
            testAreaContainer.style.display = 'none';

            if (testSettings.showResultsPerMinute && perMinuteDeviations.filter(d => isFinite(d)).length > 0) {
                displayResultsPerMinute();
            }

            const validDeviations = deviations.filter(d => isFinite(d));
            const avgDeviation = validDeviations.length > 0 ? (validDeviations.reduce((a, b) => a + b, 0) / validDeviations.length).toFixed(1) : 'N/A';
            const timeOnTargetPercent = totalTestDurationMs > 0 ? ((timeOnTargetMs / totalTestDurationMs) * 100).toFixed(1) : 'N/A';

            if (testSettings.showOverallResults) {
                totalTestTimeDisplay.textContent = testSettings.duration;
                avgDeviationDisplay.textContent = avgDeviation;
                timeOnTargetDisplay.textContent = timeOnTargetPercent;
                resultsDisplay.style.display = 'block';
            }
            startButton.style.display = 'block';
            settingsContainer.style.display = 'block';
            if (target) target.resetSpeed();
            logDebug(`Test ended. Avg Deviation: ${avgDeviation} px, Time on Target: ${timeOnTargetPercent}%`);
        }

        function saveResults() {
            logDebug("saveResults(): Preparing data...");
            const timeOnTargetPercent = totalTestDurationMs > 0 ? parseFloat(((timeOnTargetMs / totalTestDurationMs) * 100).toFixed(1)) : null;

            const detailedResultsForSave = allAttemptsData.map((attempt, index) => ({
                trial_number: index + 1,
                stimulus_details: JSON.stringify({
                    x: attempt.target_x,
                    y: attempt.target_y,
                    speed: attempt.target_speed
                }),
                response_details: JSON.stringify({
                    x: attempt.cursor_x,
                    y: attempt.cursor_y
                }),
                tracking_accuracy: isFinite(attempt.deviation) ? attempt.deviation : null,
                is_correct: isFinite(attempt.deviation) && attempt.deviation <= ON_TARGET_THRESHOLD ? 1 : 0
            }));

            const dataToSend = {
                test_type: 'analog_tracking',
                accuracy: timeOnTargetPercent,
                results: detailedResultsForSave,
                batch_id: new URLSearchParams(window.location.search).get('batch_id')
            };
            logDebug("Data to send: " + JSON.stringify(dataToSend));


            fetch('/api/save_test_results.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dataToSend)
            })
                .then(response => {
                    logDebug("Save response status: " + response.status);
                    return response.json();
                })
                .then(data => {
                    logDebug("Save response data: " + JSON.stringify(data));
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
                    console.error('Ошибка при отправке результатов:', error);
                    alert('Произошла ошибка при отправке результатов.');
                });
        }

        startButton.addEventListener('click', startTest);
        restartTestButton.addEventListener('click', startTest);
        saveResultsButton.addEventListener('click', saveResults);
    });
</script>

<?php include_once '../../includes/footer.php'; ?>
