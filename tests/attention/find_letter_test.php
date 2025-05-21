<?php
session_start();
require_once '../../api/config.php'; // Adjust path as needed

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Поиск Буквы (Внимание)";
$testType = 'attention_find_letter'; // Specific test type for this page
$testMainCategory = 'attention'; // Main category for fetching items

include_once '../../includes/header.php'; // Adjust path as needed
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест "Поиск Буквы": Оценка Концентрации Внимания</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Инструкция:</strong> Ниже будет представлен блок текста и целевая буква. Ваша задача — внимательно прочитать текст и сосчитать, сколько раз в нем встречается целевая буква. Введите полученное число в поле ответа.</p>
                    <p class="mb-4">Постарайтесь быть максимально точным. Время на выполнение одной пробы не ограничено, но старайтесь не затягивать.</p>
                    
                    <div class="alert alert-info">
                        Тест начнется после нажатия кнопки "Начать тест". Будет представлено несколько проб (текстовых блоков).
                    </div>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">Начать тест</button>
                    </div>

                    <div id="testArea" class="reaction-test-area mb-4" style="display: none;">
                        <h5 class="mb-3">Задание <span id="trialIndicator">1</span> из <span id="totalTrialsIndicator">N</span></h5>
                        <p><strong>Целевая буква:</strong> <span id="targetLetterDisplay" style="font-size: 1.5rem; font-weight: bold; color: red;"></span></p>
                        <div id="stimulusDisplay" class="stimulus-area p-3 border bg-light" style="min-height: 150px; font-family: monospace; white-space: pre-wrap; word-break: break-all;">
                            <!-- Text block will appear here -->
                        </div>
                        <div class="mt-3">
                            <label for="userCountInput" class="form-label"><strong>Ваш ответ (количество):</strong></label>
                            <input type="number" id="userCountInput" class="form-control form-control-lg w-50" min="0">
                        </div>
                        <button id="nextTrialButton" class="btn btn-success mt-3">Следующее задание</button>
                    </div>

                    <div id="progressContainer" class="mb-3"></div>

                    <div id="resultsContainer" style="display: none;">
                        <h5>Результаты Теста "Поиск Буквы":</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Проба</th>
                                    <th>Целевая буква</th>
                                    <th>Ваш ответ</th>
                                    <th>Правильный ответ</th>
                                    <th>Точность (отклонение)</th>
                                </tr>
                            </thead>
                            <tbody id="resultsTableBody"></tbody>
                        </table>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info"><strong>Общая точность (процент правильных ответов):</strong> <span id="accuracy">0</span>%</div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <button id="saveResultsButton" class="btn btn-success">Сохранить результаты</button>
                            <a href="/tests/index.php" class="btn btn-outline-primary">Вернуться к списку тестов</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const startButton = document.getElementById('startButton');
    const testArea = document.getElementById('testArea');
    const stimulusDisplay = document.getElementById('stimulusDisplay');
    const targetLetterDisplay = document.getElementById('targetLetterDisplay');
    const userCountInput = document.getElementById('userCountInput');
    const nextTrialButton = document.getElementById('nextTrialButton');
    const trialIndicator = document.getElementById('trialIndicator');
    const totalTrialsIndicator = document.getElementById('totalTrialsIndicator');

    const resultsContainer = document.getElementById('resultsContainer');
    const resultsTableBody = document.getElementById('resultsTableBody');
    const accuracyDisplay = document.getElementById('accuracy');
    const saveResultsButton = document.getElementById('saveResultsButton');
    const progressContainer = document.getElementById('progressContainer');

    const testType = '<?php echo $testType; ?>';
    const testMainCategory = '<?php echo $testMainCategory; ?>';
    let testItems = [];
    let currentTrial = 0;
    let totalTrials = 0;
    let trialResults = [];
    let progressBar;

    async function fetchTestItems() {
        try {
            const response = await fetch(`../../api/get_cognitive_test_items.php?test_main_category=${testMainCategory}`);
            const data = await response.json();
            if (data.success && data.tests) {
                const findLetterTestData = data.tests.find(t => t.test_type === testType);
                if (findLetterTestData && findLetterTestData.items) {
                    testItems = findLetterTestData.items.map(item => {
                        // Assuming item_content is { text_block: "AZBY...", target_letter: "A", actual_count: 5 }
                        return {
                            textBlock: item.item_content.text_block,
                            targetLetter: item.item_content.target_letter,
                            actualCount: parseInt(item.item_content.actual_count, 10)
                        };
                    });
                    totalTrials = testItems.length;
                    if (totalTrials > 0) {
                        progressBar = TestProgress.initTrialProgressBar(progressContainer, totalTrials);
                        return true;
                    }
                }
            }
            console.error('Failed to fetch or parse Find Letter test items:', data);
            alert('Не удалось загрузить задания для теста "Поиск Буквы". Убедитесь, что задания есть в базе данных cognitive_test_items.');
            return false;
        } catch (error) {
            console.error('Error fetching test items:', error);
            alert('Ошибка при загрузке заданий.');
            return false;
        }
    }

    startButton.addEventListener('click', async () => {
        const itemsLoaded = await fetchTestItems();
        if (itemsLoaded) {
            startButton.style.display = 'none';
            document.querySelector('.alert-info').style.display = 'none';
            testArea.style.display = 'block';
            resultsContainer.style.display = 'none';
            resultsTableBody.innerHTML = '';
            trialResults = [];
            currentTrial = 0;
            progressBar.setVisible(true);
            progressBar.updateTrial(0);
            totalTrialsIndicator.textContent = totalTrials;
            nextTrialButton.textContent = currentTrial === totalTrials -1 ? "Завершить тест" : "Следующее задание";
            nextTrial();
        }
    });

    function nextTrial() {
        if (currentTrial >= totalTrials) {
            endTest();
            return;
        }
        progressBar.updateTrial(currentTrial);
        trialIndicator.textContent = currentTrial + 1;
        const item = testItems[currentTrial];
        stimulusDisplay.textContent = item.textBlock;
        targetLetterDisplay.textContent = item.targetLetter;
        userCountInput.value = '';
        userCountInput.focus();
        nextTrialButton.disabled = false;
        if(currentTrial === totalTrials - 1) {
            nextTrialButton.textContent = "Завершить тест";
        }
    }

    nextTrialButton.addEventListener('click', () => {
        const userAnswer = parseInt(userCountInput.value, 10);
        if (isNaN(userAnswer) || userAnswer < 0) {
            alert('Пожалуйста, введите корректное число (0 или больше).');
            return;
        }
        nextTrialButton.disabled = true; // Prevent double clicks

        const currentItem = testItems[currentTrial];
        const isCorrect = userAnswer === currentItem.actualCount;
        const deviation = userAnswer - currentItem.actualCount;

        trialResults.push({
            trial: currentTrial + 1,
            targetLetter: currentItem.targetLetter,
            userAnswer: userAnswer,
            actualAnswer: currentItem.actualCount,
            deviation: deviation,
            isCorrect: isCorrect
        });

        currentTrial++;
        if (currentTrial < totalTrials) {
            nextTrial();
        } else {
            endTest();
        }
    });

    function endTest() {
        testArea.style.display = 'none';
        progressBar.setVisible(false);
        resultsContainer.style.display = 'block';

        let correctCount = 0;

        trialResults.forEach(res => {
            const row = resultsTableBody.insertRow();
            row.insertCell().textContent = res.trial;
            row.insertCell().textContent = res.targetLetter;
            row.insertCell().textContent = res.userAnswer;
            row.insertCell().textContent = res.actualAnswer;
            row.insertCell().textContent = res.deviation;
            row.classList.add(res.isCorrect ? 'table-success' : 'table-danger');
            if (res.isCorrect) {
                correctCount++;
            }
        });

        const accPercentage = totalTrials > 0 ? ((correctCount / totalTrials) * 100).toFixed(1) : 0;
        accuracyDisplay.textContent = accPercentage;
    }

    saveResultsButton.addEventListener('click', async () => {
        const finalAccuracy = parseFloat(accuracyDisplay.textContent);

        // For this test, `average_time` is not directly measured per trial by the script.
        // `accuracy` is the primary score.
        const dataToSend = {
            test_type: testType,
            results: trialResults.map(r => ({
                trial_number: r.trial,
                stimulus_value: JSON.stringify({target: r.targetLetter, text_length: testItems[r.trial-1].textBlock.length }),
                response_value: r.userAnswer.toString(),
                reaction_time: null, // Not measured per trial in this version
                is_correct: r.isCorrect
            })),
            accuracy: finalAccuracy,
            average_time: null, 
            custom_score: finalAccuracy, // Using accuracy as the custom score
            higher_is_better_custom_score: true,
            // Batch ID if part of a batch - get from URL query param if exists
            batch_id: new URLSearchParams(window.location.search).get('batch_id')
        };

        try {
            const response = await fetch('../../api/save_test_results.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                alert('Результаты успешно сохранены!');
                const batchId = new URLSearchParams(window.location.search).get('batch_id');
                if (batchId) {
                    window.location.href = `/tests/test_batch.php?batch_id=${batchId}`;
                } else {
                    window.location.href = '/tests/my_results.php'; 
                }
            } else {
                alert('Ошибка при сохранении результатов: ' + result.message);
            }
        } catch (error) {
            console.error('Error saving results:', error);
            alert('Произошла ошибка при сохранении результатов.');
        }
    });
});
</script>

<?php
include_once '../../includes/footer.php'; // Adjust path as needed
?>
