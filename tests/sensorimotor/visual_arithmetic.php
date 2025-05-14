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
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT login FROM users WHERE ispublic = TRUE");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $placeholders = implode(', ', array_fill(0, count($users), '?'));

    $stmt = $pdo->prepare("SELECT user_name, test_date FROM v_arith_respondents WHERE user_name IN (" . $placeholders . ")  ORDER BY test_date DESC LIMIT 20");
    $stmt->execute($users);
    $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // В случае ошибки просто продолжаем без списка
    error_log("Ошибка при получении списка участников: " . $e->getMessage());
}

$pageTitle = "Тест реакции на визуальную арифметику";
include_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест на сложную сенсомоторную реакцию:
                        сложение в уме (визуально)</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">Этот тест измеряет скорость вашей реакции на
                        визуальный арифметический стимул. На
                        экране будут появляться пары чисел, которые вам нужно
                        сложить в уме и определить, четная или нечетная
                        получившаяся сумма.</p>

                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите кнопку "Начать тест"</li>
                            <li>На экране будут появляться пары чисел</li>
                            <li>Сложите эти числа в уме</li>
                            <li>Если полученная сумма <strong>ЧЕТНАЯ</strong> -
                                нажмите кнопку "Четное"
                            </li>
                            <li>Если полученная сумма <strong>НЕЧЕТНАЯ</strong>
                                - нажмите кнопку "Нечетное"
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
                        <div id="stimulusArea"
                             class="stimulus-area d-flex align-items-center justify-content-center">
                            <div id="numberDisplay" class="number-display"
                                 style="display: none;"></div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button id="evenButton"
                                        class="btn btn-success btn-lg w-100"
                                        disabled>Четное
                                </button>
                            </div>
                            <div class="col-6">
                                <button id="oddButton"
                                        class="btn btn-danger btn-lg w-100"
                                        disabled>Нечетное
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

    .number-display {
        font-size: 3rem;
        font-weight: bold;
        color: #333;
        text-align: center;
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
        let currentNumber1;
        let currentNumber2;

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

                // Генерация двух случайных чисел от 1 до 50
                currentNumber1 = Math.floor(Math.random() * 50) + 1;
                currentNumber2 = Math.floor(Math.random() * 50) + 1;

                // Отображение чисел
                numberDisplay.innerHTML = `${currentNumber1} + ${currentNumber2} = ?`;
                numberDisplay.style.display = 'block';

                startTime = Date.now();
            }, delay);
        }

        function handleResponse(response) {
            if (numberDisplay.style.display === 'none') {
                // Преждевременная реакция
                clearTimeout(timeoutId);
                results.push({
                    trial_number: currentTrial + 1,
                    number1: null,
                    number2: null,
                    sum: null,
                    response: response,
                    reaction_time: -1,
                    is_correct: false
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
                    trial_number: currentTrial + 1,
                    number1: currentNumber1,
                    number2: currentNumber2,
                    sum: sum,
                    response: response,
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
            evenButton.disabled = true;
            oddButton.disabled = true;

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
                const numbersCell = document.createElement('td');
                const sumCell = document.createElement('td');
                const timeCell = document.createElement('td');
                const correctCell = document.createElement('td');

                trialCell.textContent = result.trial_number;

                if (result.reaction_time < 0) {
                    numbersCell.textContent = 'N/A';
                    sumCell.textContent = 'N/A';
                    timeCell.textContent = 'Преждевременная реакция';
                    correctCell.textContent = 'Нет';
                    row.classList.add('table-danger');
                } else {
                    numbersCell.textContent = `${result.number1} + ${result.number2}`;
                    sumCell.textContent = result.sum;
                    timeCell.textContent = `${result.reaction_time} мс`;
                    correctCell.textContent = result.is_correct ? 'Да' : 'Нет';

                    if (!result.is_correct) {
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
            const urlParams = new URLSearchParams(window.location.search);
            const batchId = urlParams.get('batch_id');

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
