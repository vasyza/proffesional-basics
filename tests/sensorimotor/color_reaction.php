<?php
session_start();
require_once '../../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

// Получаем список участников теста
$respondents = [];
try {
    // Получаем подключение к базе данных
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT user_name, test_date FROM color_respondents WHERE isPublic = TRUE ORDER BY test_date DESC LIMIT 20");
    $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // В случае ошибки просто продолжаем без списка
    error_log("Ошибка при получении списка участников: " . $e->getMessage());
}

$pageTitle = "Тест реакции на цвета";
include_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест на сложную сенсомоторную реакцию на
                        разные цвета</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Этот тест измеряет скорость вашей реакции на
                        разные цвета. В зависимости от цвета
                        круга, вам нужно нажать соответствующую кнопку:</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите кнопку "Начать тест"</li>
                            <li>На экране будут появляться круги разных цветов
                            </li>
                            <li>Если появится <span class="text-danger fw-bold">КРАСНЫЙ</span>
                                круг - нажмите кнопку
                                "Красный"
                            </li>
                            <li>Если появится <span
                                        class="text-success fw-bold">ЗЕЛЕНЫЙ</span>
                                круг - нажмите кнопку
                                "Зеленый"
                            </li>
                            <li>Если появится <span
                                        class="text-primary fw-bold">СИНИЙ</span>
                                круг - нажмите кнопку
                                "Синий"
                            </li>
                            <li>Тест включает 15 попыток</li>
                        </ol>
                    </div>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">
                            Начать тест
                        </button>
                    </div>

                    <div class="reaction-test-area mb-4">
                        <div id="stimulusArea" class="stimulus-area">
                            <div id="colorStimulus"
                                 class="color-stimulus"></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <button id="redButton"
                                        class="btn btn-danger btn-lg w-100"
                                        disabled>Красный
                                </button>
                            </div>
                            <div class="col-4">
                                <button id="greenButton"
                                        class="btn btn-success btn-lg w-100"
                                        disabled>Зеленый
                                </button>
                            </div>
                            <div class="col-4">
                                <button id="blueButton"
                                        class="btn btn-primary btn-lg w-100"
                                        disabled>Синий
                                </button>
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
                                    <strong>Среднее время реакции:</strong>
                                    <span id="averageTime">0</span> мс
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>Точность:</strong> <span
                                            id="accuracy">0</span>%
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button id="saveResultsButton"
                                    class="btn btn-success">Сохранить результаты
                            </button>
                            <a href="/tests/index.php"
                               class="btn btn-outline-primary">Вернуться к
                                списку тестов</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- раздел со списком участников -->
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Участники теста</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($respondents)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Имя участника</th>
                                    <th>Дата прохождения</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($respondents as $index => $respondent): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($respondent['user_name']) ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($respondent['test_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Пока никто не прошел
                            этот тест. Будьте первым!
                        </div>
                    <?php endif; ?>
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
        const resultsContainer = document.getElementById('resultsContainer');
        const resultsTable = document.getElementById('resultsTable');
        const averageTime = document.getElementById('averageTime');
        const accuracy = document.getElementById('accuracy');
        const saveResultsButton = document.getElementById('saveResultsButton');

        const colors = [
            {name: 'red', hex: '#ff0000', button: redButton},
            {name: 'green', hex: '#00aa00', button: greenButton},
            {name: 'blue', hex: '#0000ff', button: blueButton}
        ];

        let currentTrial = 0;
        const totalTrials = 15;
        let startTime;
        let results = [];
        let testInProgress = false;
        let timeoutId;
        let currentColor;

        // Initialize our new progress bar
        const progressBar = TestProgress.initTrialProgressBar('progressContainer', totalTrials);

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
                clearTimeout(timeoutId);
                results.push({
                    trial_number: currentTrial + 1,
                    color: null,
                    reaction_time: -1,
                    is_correct: false
                });

                currentTrial++;
                progressBar.updateTrial(currentTrial);
                nextTrial();
            } else {
                // Реакция на стимул
                const endTime = Date.now();
                const reactionTime = endTime - startTime;
                const isCorrect = color === currentColor.name;

                results.push({
                    trial_number: currentTrial + 1,
                    color: currentColor.name,
                    reaction_time: reactionTime,
                    is_correct: isCorrect
                });

                currentTrial++;
                progressBar.updateTrial(currentTrial);
                nextTrial();
            }
        }

        function endTest() {
            testInProgress = false;
            redButton.disabled = true;
            greenButton.disabled = true;
            blueButton.disabled = true;

            // Вычисление среднего времени реакции (только для корректных ответов)
            const correctResults = results.filter(r => r.is_correct && r.reaction_time > 0);
            let totalTime = 0;
            let correctCount = correctResults.length;

            correctResults.forEach(result => {
                totalTime += result.reaction_time;
            });

            averageTime.textContent = correctCount > 0 ? (totalTime / correctCount).toFixed(1) : "N/A";

            // Вычисление точности (включая преждевременные реакции)
            // Делим количество правильных ответов на общее количество попыток
            accuracy.textContent = results.length > 0
                ? ((correctResults.length / results.length) * 100).toFixed(1)
                : "0";

            // Заполнение таблицы результатов
            resultsTable.innerHTML = '';
            results.forEach(result => {
                const row = document.createElement('tr');
                const trialCell = document.createElement('td');
                const colorCell = document.createElement('td');
                const timeCell = document.createElement('td');
                const correctCell = document.createElement('td');

                trialCell.textContent = result.trial_number;
                colorCell.textContent = result.color ? result.color : 'Преждевременная реакция';
                timeCell.textContent = result.reaction_time > 0 ? `${result.reaction_time} мс` : '-';
                correctCell.textContent = result.is_correct ? 'Да' : 'Нет';

                if (!result.is_correct) {
                    row.classList.add('table-warning');
                }

                row.appendChild(trialCell);
                row.appendChild(colorCell);
                row.appendChild(timeCell);
                row.appendChild(correctCell);
                resultsTable.appendChild(row);
            });

            resultsContainer.style.display = 'block';
        }

        function saveResults() {
            const urlParams = new URLSearchParams(window.location.search);
            const batchId = urlParams.get('batch_id');

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

                        if (batchId) {
                            window.location.href = `/tests/test_batch.php?batch_id=${batchId}`;
                        } else {
                            window.location.href = '/tests/results.php';
                        }

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
