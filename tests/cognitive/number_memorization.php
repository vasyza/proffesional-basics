<?php
session_start();
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}
$pageTitle = "Тест: Запоминание чисел";
include_once '../../includes/header.php';

$respondents = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT login FROM users WHERE ispublic = TRUE");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($users) > 0) {
        $placeholders = implode(', ', array_fill(0, count($users), '?'));
        $stmt = $pdo->prepare("SELECT user_name, test_date FROM number_memorization_respondents WHERE user_name IN (" . $placeholders . ")  ORDER BY test_date DESC LIMIT 20");
        $stmt->execute($users);
        $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Ошибка при получении списка участников (number_memorization): " . $e->getMessage());
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
                    <p class="mb-3">Этот тест оценивает вашу кратковременную
                        зрительную память. Вам будут показаны последовательности
                        чисел, которые нужно запомнить и воспроизвести.</p>
                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите "Начать тест".</li>
                            <li>На экране на короткое время появится
                                последовательность цифр. Запомните её.
                            </li>
                            <li>После того как цифры исчезнут, введите их в том
                                же порядке в поле ввода.
                            </li>
                            <li>Нажмите "Проверить". Тест состоит из нескольких
                                попыток с разной длиной последовательности.
                            </li>
                        </ol>
                    </div>

                    <div id="settingsArea">
                        <label for="startLength">Начальная длина
                            последовательности:</label>
                        <input type="number" id="startLength" value="3" min="2"
                               max="5" class="form-control mb-2">
                        <label for="maxLength">Максимальная длина
                            последовательности:</label>
                        <input type="number" id="maxLength" value="7" min="3"
                               max="10" class="form-control mb-3">
                        <button id="startButton"
                                class="btn btn-primary btn-lg px-4">Начать тест
                        </button>
                    </div>

                    <div id="testAreaContainer" style="display:none;">
                        <div id="numberSequenceDisplay"
                             class="h1 text-center my-4"
                             style="min-height: 50px;"></div>
                        <div id="inputArea" style="display:none;">
                            <input type="text" id="responseInput"
                                   class="form-control form-control-lg mb-2"
                                   placeholder="Введите числа">
                            <button id="submitResponseButton"
                                    class="btn btn-success w-100">Проверить
                            </button>
                        </div>
                        <div id="feedbackArea" class="text-center mt-2"></div>
                        <div id="progressContainer" class="mt-3"></div>
                    </div>

                    <div id="resultsDisplay" class="mt-4" style="display:none;">
                        <h4>Результаты теста "Запоминание чисел"</h4>
                        <p><strong>Максимальная правильно запомненная
                                длина:</strong> <span
                                    id="maxCorrectLength"></span></p>
                        <p><strong>Общая точность:</strong> <span
                                    id="overallAccuracy"></span>%</p>
                        <ul id="attemptResultsList" class="list-group"></ul>
                        <button id="saveResultsButton"
                                class="btn btn-success mt-3">Сохранить
                            результаты
                        </button>
                        <button id="restartTestButton"
                                class="btn btn-info ms-2 mt-3">Пройти еще раз
                        </button>
                        <a href="/tests/index.php"
                           class="btn btn-outline-secondary ms-2 mt-3">К списку
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
        const settingsArea = document.getElementById('settingsArea');
        const testAreaContainer = document.getElementById('testAreaContainer');
        const numberSequenceDisplay = document.getElementById('numberSequenceDisplay');
        const inputArea = document.getElementById('inputArea');
        const responseInput = document.getElementById('responseInput');
        const submitResponseButton = document.getElementById('submitResponseButton');
        const feedbackArea = document.getElementById('feedbackArea');
        const resultsDisplay = document.getElementById('resultsDisplay');
        const maxCorrectLengthElement = document.getElementById('maxCorrectLength');
        const overallAccuracyElement = document.getElementById('overallAccuracy');
        const attemptResultsList = document.getElementById('attemptResultsList');
        const saveResultsButton = document.getElementById('saveResultsButton');
        const restartTestButton = document.getElementById('restartTestButton');

        let currentLength;
        let startSequenceLength = 3;
        let maxSequenceLength = 7;
        const TRIALS_PER_LENGTH = 2; // 2 попытки на каждую длину
        let currentTrial = 0;
        let totalTrialsForProgress;
        let sequenceToRemember;
        let results = [];
        let maxCorrectlyRememberedLength = 0;
        let totalCorrectAttempts = 0;
        let totalAttempts = 0;
        let progressBar;

        startButton.addEventListener('click', startFullTest);
        submitResponseButton.addEventListener('click', checkResponse);
        responseInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') checkResponse();
        });
        restartTestButton.addEventListener('click', startFullTest);
        saveResultsButton.addEventListener('click', saveTestResults);

        function startFullTest() {
            startSequenceLength = parseInt(document.getElementById('startLength').value) || 3;
            maxSequenceLength = parseInt(document.getElementById('maxLength').value) || 7;
            if (startSequenceLength > maxSequenceLength) {
                alert("Начальная длина не может быть больше максимальной.");
                return;
            }

            currentLength = startSequenceLength;
            currentTrial = 0; // Сброс для каждой новой длины
            totalAttempts = 0; // Общее количество предъявлений
            totalCorrectAttempts = 0;
            maxCorrectlyRememberedLength = 0;
            results = [];

            totalTrialsForProgress = (maxSequenceLength - startSequenceLength + 1) * TRIALS_PER_LENGTH;
            if (!progressBar) {
                progressBar = TestProgress.initTrialProgressBar('progressContainer', totalTrialsForProgress);
            } else {
                progressBar.updateTrial(0); // Reset progress for the new set of trials
            }
            progressBar.setVisible(true);

            settingsArea.style.display = 'none';
            resultsDisplay.style.display = 'none';
            attemptResultsList.innerHTML = '';
            testAreaContainer.style.display = 'block';
            feedbackArea.textContent = '';
            startButton.style.display = 'none';

            nextSequence();
        }

        function generateSequence(length) {
            let seq = '';
            for (let i = 0; i < length; i++) {
                seq += Math.floor(Math.random() * 10);
            }
            return seq;
        }

        function nextSequence() {
            if (currentLength > maxSequenceLength) {
                endFullTest();
                return;
            }
            if (currentTrial >= TRIALS_PER_LENGTH) {
                currentLength++;
                currentTrial = 0;
                if (currentLength > maxSequenceLength) {
                    endFullTest();
                    return;
                }
            }

            totalAttempts++;
            progressBar.updateTrial(totalAttempts - 1);

            sequenceToRemember = generateSequence(currentLength);
            numberSequenceDisplay.textContent = sequenceToRemember;
            numberSequenceDisplay.style.display = 'block';
            inputArea.style.display = 'none';
            responseInput.value = '';
            feedbackArea.textContent = `Запомните: ${currentLength} цифр`;

            setTimeout(() => {
                numberSequenceDisplay.style.display = 'none';
                feedbackArea.textContent = 'Введите последовательность';
                inputArea.style.display = 'block';
                responseInput.focus();
            }, currentLength * 700 + 500); // Time to display: 0.7s per digit + 0.5s buffer
        }

        function checkResponse() {
            const userAnswer = responseInput.value;
            const isCorrect = userAnswer === sequenceToRemember;

            if (isCorrect) {
                totalCorrectAttempts++;
                if (currentLength > maxCorrectlyRememberedLength) {
                    maxCorrectlyRememberedLength = currentLength;
                }
                feedbackArea.textContent = 'Правильно!';
                feedbackArea.className = 'text-center mt-2 text-success';
            } else {
                feedbackArea.textContent = `Неправильно. Верная последовательность: ${sequenceToRemember}`;
                feedbackArea.className = 'text-center mt-2 text-danger';
            }

            results.push({
                trial_number: totalAttempts,
                stimulus_value: `length_${currentLength}_trial_${currentTrial + 1}`,
                numbers_shown: sequenceToRemember,
                numbers_recalled: userAnswer,
                is_correct: isCorrect,
                reaction_time: null // Not directly applicable here in the same way as reaction tests
            });

            currentTrial++;
            setTimeout(nextSequence, 1500); // Delay before next sequence
        }

        function endFullTest() {
            testAreaContainer.style.display = 'none';
            progressBar.setVisible(false);
            resultsDisplay.style.display = 'block';

            maxCorrectLengthElement.textContent = maxCorrectlyRememberedLength > 0 ? maxCorrectlyRememberedLength : "Не удалось запомнить ни одной последовательности";
            const accuracyPercentage = totalAttempts > 0 ? ((totalCorrectAttempts / totalAttempts) * 100).toFixed(1) : 0;
            overallAccuracyElement.textContent = `${accuracyPercentage}%`;

            results.forEach(res => {
                const listItem = document.createElement('li');
                listItem.className = `list-group-item ${res.is_correct ? 'list-group-item-success' : 'list-group-item-danger'}`;
                listItem.textContent = `Попытка ${res.trial_number}: Показано - ${res.numbers_shown}, Введено - ${res.numbers_recalled} (${res.is_correct ? 'Верно' : 'Ошибка'})`;
                attemptResultsList.appendChild(listItem);
            });
            startButton.style.display = 'block';
            settingsArea.style.display = 'block';
        }

        function saveTestResults() {
            const accuracyPercentage = totalAttempts > 0 ? parseFloat(((totalCorrectAttempts / totalAttempts) * 100).toFixed(1)) : 0;
            // 'average_time' isn't directly applicable. We can send maxCorrectlyRememberedLength or null.
            // Or, we can calculate average time per correct digit if we had timing for input.
            // For simplicity, we'll send max length as 'average_time' for now, or null.
            const representativeMetric = maxCorrectlyRememberedLength > 0 ? maxCorrectlyRememberedLength : null;

            const dataToSend = {
                test_type: 'number_memorization',
                average_time: representativeMetric,
                accuracy: accuracyPercentage,
                results: results.map(r => ({
                    trial_number: r.trial_number,
                    stimulus_value: r.stimulus_value, // Or JSON.stringify({shown: r.numbers_shown})
                    response_value: r.numbers_recalled,
                    is_correct: r.is_correct,
                    reaction_time: r.reaction_time // Will be null for this test as designed
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
    });
</script>
<?php include_once '../../includes/footer.php'; ?>
