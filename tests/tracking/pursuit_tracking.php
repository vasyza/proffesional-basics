<?php
session_start();
require_once '../../api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Тест: Слежение с преследованием";
include_once '../../includes/header.php';

// Получение списка респондентов для этого теста
$respondents = [];
try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT login FROM users WHERE ispublic = TRUE");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $placeholders = implode(', ', array_fill(0, count($users), '?'));

    $stmt = $pdo->prepare("SELECT user_name, test_date FROM pursuit_tracking_respondents WHERE user_name IN (" . $placeholders . ")  ORDER BY test_date DESC LIMIT 20");
    $stmt->execute($users);
    $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка при получении списка участников (pursuit_tracking): " . $e->getMessage());
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
                        преследовать и "захватывать" движущуюся цель
                        курсором.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Настройте параметры теста ниже.</li>
                            <li>Нажмите "Начать тест".</li>
                            <li>На экране появится движущаяся цель. Ваша задача
                                - как можно быстрее навести на нее курсор и
                                кликнуть.
                            </li>
                            <li>После успешного "захвата" цель появится в новом
                                месте.
                            </li>
                            <li>Тест будет длиться указанное вами время, или до
                                определенного количества "захватов".
                            </li>
                        </ol>
                    </div>

                    <div id="settingsContainer" class="mb-4">
                        <h5 class="mb-3">Настройки теста:</h5>
                        <div class="row g-3">
                            <div>
                                <label for="testDuration" class="form-label">Время
                                    выполнения (секунд):</label>
                                <select id="testDuration" class="form-select">
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
                                       class="form-control" value="10" min="0"
                                       max="50">
                            </div>
                            <div class="col-md-4">
                                <label for="accelerationInterval"
                                       class="form-label">Интервал ускорения
                                    (сек):</label>
                                <input type="number" id="accelerationInterval"
                                       class="form-control" value="15" min="1"
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
                                           id="showResultsPerMinute" checked>
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
                            <span id="hitsDisplay">Захватов: 0</span>
                            <span id="missesDisplay">Промахов (клик мимо): 0</span>
                            <span id="currentSpeedDisplay">Скорость цели: - px/s</span>
                        </div>
                        <canvas id="trackingCanvas"
                                class="border bg-light mx-auto"
                                style="width: 100%; max-width:700px; height: 450px; cursor:crosshair;"></canvas>
                        <div id="progressContainer" class="mt-3"></div>
                        <div id="resultsPerMinuteContainer" class="mt-2"
                             style="display:none;">
                            <h6>Результаты по минутам:</h6>
                            <ul id="resultsPerMinuteList"
                                class="list-unstyled"></ul>
                        </div>
                    </div>

                    <div id="resultsDisplay" class="mt-4" style="display:none;">
                        <h4>Результаты теста "Слежение с преследованием"</h4>
                        <p>Общее время выполнения: <span
                                    id="totalTestTimeDisplay">-</span> секунд
                        </p>
                        <p>Всего захватов: <span id="totalHitsDisplay">-</span>
                        </p>
                        <p>Всего промахов (клик мимо): <span
                                    id="totalMissesDisplay">-</span></p>
                        <p>Среднее время реакции на захват: <span
                                    id="avgReactionTimeDisplay">-</span> мс</p>
                        <p>Точность (процент успешных захватов): <span
                                    id="accuracyDisplayPercent">-</span> %</p>
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
        const hitsDisplay = document.getElementById('hitsDisplay');
        const missesDisplay = document.getElementById('missesDisplay');
        const currentSpeedDisplay = document.getElementById('currentSpeedDisplay');
        const resultsPerMinuteContainer = document.getElementById('resultsPerMinuteContainer');
        const resultsPerMinuteList = document.getElementById('resultsPerMinuteList');

        const totalTestTimeDisplay = document.getElementById('totalTestTimeDisplay');
        const totalHitsDisplay = document.getElementById('totalHitsDisplay');
        const totalMissesDisplay = document.getElementById('totalMissesDisplay');
        const avgReactionTimeDisplay = document.getElementById('avgReactionTimeDisplay');
        const accuracyDisplayPercent = document.getElementById('accuracyDisplayPercent');

        let testSettings = {};
        let testTimerId, accelerationTimerId, gameLoopId;
        let remainingTime, target, hits, misses, targetAppearTime;
        let reactionTimes = [];
        let allAttemptsData = [];
        let perMinuteStats = {
            hits: 0,
            misses: 0,
            reactionSum: 0,
            reactionCount: 0
        };

        let lastFrameTime, totalTestDurationMs, elapsedMsSinceLastMinuteCheck;
        let progressBar;

        const TARGET_RADIUS = 15;
        const DEBUG = true;

        function logDebug(message) {
            if (DEBUG) {
                console.log(`[PursuitTracking] ${message}`);
            }
        }

        function Target(x, y, radius, speed, color = 'green') {
            this.x = x;
            this.y = y;
            this.radius = radius;
            this.initialSpeed = speed;
            this.speed = speed;
            this.dx = (Math.random() < 0.5 ? -1 : 1);
            this.dy = (Math.random() < 0.5 ? -1 : 1);
            let magnitude = Math.sqrt(this.dx * this.dx + this.dy * this.dy);
            if (magnitude === 0 || isNaN(magnitude)) { // Added isNaN check
                this.dx = this.speed / Math.sqrt(2);
                this.dy = this.speed / Math.sqrt(2);
            } else {
                this.dx = (this.dx / magnitude) * this.speed;
                this.dy = (this.dy / magnitude) * this.speed;
            }
            this.color = color;
            this.isVisible = false;

            this.spawn = function () {
                logDebug(`spawn(): Canvas dimensions: ${canvas.width}x${canvas.height}`);
                this.x = Math.random() * (canvas.width - this.radius * 2) + this.radius;
                this.y = Math.random() * (canvas.height - this.radius * 2) + this.radius;
                let angle = Math.random() * 2 * Math.PI;
                this.dx = Math.cos(angle) * this.speed;
                this.dy = Math.sin(angle) * this.speed;
                this.isVisible = true;
                targetAppearTime = Date.now();
                logDebug(`Target spawned at (${this.x.toFixed(1)}, ${this.y.toFixed(1)}), Speed: ${this.speed.toFixed(1)}, AppearTime: ${targetAppearTime}, Visible: ${this.isVisible}`);
            };

            this.update = function (deltaTime) {
                if (!this.isVisible) return;

                this.x += this.dx * deltaTime;
                this.y += this.dy * deltaTime;

                // Bounce off walls
                if (this.x + this.radius > canvas.width || this.x - this.radius < 0) {
                    this.dx *= -1;
                    this.x = Math.max(this.radius, Math.min(this.x, canvas.width - this.radius)); // Clamp position
                }
                if (this.y + this.radius > canvas.height || this.y - this.radius < 0) {
                    this.dy *= -1;
                    this.y = Math.max(this.radius, Math.min(this.y, canvas.height - this.radius)); // Clamp position
                }
                // logDebug(`Target updated: Old(${prevX.toFixed(1)},${prevY.toFixed(1)}) New(${this.x.toFixed(1)}, ${this.y.toFixed(1)}), dX:${this.dx.toFixed(1)}, dY:${this.dy.toFixed(1)}, dT:${deltaTime.toFixed(3)}`);
            };

            this.draw = function () {
                if (!this.isVisible) {
                    // logDebug("Target.draw() called, but target is not visible.");
                    return;
                }
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
                ctx.closePath();
                // logDebug(`Target drawn at (${this.x.toFixed(1)}, ${this.y.toFixed(1)})`);
            };

            this.isClicked = function (mouseX, mouseY) {
                if (!this.isVisible) return false;
                const distance = Math.sqrt((mouseX - this.x) ** 2 + (mouseY - this.y) ** 2);
                logDebug(`isClicked: Mouse (${mouseX.toFixed(1)}, ${mouseY.toFixed(1)}), Target (${this.x.toFixed(1)}, ${this.y.toFixed(1)}), Radius: ${this.radius}, Distance: ${distance.toFixed(1)}`);
                return distance < this.radius;
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

        function collectSettings() {
            testSettings = {
                duration: parseInt(document.getElementById('testDuration').value),
                initialSpeed: 80,
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

            // Теперь устанавливаем размеры канваса, когда он точно видим
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            logDebug(`Canvas dimensions set: ${canvas.width}x${canvas.height}`);


            startButton.style.display = 'none';
            resultsPerMinuteList.innerHTML = '';
            resultsPerMinuteContainer.style.display = testSettings.showResultsPerMinute ? 'block' : 'none';

            hits = 0;
            misses = 0;
            reactionTimes = [];
            allAttemptsData = [];
            perMinuteStats = {
                hits: 0,
                misses: 0,
                reactionSum: 0,
                reactionCount: 0
            };
            elapsedMsSinceLastMinuteCheck = 0;

            remainingTime = testSettings.duration;
            target = new Target(canvas.width / 2, canvas.height / 2, TARGET_RADIUS, testSettings.initialSpeed);

            timerDisplay.style.display = testSettings.showTime ? 'inline' : 'none';
            hitsDisplay.textContent = `Захватов: ${hits}`;
            missesDisplay.textContent = `Промахов: ${misses}`;
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

            target.spawn(); // Первое появление цели
            canvas.addEventListener('click', handleCanvasClick);
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

        function handleCanvasClick(event) {
            logDebug(`Canvas clicked. Target visible: ${target.isVisible}, Remaining time: ${remainingTime}`);
            if (!target.isVisible || remainingTime <= 0) {
                logDebug("Click ignored: target not visible or time up.");
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const mouseX = event.clientX - rect.left;
            const mouseY = event.clientY - rect.top;
            logDebug(`Click coordinates: Raw (${event.clientX}, ${event.clientY}), Canvas Relative (${mouseX.toFixed(1)}, ${mouseY.toFixed(1)})`);

            const attemptTime = Date.now();
            const reactionTime = attemptTime - targetAppearTime;
            logDebug(`Attempt time: ${attemptTime}, Reaction time: ${reactionTime} ms`);

            if (target.isClicked(mouseX, mouseY)) {
                hits++;
                reactionTimes.push(reactionTime);
                perMinuteStats.hits++;
                perMinuteStats.reactionSum += reactionTime;
                perMinuteStats.reactionCount++;
                target.isVisible = false;
                logDebug(`HIT! Target at (${target.x.toFixed(1)}, ${target.y.toFixed(1)}). Hits: ${hits}`);
                allAttemptsData.push({
                    type: 'hit',
                    timestamp: attemptTime - startTime,
                    reaction_time: reactionTime,
                    target_x: target.x,
                    target_y: target.y,
                    target_speed: target.speed
                });
                if (remainingTime > 0) {
                    setTimeout(() => {
                        if (remainingTime > 0) target.spawn();
                    }, 500 + Math.random() * 1000); // 0.5 to 1.5 seconds delay
                }
            } else {
                misses++;
                perMinuteStats.misses++;
                logDebug(`MISS! Target was at (${target.x.toFixed(1)}, ${target.y.toFixed(1)}). Misses: ${misses}`);
                allAttemptsData.push({
                    type: 'miss',
                    timestamp: attemptTime - startTime,
                    reaction_time: reactionTime, // Time until miss
                    target_x: target.x,
                    target_y: target.y,
                    target_speed: target.speed,
                    click_x: mouseX,
                    click_y: mouseY
                });
            }
            hitsDisplay.textContent = `Захватов: ${hits}`;
            missesDisplay.textContent = `Промахов: ${misses}`;
        }

        function gameLoop() {
            if (remainingTime <= 0) {
                cancelAnimationFrame(gameLoopId);
                logDebug("Game loop stopped: time is up.");
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
            target.draw(); // Will only draw if target.isVisible is true

            elapsedMsSinceLastMinuteCheck += deltaTime * 1000;
            if (elapsedMsSinceLastMinuteCheck >= 60000 && testSettings.showResultsPerMinute) {
                displayResultsPerMinute();
                perMinuteStats = {
                    hits: 0,
                    misses: 0,
                    reactionSum: 0,
                    reactionCount: 0
                };
                elapsedMsSinceLastMinuteCheck = 0;
            }

            gameLoopId = requestAnimationFrame(gameLoop);
        }

        function displayResultsPerMinute() {
            if (!testSettings.showResultsPerMinute) return;
            const minuteNumber = Math.floor((totalTestDurationMs - (remainingTime * 1000) - elapsedMsSinceLastMinuteCheck + (elapsedMsSinceLastMinuteCheck >= 60000 ? 60000 : 0)) / 60000);
            if (minuteNumber < 1) return; // Не показывать для "нулевой" минуты
            const avgMinReaction = perMinuteStats.reactionCount > 0 ? (perMinuteStats.reactionSum / perMinuteStats.reactionCount).toFixed(0) : 'N/A';

            const listItem = document.createElement('li');
            listItem.textContent = `Минута ${minuteNumber}: Захватов - ${perMinuteStats.hits}, Промахов - ${perMinuteStats.misses}, Ср. время - ${avgMinReaction} мс`;
            resultsPerMinuteList.appendChild(listItem);
            logDebug(`Results for minute ${minuteNumber}: Hits - ${perMinuteStats.hits}, Misses - ${perMinuteStats.misses}, Avg Reaction - ${avgMinReaction} ms`);
        }


        function applyDiscreteAcceleration() {
            if (remainingTime <= 0 || !target) return;
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

        function endTest() {
            logDebug("endTest(): Test ending...");
            clearTimeout(testTimerId);
            clearTimeout(accelerationTimerId);
            cancelAnimationFrame(gameLoopId);
            canvas.removeEventListener('click', handleCanvasClick);
            progressBar.setVisible(false);
            testAreaContainer.style.display = 'none';

            if (testSettings.showResultsPerMinute && (perMinuteStats.hits > 0 || perMinuteStats.misses > 0)) {
                displayResultsPerMinute();
            }

            const avgReaction = reactionTimes.length > 0 ? (reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length).toFixed(0) : 'N/A';
            const totalAttempts = hits + misses;
            const accuracyPercent = totalAttempts > 0 ? ((hits / totalAttempts) * 100).toFixed(1) : '0.0';

            if (testSettings.showOverallResults) {
                totalTestTimeDisplay.textContent = testSettings.duration;
                totalHitsDisplay.textContent = hits;
                totalMissesDisplay.textContent = misses;
                avgReactionTimeDisplay.textContent = avgReaction;
                accuracyDisplayPercent.textContent = accuracyPercent;
                resultsDisplay.style.display = 'block';
            }
            startButton.style.display = 'block';
            settingsContainer.style.display = 'block';
            if (target) target.resetSpeed();
            logDebug(`Test ended. Hits: ${hits}, Misses: ${misses}, Avg Reaction: ${avgReaction} ms, Accuracy: ${accuracyPercent}%`);
        }

        function saveResults() {
            logDebug("saveResults(): Preparing data...");
            const avgReaction = reactionTimes.length > 0 ? parseFloat((reactionTimes.reduce((a, b) => a + b, 0) / reactionTimes.length).toFixed(0)) : null;
            const totalAttemptsMade = hits + misses;
            const calculatedAccuracy = totalAttemptsMade > 0 ? parseFloat(((hits / totalAttemptsMade) * 100).toFixed(1)) : 0;

            const detailedResultsForSave = allAttemptsData.map((attempt, index) => ({
                trial_number: index + 1,
                stimulus_details: JSON.stringify({
                    x: attempt.target_x,
                    y: attempt.target_y,
                    speed: attempt.target_speed
                }),
                response_details: attempt.type === 'miss' ? JSON.stringify({
                    click_x: attempt.click_x,
                    click_y: attempt.click_y
                }) : null,
                reaction_time: attempt.reaction_time,
                is_correct: (attempt.type === 'hit') ? 1 : 0
            }));

            const dataToSend = {
                test_type: 'pursuit_tracking',
                average_time: avgReaction,
                accuracy: calculatedAccuracy,
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
