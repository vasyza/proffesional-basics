<?php
session_start();
require_once '../../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}
$pageTitle = "Тест: Аналогии";
include_once '../../includes/header.php';

$respondents = [];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT login FROM users WHERE ispublic = TRUE");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($users) > 0) {
        $placeholders = implode(', ', array_fill(0, count($users), '?'));
        $stmt = $pdo->prepare("SELECT user_name, test_date FROM analogies_test_respondents WHERE user_name IN (" . $placeholders . ")  ORDER BY test_date DESC LIMIT 20");
        $stmt->execute($users);
        $respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Ошибка при получении списка участников (analogies_test): " . $e->getMessage());
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
                    <p class="mb-3">Этот тест оценивает вашу способность
                        находить логические связи и аналогии между парами слов.
                        Вам будет дана пара слов, связанных определенным
                        образом, и одно слово из второй пары. Ваша задача —
                        подобрать четвертое слово так, чтобы оно образовывало
                        аналогичную связь со третьим словом.</p>
                    <div class="alert alert-info">
                        <strong>Инструкция:</strong>
                        <ol>
                            <li>Нажмите "Начать тест".</li>
                            <li>Вам будет представлена задача в формате: "A : B
                                :: C : ?".
                            </li>
                            <li>Выберите из предложенных вариантов тот, который
                                наилучшим образом завершает аналогию.
                            </li>
                            <li>Тест состоит из 10 заданий.</li>
                        </ol>
                    </div>

                    <div class="text-center mb-3">
                        <button id="startButton"
                                class="btn btn-primary btn-lg px-4">Начать тест
                        </button>
                    </div>

                    <div id="testAreaContainer" style="display:none;">
                        <div id="analogyQuestionDisplay"
                             class="h4 text-center my-4"></div>
                        <div id="optionsArea" class="list-group mb-3">
                        </div>
                        <div id="feedbackArea" class="text-center mt-2"></div>
                        <div id="progressContainer" class="mt-3"></div>
                    </div>

                    <div id="resultsDisplay" class="mt-4" style="display:none;">
                        <h4>Результаты теста "Аналогии"</h4>
                        <p><strong>Количество правильных ответов:</strong> <span
                                    id="correctAnswersCount"></span> из 10</p>
                        <p><strong>Точность:</strong> <span
                                    id="accuracyPercentage"></span>%</p>
                        <p><strong>Среднее время на ответ:</strong> <span
                                    id="avgTimePerAnalogy"></span> сек.</p>
                        <ul id="detailedResultsList" class="list-group"></ul>
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
        const testAreaContainer = document.getElementById('testAreaContainer');
        const analogyQuestionDisplay = document.getElementById('analogyQuestionDisplay');
        const optionsArea = document.getElementById('optionsArea');
        const feedbackArea = document.getElementById('feedbackArea');
        const resultsDisplay = document.getElementById('resultsDisplay');
        const correctAnswersCountElement = document.getElementById('correctAnswersCount');
        const accuracyPercentageElement = document.getElementById('accuracyPercentage');
        const avgTimePerAnalogyElement = document.getElementById('avgTimePerAnalogy');
        const detailedResultsList = document.getElementById('detailedResultsList');
        const saveResultsButton = document.getElementById('saveResultsButton');
        const restartTestButton = document.getElementById('restartTestButton');

        const analogies = [
            // Простые (Тип связи: Синонимы/Антонимы, Часть-целое, Причина-следствие)
            {
                a: "Большой",
                b: "Огромный",
                c: "Маленький",
                options: ["Крошечный", "Высокий", "Мокрый", "Синий"],
                answer: "Крошечный",
                type: "синоним-градация"
            },
            {
                a: "День",
                b: "Ночь",
                c: "Свет",
                options: ["Тьма", "Солнце", "Утро", "Звезда"],
                answer: "Тьма",
                type: "антоним"
            },
            {
                a: "Колесо",
                b: "Машина",
                c: "Крыло",
                options: ["Птица", "Дом", "Лодка", "Дерево"],
                answer: "Птица",
                type: "часть-целое"
            },
            {
                a: "Огонь",
                b: "Дым",
                c: "Дождь",
                options: ["Лужа", "Снег", "Ветер", "Солнце"],
                answer: "Лужа",
                type: "причина-следствие"
            },

            // Средние (Тип связи: Инструмент-действие, Объект-свойство, Категория-элемент)
            {
                a: "Молоток",
                b: "Забивать",
                c: "Ножницы",
                options: ["Резать", "Писать", "Читать", "Смотреть"],
                answer: "Резать",
                type: "инструмент-действие"
            },
            {
                a: "Лимон",
                b: "Кислый",
                c: "Сахар",
                options: ["Сладкий", "Горький", "Соленый", "Острый"],
                answer: "Сладкий",
                type: "объект-свойство"
            },
            {
                a: "Собака",
                b: "Животное",
                c: "Роза",
                options: ["Цветок", "Дерево", "Камень", "Вода"],
                answer: "Цветок",
                type: "элемент-категория"
            },

            // Сложные (Более абстрактные связи, менее очевидные)
            {
                a: "Книга",
                b: "Знание",
                c: "Компас",
                options: ["Направление", "Путешествие", "Море", "Карта"],
                answer: "Направление",
                type: "объект-назначение"
            },
            {
                a: "Писатель",
                b: "Роман",
                c: "Скульптор",
                options: ["Статуя", "Картина", "Музыка", "Стих"],
                answer: "Статуя",
                type: "деятель-продукт"
            },
            {
                a: "Голод",
                b: "Еда",
                c: "Жажда",
                options: ["Вода", "Сон", "Работа", "Отдых"],
                answer: "Вода",
                type: "потребность-удовлетворение"
            }
        ];

        let currentAnalogyIndex = 0;
        const TOTAL_ANALOGIES = 10; // Покажем 10 аналогий, можно больше если их много
        let userResults = [];
        let analogyStartTime;
        let progressBar;

        startButton.addEventListener('click', startFullTest);
        restartTestButton.addEventListener('click', startFullTest);
        saveResultsButton.addEventListener('click', saveTestResults);

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        function startFullTest() {
            shuffleArray(analogies); // Перемешиваем массив аналогий
            currentAnalogyIndex = 0;
            userResults = [];
            resultsDisplay.style.display = 'none';
            detailedResultsList.innerHTML = '';
            startButton.style.display = 'none';
            testAreaContainer.style.display = 'block';
            feedbackArea.textContent = '';

            if (!progressBar) {
                progressBar = TestProgress.initTrialProgressBar('progressContainer', TOTAL_ANALOGIES);
            }
            progressBar.updateTrial(0);
            progressBar.setVisible(true);

            loadNextAnalogy();
        }

        function loadNextAnalogy() {
            feedbackArea.textContent = '';
            optionsArea.innerHTML = ''; // Clear previous options

            if (currentAnalogyIndex >= TOTAL_ANALOGIES || currentAnalogyIndex >= analogies.length) {
                endFullTest();
                return;
            }

            progressBar.updateTrial(currentAnalogyIndex);

            const current = analogies[currentAnalogyIndex];
            analogyQuestionDisplay.textContent = `${current.a} : ${current.b} :: ${current.c} : ?`;

            const shuffledOptions = [...current.options];
            shuffleArray(shuffledOptions);

            shuffledOptions.forEach(option => {
                const button = document.createElement('button');
                button.className = 'list-group-item list-group-item-action';
                button.textContent = option;
                button.onclick = () => handleOptionClick(option, current.answer, current);
                optionsArea.appendChild(button);
            });
            analogyStartTime = Date.now();
        }

        function handleOptionClick(selectedOption, correctAnswer, currentAnalogyData) {
            const reactionTime = Date.now() - analogyStartTime;
            const isCorrect = selectedOption === correctAnswer;

            userResults.push({
                trial_number: currentAnalogyIndex + 1,
                analogy_pair: {
                    a: currentAnalogyData.a,
                    b: currentAnalogyData.b,
                    c: currentAnalogyData.c,
                    correct_d: correctAnswer,
                    type: currentAnalogyData.type
                },
                stimulus_value: `${currentAnalogyData.a}:${currentAnalogyData.b}::${currentAnalogyData.c}:?`, // Stimulus
                response_value: selectedOption, // User's response
                reaction_time: reactionTime,
                is_correct: isCorrect
            });

            if (isCorrect) {
                feedbackArea.textContent = 'Правильно!';
                feedbackArea.className = 'text-center mt-2 text-success';
            } else {
                feedbackArea.textContent = `Неправильно. Верный ответ: ${correctAnswer}`;
                feedbackArea.className = 'text-center mt-2 text-danger';
            }

            // Disable options after selection
            Array.from(optionsArea.children).forEach(btn => btn.disabled = true);

            currentAnalogyIndex++;
            setTimeout(loadNextAnalogy, 1500); // Delay before next analogy
        }

        function endFullTest() {
            testAreaContainer.style.display = 'none';
            progressBar.setVisible(false);
            resultsDisplay.style.display = 'block';

            const correctCount = userResults.filter(r => r.is_correct).length;
            const accuracy = (correctCount / TOTAL_ANALOGIES) * 100;
            const totalReactionTime = userResults.reduce((sum, r) => sum + r.reaction_time, 0);
            const avgTime = userResults.length > 0 ? (totalReactionTime / userResults.length / 1000).toFixed(1) : "N/A";


            correctAnswersCountElement.textContent = correctCount;
            accuracyPercentageElement.textContent = accuracy.toFixed(1);
            avgTimePerAnalogyElement.textContent = avgTime;

            userResults.forEach(res => {
                const listItem = document.createElement('li');
                listItem.className = `list-group-item ${res.is_correct ? 'list-group-item-success' : 'list-group-item-danger'}`;
                let analogyString = `${res.analogy_pair.a} : ${res.analogy_pair.b} :: ${res.analogy_pair.c} : ${res.response_value}`;
                if (!res.is_correct) {
                    analogyString += ` (Верно: ${res.analogy_pair.correct_d})`;
                }
                listItem.textContent = `Задание ${res.trial_number}: ${analogyString} - Время: ${(res.reaction_time / 1000).toFixed(1)}с.`;
                detailedResultsList.appendChild(listItem);
            });
            startButton.style.display = 'block';
        }

        function saveTestResults() {
            const correctCount = userResults.filter(r => r.is_correct).length;
            const accuracyVal = (correctCount / Math.min(TOTAL_ANALOGIES, analogies.length)) * 100;
            const totalReactionTime = userResults.reduce((sum, r) => sum + r.reaction_time, 0);
            const avgTimeVal = userResults.length > 0 ? (totalReactionTime / userResults.length) : null;


            const dataToSend = {
                test_type: 'analogies_test',
                average_time: avgTimeVal, // Average time per analogy in ms
                accuracy: parseFloat(accuracyVal.toFixed(1)),
                results: userResults.map(r => ({
                    trial_number: r.trial_number,
                    stimulus_value: r.stimulus_value,
                    response_value: r.response_value,
                    is_correct: r.is_correct,
                    reaction_time: r.reaction_time
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
