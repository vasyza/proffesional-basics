<?php
session_start();
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}
$pageTitle = "Тест: Таблицы Шульте";
include_once '../../includes/header.php';

$respondents = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT login FROM users WHERE ispublic = TRUE");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($users) > 0) {
        $placeholders = implode(', ', array_fill(0, count($users), '?'));
        $stmt = $pdo->prepare("SELECT user_name, test_date FROM schulte_table_respondents WHERE user_name IN (" . $placeholders . ")  ORDER BY test_date DESC LIMIT 20");
        $stmt->execute($users);
        $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Ошибка при получении списка участников (schulte_table): " . $e->getMessage());
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $pageTitle; ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Этот тест предназначен для оценки объема и
                        устойчивости внимания. Вам будет предложено несколько
                        таблиц с числами. Ваша задача - как можно быстрее найти
                        и указать все числа по порядку от 1 до 25.</p>
                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите "Начать тест".</li>
                            <li>На экране появится таблица 5x5 с числами от 1 до
                                25 в случайном порядке.
                            </li>
                            <li>Начиная с 1, последовательно кликайте на числа
                                до 25.
                            </li>
                            <li>Время выполнения для каждой таблицы будет
                                зафиксировано.
                            </li>
                            <li>Тест состоит из 3 таблиц.</li>
                        </ol>
                    </div>

                    <div class="text-center mb-3">
                        <button id="startButton"
                                class="btn btn-primary btn-lg px-4">Начать тест
                        </button>
                    </div>

                    <div id="testAreaContainer" style="display:none;">
                        <div id="schulteTable" class="mb-3"
                             style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; max-width: 300px; margin: auto; border: 1px solid #ccc; padding: 10px;">
                        </div>
                        <div id="currentNumberDisplay"
                             class="text-center h5 mb-2">Искать: <span
                                    id="targetNumber">1</span></div>
                        <div id="tableTimerDisplay"
                             class="text-center text-muted mb-3">Время на
                            таблицу: 0.0с
                        </div>
                        <div id="progressContainer" class="mt-3"></div>
                    </div>

                    <div id="resultsDisplay" class="mt-4" style="display:none;">
                        <h4>Результаты теста "Таблицы Шульте"</h4>
                        <ul id="tableResultsList"></ul>
                        <p><strong>Среднее время на таблицу:</strong> <span
                                    id="avgTimePerTable"></span> сек.</p>
                        <p><strong>Общее время выполнения:</strong> <span
                                    id="totalTimeForAllTables"></span> сек.</p>
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
                            проходил этот тест.
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
        const testAreaContainer = document.getElementById('testAreaContainer');
        const schulteTableElement = document.getElementById('schulteTable');
        const targetNumberElement = document.getElementById('targetNumber');
        const tableTimerDisplay = document.getElementById('tableTimerDisplay');
        const resultsDisplay = document.getElementById('resultsDisplay');
        const tableResultsList = document.getElementById('tableResultsList');
        const avgTimePerTableElement = document.getElementById('avgTimePerTable');
        const totalTimeForAllTablesElement = document.getElementById('totalTimeForAllTables');
        const saveResultsButton = document.getElementById('saveResultsButton');
        const restartTestButton = document.getElementById('restartTestButton');

        const TOTAL_TABLES = 3;
        const TABLE_SIZE = 25;
        let currentTableNumber = 0;
        let currentNumberToFind = 1;
        let tableStartTime;
        let tableTimerInterval;
        let results = []; // To store time for each table
        let progressBar;

        startButton.addEventListener('click', startFullTest);
        restartTestButton.addEventListener('click', startFullTest);
        saveResultsButton.addEventListener('click', saveTestResults);

        function startFullTest() {
            currentTableNumber = 0;
            results = [];
            resultsDisplay.style.display = 'none';
            tableResultsList.innerHTML = '';
            startButton.style.display = 'none';
            testAreaContainer.style.display = 'block';
            if (!progressBar) {
                progressBar = TestProgress.initTrialProgressBar('progressContainer', TOTAL_TABLES);
            }
            progressBar.updateTrial(0);
            progressBar.setVisible(true);
            startNewTable();
        }

        function startNewTable() {
            currentTableNumber++;
            if (currentTableNumber > TOTAL_TABLES) {
                endFullTest();
                return;
            }
            progressBar.updateTrial(currentTableNumber - 1);
            currentNumberToFind = 1;
            targetNumberElement.textContent = currentNumberToFind;
            generateTable();
            tableStartTime = Date.now();
            tableTimerDisplay.textContent = "Время на таблицу: 0.0с";
            clearInterval(tableTimerInterval);
            tableTimerInterval = setInterval(() => {
                const elapsedTime = ((Date.now() - tableStartTime) / 1000).toFixed(1);
                tableTimerDisplay.textContent = `Время на таблицу: ${elapsedTime}с`;
            }, 100);
        }

        function generateTable() {
            schulteTableElement.innerHTML = '';
            let numbers = [];
            for (let i = 1; i <= TABLE_SIZE; i++) {
                numbers.push(i);
            }
            numbers.sort(() => Math.random() - 0.5); // Shuffle numbers

            numbers.forEach(num => {
                const cell = document.createElement('div');
                cell.textContent = num;
                cell.style.cssText = "border: 1px solid #ddd; padding: 10px; text-align: center; cursor: pointer; font-size: 1.2em;";
                cell.addEventListener('click', () => handleCellClick(num, cell));
                schulteTableElement.appendChild(cell);
            });
        }

        function handleCellClick(numberClicked, cellElement) {
            if (numberClicked === currentNumberToFind) {
                cellElement.style.backgroundColor = '#a0e8a0'; // Light green for correct
                cellElement.style.pointerEvents = 'none'; // Disable further clicks
                currentNumberToFind++;
                targetNumberElement.textContent = currentNumberToFind;

                if (currentNumberToFind > TABLE_SIZE) {
                    const tableTime = (Date.now() - tableStartTime) / 1000;
                    results.push({
                        trial_number: currentTableNumber,
                        reaction_time: tableTime * 1000,
                        is_correct: 1,
                        stimulus_value: `table_${currentTableNumber}`
                    });
                    clearInterval(tableTimerInterval);
                    tableTimerDisplay.textContent = `Время на таблицу: ${tableTime.toFixed(1)}с (Завершено)`;
                    progressBar.updateTrial(currentTableNumber);
                    setTimeout(startNewTable, 1000); // Start next table after a short delay
                }
            } else {
                // Optional: visual feedback for wrong click, e.g., flash red
                cellElement.style.backgroundColor = '#f8d7da'; // Light red for incorrect
                setTimeout(() => {
                    cellElement.style.backgroundColor = '';
                }, 200);
            }
        }

        function endFullTest() {
            testAreaContainer.style.display = 'none';
            progressBar.setVisible(false);
            resultsDisplay.style.display = 'block';

            let totalTime = 0;
            results.forEach((result, index) => {
                const listItem = document.createElement('li');
                listItem.textContent = `Таблица ${index + 1}: ${(result.reaction_time / 1000).toFixed(1)} сек.`;
                tableResultsList.appendChild(listItem);
                totalTime += (result.reaction_time / 1000);
            });

            const avgTime = results.length > 0 ? (totalTime / results.length).toFixed(1) : "N/A";
            avgTimePerTableElement.textContent = avgTime;
            totalTimeForAllTablesElement.textContent = totalTime.toFixed(1);
            startButton.style.display = 'block';
        }

        function saveTestResults() {
            const totalTime = results.reduce((sum, r) => sum + r.reaction_time, 0); // in ms
            const averageTableTime = results.length > 0 ? totalTime / results.length : null; // in ms

            const dataToSend = {
                test_type: 'schulte_table',
                average_time: averageTableTime, // Average time per table
                accuracy: 100, // Assuming 100% accuracy if all tables are completed
                results: results.map((r, index) => ({
                    trial_number: index + 1,
                    stimulus_value: `table_${index + 1}`,
                    reaction_time: r.reaction_time, // Time for this table in ms
                    is_correct: 1 // Marking as correct completion of a table
                })),
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
                        resultsDisplay.style.display = 'none'; // Hide results after saving
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
    });
</script>
<?php include_once '../../includes/footer.php'; ?>
