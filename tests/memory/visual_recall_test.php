<?php
session_start();
require_once '../../api/config.php'; // Adjust path as needed

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$pageTitle = "Визуальное Запоминание (Память)";
$testType = 'memory_visual_recall'; // Specific test type
$testMainCategory = 'memory';     // Main category for fetching items

include_once '../../includes/header.php'; // Adjust path as needed
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Тест "Визуальное Запоминание": Оценка Зрительной Кратковременной Памяти</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Инструкция:</strong> Сначала вам будет показан набор изображений на короткое время. Постарайтесь запомнить их.</p>
                    <p class="mb-2">Затем вам будет представлен более широкий набор изображений. Ваша задача — выбрать только те изображения, которые были в первоначальном наборе.</p>
                    <p class="mb-4">Тест начнется после нажатия кнопки "Начать тест". Будет несколько таких проб.</p>

                    <div class="text-center mb-4">
                        <button id="startButton" class="btn btn-primary btn-lg">Начать тест</button>
                    </div>

                    <div id="testArea" class="reaction-test-area mb-4" style="display: none;">
                        <h5 class="mb-3">Задание <span id="trialIndicator">1</span> из <span id="totalTrialsIndicator">N</span></h5>
                        
                        <!-- Phase 1: Memorization -->
                        <div id="memorizationPhase" style="display: none;">
                            <p class="text-center">Запомните эти изображения:</p>
                            <div id="initialImagesDisplay" class="d-flex flex-wrap justify-content-center align-items-center" style="min-height: 200px; border: 1px solid #eee; padding: 10px;">
                                <!-- Initial images appear here -->
                            </div>
                        </div>

                        <!-- Phase 2: Recognition -->
                        <div id="recognitionPhase" style="display: none;">
                            <p class="text-center">Выберите изображения, которые вы видели в предыдущем наборе:</p>
                            <div id="recognitionSetDisplay" class="d-flex flex-wrap justify-content-center align-items-center" style="min-height: 200px; border: 1px solid #eee; padding: 10px;">
                                <!-- Recognition set appears here as clickable images -->
                            </div>
                            <div class="text-center mt-3">
                                <button id="submitRecognitionButton" class="btn btn-success">Подтвердить выбор</button>
                            </div>
                        </div>
                    </div>

                    <div id="progressContainer" class="mb-3"></div>

                    <div id="resultsContainer" style="display: none;">
                        <h5>Результаты Теста "Визуальное Запоминание":</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Проба</th>
                                    <th>Правильно выбрано (Хиты)</th>
                                    <th>Пропущено</th>
                                    <th>Ложные тревоги</th>
                                    <th>Оценка</th>
                                </tr>
                            </thead>
                            <tbody id="resultsTableBody"></tbody>
                        </table>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="alert alert-info"><strong>Общая точность (средняя оценка):</strong> <span id="overallAccuracy">0</span>%</div>
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

<style>
.recognition-image-container {
    margin: 5px;
    border: 2px solid transparent;
    cursor: pointer;
}
.recognition-image-container.selected {
    border-color: blue;
}
.recognition-image-container img {
    width: 100px; height: 100px; object-fit: contain; display: block;
}
#initialImagesDisplay img {
    width: 100px; height: 100px; object-fit: contain; margin: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const startButton = document.getElementById('startButton');
    const testArea = document.getElementById('testArea');
    const memorizationPhaseDiv = document.getElementById('memorizationPhase');
    const initialImagesDisplay = document.getElementById('initialImagesDisplay');
    const recognitionPhaseDiv = document.getElementById('recognitionPhase');
    const recognitionSetDisplay = document.getElementById('recognitionSetDisplay');
    const submitRecognitionButton = document.getElementById('submitRecognitionButton');
    const trialIndicator = document.getElementById('trialIndicator');
    const totalTrialsIndicator = document.getElementById('totalTrialsIndicator');

    const resultsContainer = document.getElementById('resultsContainer');
    const resultsTableBody = document.getElementById('resultsTableBody');
    const overallAccuracyDisplay = document.getElementById('overallAccuracy');
    const saveResultsButton = document.getElementById('saveResultsButton');
    const progressContainer = document.getElementById('progressContainer');

    const testType = '<?php echo $testType; ?>';
    const testMainCategory = '<?php echo $testMainCategory; ?>';
    let testItems = [];
    let currentTrial = 0;
    let totalTrials = 0;
    let trialResultsData = []; // Renamed from trialResults to avoid conflict
    let progressBar;
    let currentItem;

    async function fetchTestItems() {
        try {
            const response = await fetch(`../../api/get_cognitive_test_items.php?test_main_category=${testMainCategory}`);
            const data = await response.json();
            if (data.success && data.tests) {
                const visualTestData = data.tests.find(t => t.test_type === testType);
                if (visualTestData && visualTestData.items && visualTestData.items.length > 0) {
                    testItems = visualTestData.items.map(item => item.item_content); 
                    // item_content: { initial_images: [], recognition_set: [{id, src}], correct_ids: [], presentation_time_ms, image_base_path }
                    totalTrials = testItems.length;
                    progressBar = TestProgress.initTrialProgressBar(progressContainer, totalTrials);
                    return true;
                }
            }
            console.error('Failed to fetch or parse Visual Recall test items:', data);
            alert('Не удалось загрузить задания. Убедитесь, что задания для memory_visual_recall есть в cognitive_test_items.');
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
            document.querySelector('.alert.alert-info').style.display = 'none'; // Hide initial instructions
            testArea.style.display = 'block';
            resultsContainer.style.display = 'none';
            resultsTableBody.innerHTML = '';
            trialResultsData = [];
            currentTrial = 0;
            progressBar.setVisible(true);
            progressBar.updateTrial(0);
            totalTrialsIndicator.textContent = totalTrials;
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
        currentItem = testItems[currentTrial];
        
        initialImagesDisplay.innerHTML = '';
        currentItem.initial_images.forEach(imgName => {
            const img = document.createElement('img');
            img.src = (currentItem.image_base_path || '') + imgName;
            img.alt = 'Image to remember';
            initialImagesDisplay.appendChild(img);
        });
        
        recognitionPhaseDiv.style.display = 'none';
        memorizationPhaseDiv.style.display = 'block';

        setTimeout(() => {
            memorizationPhaseDiv.style.display = 'none';
            startRecognitionPhase();
        }, currentItem.presentation_time_ms || 5000);
    }

    function startRecognitionPhase() {
        recognitionSetDisplay.innerHTML = '';
        currentItem.recognition_set.forEach(imgData => {
            const container = document.createElement('div');
            container.className = 'recognition-image-container';
            container.dataset.imageId = imgData.id;
            const img = document.createElement('img');
            img.src = (currentItem.image_base_path || '') + imgData.src;
            img.alt = imgData.id;
            container.appendChild(img);
            container.addEventListener('click', () => {
                container.classList.toggle('selected');
            });
            recognitionSetDisplay.appendChild(container);
        });
        recognitionPhaseDiv.style.display = 'block';
    }

    submitRecognitionButton.addEventListener('click', () => {
        const selectedImages = Array.from(recognitionSetDisplay.querySelectorAll('.recognition-image-container.selected'))
                                   .map(el => el.dataset.imageId);
        
        const correctOriginals = currentItem.correct_ids;
        let hits = 0;
        let falseAlarms = 0;

        selectedImages.forEach(selectedId => {
            if (correctOriginals.includes(selectedId)) {
                hits++;
            } else {
                falseAlarms++;
            }
        });
        const misses = correctOriginals.length - hits;
        
        let score = 0;
        if (correctOriginals.length > 0) {
             score = Math.max(0, (hits - falseAlarms)) / correctOriginals.length;
        }
        score = parseFloat((score * 100).toFixed(1));

        trialResultsData.push({
            trial: currentTrial + 1,
            hits: hits,
            misses: misses,
            falseAlarms: falseAlarms,
            score: score,
            stimulus_value: JSON.stringify({ initial_count: correctOriginals.length, recognition_count: currentItem.recognition_set.length }),
            response_value: JSON.stringify(selectedImages)
        });

        currentTrial++;
        // The line below was causing an error as nextTrialButton is not defined in this file.
        // nextTrialButton.textContent = currentTrial === totalTrials -1 ? "Завершить тест" : "Следующее задание";
        nextTrial();
    });

    function endTest() {
        testArea.style.display = 'none';
        progressBar.setVisible(false);
        resultsContainer.style.display = 'block';
        let totalScoreSum = 0;

        trialResultsData.forEach(res => {
            const row = resultsTableBody.insertRow();
            row.insertCell().textContent = res.trial;
            row.insertCell().textContent = res.hits;
            row.insertCell().textContent = res.misses;
            row.insertCell().textContent = res.falseAlarms;
            row.insertCell().textContent = res.score.toFixed(1) + '%';
            totalScoreSum += res.score;
        });
        const overallAvgScore = totalTrials > 0 ? (totalScoreSum / totalTrials).toFixed(1) : 0;
        overallAccuracyDisplay.textContent = overallAvgScore + '%';
    }

    saveResultsButton.addEventListener('click', async () => {
        const finalOverallScore = parseFloat(overallAccuracyDisplay.textContent);

        const dataToSend = {
            test_type: testType,
            results: trialResultsData.map(r => ({
                trial_number: r.trial,
                stimulus_value: r.stimulus_value,
                response_value: r.response_value,
                reaction_time: null, 
                is_correct: r.score > 50 
            })),
            accuracy: null, 
            average_time: null, 
            custom_score: finalOverallScore,
            higher_is_better_custom_score: true,
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
