<?php
session_start();
require_once '../api/config.php';

// Авторизация
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id']; // Получаем ID текущего пользователя

// Обработка фильтра
$allowedTypes = ['light_reaction', 'sound_reaction', 'color_reaction', 'visual_arithmetic', 'sound_arithmetic', 'moving_object_simple', 'moving_object_complex', 'analog_tracking', 'pursuit_tracking', 'schulte_table', 'number_memorization', 'analogies_test'];
$testType = isset($_GET['type']) && in_array($_GET['type'], $allowedTypes) ? $_GET['type'] : '';

// Получение данных
try {
    $pdo = getDbConnection();

    $sql = "
        SELECT ts.*, u.name 
        FROM test_sessions ts 
        JOIN users u ON ts.user_id = u.id
        WHERE ts.user_id = :user_id
    ";

    if ($testType) {
        $sql .= " AND ts.test_type = :type";
    }

    $sql .= " ORDER BY ts.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    if ($testType) {
        $stmt->bindParam(':type', $testType);
    }
    $stmt->execute();
    $results = $stmt->fetchAll();    // Получение данных для графиков
    $graphData = [];
    if ($testType) {
        $graphQuery = "
            SELECT created_at, average_time, accuracy 
            FROM test_sessions 
            WHERE user_id = :user_id AND test_type = :type
            ORDER BY created_at
        ";
        $graphStmt = $pdo->prepare($graphQuery);
        $graphStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $graphStmt->bindParam(':type', $testType);
        $graphStmt->execute();
        $graphData = $graphStmt->fetchAll();
    }

    // Получение PVK оценок пользователя
    $pvkAssessments = [];
    try {        $pvkQuery = "            SELECT 
                upa.profession_id,
                upa.pvk_id,
                upa.assessment_score,
                upa.assessment_level,
                upa.last_calculated,
                p.title as profession_name,
                pq.name as pvk_name,
                pq.description as pvk_description
            FROM user_pvk_assessments upa
            JOIN professions p ON upa.profession_id = p.id
            JOIN professional_qualities pq ON upa.pvk_id = pq.id
            WHERE upa.user_id = :user_id
            ORDER BY upa.last_calculated DESC, p.title, pq.name
        ";
        $pvkStmt = $pdo->prepare($pvkQuery);
        $pvkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $pvkStmt->execute();
        $pvkData = $pvkStmt->fetchAll();
        
        // Группируем по профессиям
        foreach ($pvkData as $assessment) {
            $profId = $assessment['profession_id'];
            if (!isset($pvkAssessments[$profId])) {
                $pvkAssessments[$profId] = [
                    'profession_name' => $assessment['profession_name'],
                    'pvk_list' => []
                ];
            }
            $pvkAssessments[$profId]['pvk_list'][] = $assessment;
        }
    } catch (PDOException $e) {
        // Игнорируем ошибки PVK, если таблицы еще не созданы
    }

} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

// Заголовок
$pageTitle = "Мои результаты тестов";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Мои результаты тестов</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- PVK Development Levels Section -->
    <?php if (!empty($pvkAssessments)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🎯 Уровни развития профессионально важных качеств (ПВК)</h5>
            </div>
            <div class="card-body">
                <?php foreach ($pvkAssessments as $professionData): ?>
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($professionData['profession_name']); ?>
                        </h6>
                        <div class="row">
                            <?php foreach ($professionData['pvk_list'] as $pvk): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-left-success">
                                        <div class="card-body">
                                            <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($pvk['pvk_name']); ?>">
                                                <?php echo htmlspecialchars($pvk['pvk_name']); ?>
                                            </h6>                                            <div class="mb-2">
                                                <span class="badge badge-lg 
                                                    <?php 
                                                    $level = $pvk['assessment_score'];
                                                    if ($level >= 8) echo 'bg-success';
                                                    elseif ($level >= 6) echo 'bg-warning';
                                                    elseif ($level >= 4) echo 'bg-info';
                                                    else echo 'bg-secondary';
                                                    ?>">
                                                    Уровень: <?php echo round($level, 1); ?>/10
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                Категория: <?php echo ucfirst($pvk['assessment_level']); ?><br>
                                                <?php echo date('d.m.Y', strtotime($pvk['last_calculated'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="alert alert-info mt-3">
                    <small>
                        <i class="fas fa-info-circle"></i> 
                        ПВК оценки рассчитываются на основе ваших результатов тестов и критериев, 
                        настроенных экспертами для каждой профессии.
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form class="mb-3" method="get">
        <label for="type" class="form-label">Фильтр по типу теста:</label>
        <div class="input-group">
            <select name="type" id="type" class="form-select"
                    onchange="this.form.submit()">
                <option value="">Все типы</option>
                <?php $typeLabels = [
                    'light_reaction' => 'Реакция на свет',
                    'sound_reaction' => 'Реакция на звук',
                    'color_reaction' => 'Реакция на разные цвета',
                    'sound_arithmetic' => 'Звуковой сигнал и арифметика',
                    'visual_arithmetic' => 'Визуальная арифметика',
                    'moving_object_simple' => 'Простая реакция на движущийся объект',
                    'moving_object_complex' => 'Сложная реакция на движущийся объект',
                    'analog_tracking' => 'Аналоговое слежение',
                    'pursuit_tracking' => 'Слежение с преследованием',
                    'schulte_table' => 'Тест внимания: Таблицы Шульте',
                    'number_memorization' => 'Тест памяти: Запоминание чисел',
                    'analogies_test' => 'Тест мышления: Аналогии'
                ]; ?>
                <?php foreach ($allowedTypes as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo $testType === $type ? 'selected' : ''; ?>>
                        <?php echo $typeLabels[$type]; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($testType): ?>
                <a href="?" class="btn btn-outline-secondary">Сбросить</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($testType && count($graphData) >= 2): ?>
        <div class="mb-4">
            <h4>Графики для <?php echo $typeLabels[$testType]; ?></h4>
            <div>
                <canvas id="timeChart"></canvas>
            </div>
            <?php if (!empty(array_filter(array_column($graphData, 'accuracy')))): ?>
                <div class="mt-4">
                    <canvas id="accuracyChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (count($results) > 0): ?>
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Мои результаты</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Тип теста</th>
                            <th>Среднее время</th>
                            <th>Точность</th>
                            <th>Дата</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($results as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $typeLabels[$row['test_type']] ?? $row['test_type']; ?></td>
                                <td>
                                    <?php
                                    // Check if average_time is not null
                                    if ($row['average_time'] !== null) {
                                        // For specific cognitive tests, assume time is in ms and convert to seconds
                                        if (in_array($row['test_type'], ['schulte_table', 'analogies_test', 'number_memorization'])) {
                                            // For number_memorization, average_time might be max length, not time.
                                            // Let's assume for schulte and analogies it's time in ms.
                                            // For number_memorization, 'average_time' stores max_correct_length, so no 'сек' unit.
                                            if ($row['test_type'] === 'number_memorization') {
                                                 echo round($row['average_time'], 0) . ' (макс. длина)';
                                            } else {
                                                 echo round($row['average_time'] / 1000, 2) . ' сек';
                                            }
                                        } else {
                                            // For other tests, assume it's already in seconds or a different unit
                                            echo round($row['average_time'], 2) . ' сек';
                                        }
                                    } else {
                                        echo '—'; // Display dash if average_time is null
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['accuracy'] !== null ? round($row['accuracy'], 1) . '%' : '—'; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">У вас пока нет результатов тестов.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const graphData = <?php echo json_encode($graphData); ?>;
    const currentTestType = '<?php echo $testType; ?>';
    let labels = [];
    let timeData = [];
    let accuracyData = [];

    if (graphData.length > 0) {
        labels = graphData.map(item => new Date(item.created_at).toLocaleString('ru-RU'));

        if (currentTestType === 'schulte_table' || currentTestType === 'analogies_test') {
            timeData = graphData.map(item => item.average_time !== null ? (item.average_time / 1000).toFixed(2) : null);
        } else if (currentTestType === 'number_memorization') {
            timeData = graphData.map(item => item.average_time);
        }
        else {
            timeData = graphData.map(item => item.average_time !== null ? parseFloat(item.average_time).toFixed(2) : null);
        }
        accuracyData = graphData.map(item => item.accuracy !== null ? parseFloat(item.accuracy).toFixed(1) : null);


        let timeChartLabel = 'Среднее время (сек)';
        if (currentTestType === 'number_memorization') {
            timeChartLabel = 'Макс. запомненная длина';
        }

        if (timeData.some(t => t !== null)) {
            new Chart(document.getElementById('timeChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: timeChartLabel,
                        data: timeData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        fill: false,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: { display: true, text: 'Дата прохождения' }
                        },
                        y: {
                             title: { display: true, text: timeChartLabel }
                        }
                    }
                }
            });
        } else {
            document.getElementById('timeChart').style.display = 'none';
        }


        if (accuracyData.some(a => a !== null)) {
            new Chart(document.getElementById('accuracyChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Точность (%)',
                        data: accuracyData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: false,
                        tension: 0.1
                    }]
                },
                 options: {
                    scales: {
                        x: {
                            title: { display: true, text: 'Дата прохождения' }
                        },
                        y: {
                             title: { display: true, text: 'Точность (%)' }
                        }
                    }
                }
            });
        } else {
             if(document.getElementById('accuracyChart')) {
                document.getElementById('accuracyChart').style.display = 'none';
            }
        }
    } else {
        if(document.getElementById('timeChart')) {
            document.getElementById('timeChart').style.display = 'none';
        }
        if(document.getElementById('accuracyChart')) {
            document.getElementById('accuracyChart').style.display = 'none';
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
