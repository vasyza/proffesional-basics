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
    $stmt = $pdo->query("SELECT user_name, test_date FROM light_respondents WHERE isPublic = TRUE ORDER BY test_date DESC LIMIT 20");
    $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // В случае ошибки просто продолжаем без списка
    error_log("Ошибка при получении списка участников: " . $e->getMessage());
}

$pageTitle = "Тест реакции на свет";
include_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест на простую сенсомоторную реакцию на
                        свет</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Этот тест измеряет скорость вашей реакции на
                        световой стимул. Как только на экране
                        появится цветной круг, нажмите как можно быстрее на
                        кнопку.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите кнопку "Начать тест"</li>
                            <li>Будьте готовы и внимательно смотрите на область
                                ниже
                            </li>
                            <li>Как только увидите круг, нажмите кнопку "Клик!"
                                как можно быстрее
                            </li>
                            <li>Тест включает 10 попыток</li>
                        </ol>
                    </div>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">
                            Начать тест
                        </button>
                    </div>

                    <div class="reaction-test-area mb-4">
                        <div id="stimulusArea" class="stimulus-area">
                            <div id="lightStimulus"
                                 class="light-stimulus"></div>
                        </div>
                        <button id="reactionButton"
                                class="btn btn-danger btn-lg w-100" disabled>
                            Клик!
                        </button>
                    </div>

                    <div id="progressContainer" class="mb-3"></div>

                    <div id="resultsContainer" style="display: none;">
                        <h5>Результаты:</h5>
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Попытка</th>
                                <th>Время реакции (мс)</th>
                            </tr>
                            </thead>
                            <tbody id="resultsTable">
                            </tbody>
                        </table>
                        <div class="alert alert-success">
                            <strong>Среднее время реакции:</strong> <span
                                    id="averageTime">0</span> мс
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

    .light-stimulus {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #ff0000;
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
        const reactionButton = document.getElementById('reactionButton');
        const lightStimulus = document.getElementById('lightStimulus');
        const resultsContainer = document.getElementById('resultsContainer');
        const resultsTable = document.getElementById('resultsTable');
        const averageTime = document.getElementById('averageTime');
        const saveResultsButton = document.getElementById('saveResultsButton');

        const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff'];
        let currentTrial = 0;
        const totalTrials = 10;
        let startTime;
        let results = [];
        let testInProgress = false;
        let timeoutId;

        // Initialize our new progress bar
        const progressBar = TestProgress.initTrialProgressBar('progressContainer', totalTrials);

        startButton.addEventListener('click', startTest);
        reactionButton.addEventListener('click', handleReaction);
        saveResultsButton.addEventListener('click', saveResults);

        function startTest() {
            startButton.style.display = 'none';
            reactionButton.disabled = false;
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

            reactionButton.disabled = false;
            lightStimulus.style.display = 'none';

            // Случайная задержка от 1 до 4 секунд
            const delay = Math.floor(Math.random() * 3000) + 1000;

            timeoutId = setTimeout(() => {
                if (!testInProgress) return;

                // Выбор случайного цвета
                lightStimulus.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];

                // Показать стимул
                lightStimulus.style.display = 'block';
                startTime = Date.now();
            }, delay);
        }

        function handleReaction() {
            if (lightStimulus.style.display === 'none') {
                // Преждевременная реакция
                clearTimeout(timeoutId);
                results.push({
                    trial_number: currentTrial + 1,
                    reaction_time: -1 // Код для преждевременной реакции
                });

                currentTrial++;
                progressBar.updateTrial(currentTrial);
                nextTrial();
            } else {
                // Правильная реакция
                const endTime = Date.now();
                const reactionTime = endTime - startTime;

                results.push({
                    trial_number: currentTrial + 1,
                    reaction_time: reactionTime
                });

                currentTrial++;
                progressBar.updateTrial(currentTrial);
                nextTrial();
            }
        }

        function endTest() {
            testInProgress = false;
            reactionButton.disabled = true;

            // Вычисление среднего времени реакции (исключая преждевременные)
            const validResults = results.filter(r => r.time > 0);
            const sum = validResults.reduce((acc, r) => acc + r.time, 0);
            const avg = validResults.length > 0 ? Math.round(sum / validResults.length) : 0;

            // Отображение результатов
            resultsTable.innerHTML = '';
            results.forEach(r => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${r.trial}</td>
                    <td>${r.time > 0 ? r.time + ' мс' : 'Преждевременная реакция'}</td>
                `;
                resultsTable.appendChild(row);
            });

            averageTime.textContent = avg;
            resultsContainer.style.display = 'block';
        }

        function saveResults() {
            const urlParams = new URLSearchParams(window.location.search);
            const batchId = urlParams.get('batch_id');

            // Отправка данных на сервер
            fetch('/api/save_test_results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    test_type: 'light_reaction',
                    results: results,
                    average_time: parseInt(averageTime.textContent)
                }),
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
                    console.error('Error:', error);
                    alert('Произошла ошибка при сохранении результатов');
                });
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>
