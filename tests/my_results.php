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
$allowedTypes = ['light_reaction', 'sound_reaction', 'color_reaction', 'visual_arithmetic', 'sound_arithmetic', 'moving_object_simple', 'moving_object_complex'];
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
    $results = $stmt->fetchAll();

    // Получение данных для графиков
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
                    'pursuit_tracking' => 'Слежение с преследованием'
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
                                <td><?php echo $row['average_time'] !== null ? round($row['average_time'], 2) . ' сек' : '—'; ?></td>
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
    const labels = graphData.map(item => item.created_at);

    const timeData = graphData.map(item => item.average_time);
    const accuracyData = graphData.map(item => item.accuracy);

    new Chart(document.getElementById('timeChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Среднее время (сек)',
                data: timeData,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        }
    });

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
                    borderWidth: 1
                }]
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>
