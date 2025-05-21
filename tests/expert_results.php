<?php
session_start();
require_once '../api/config.php';

// Авторизация (предположим, что у вас есть проверка, что текущий пользователь — админ или эксперт)
if (!isset($_SESSION['user_id']) || !($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'expert')) {
    header("Location: /auth/login.php");
    exit;
}

$pdo = getDbConnection();

// Если user_id не задан — показываем список всех пользователей
if (!isset($_GET['user_id'])) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $query = "SELECT id, name FROM users";
    if ($search) {
        $query .= " WHERE name LIKE :search";
    }
    $query .= " ORDER BY name";
    $stmt = $pdo->prepare($query);
    if ($search) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    $stmt->execute();
    $users = $stmt->fetchAll();
    // Получаем всех пользователей

    include '../includes/header.php';
    ?>
    <div class="container py-5">
        <h1 class="mb-4">Выберите пользователя</h1>
        <form class="mb-3" method="get">
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                       placeholder="Поиск по имени пользователя"
                       value="<?php echo htmlspecialchars($search ?? ''); ?>">
                <button type="submit" class="btn btn-primary">Поиск</button>
            </div>
        </form>
        <?php if (count($users) > 0): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Пользователи</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($users as $u): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($u['name']); ?></span>
                                <a href="?user_id=<?php echo $u['id']; ?>"
                                   class="btn btn-primary btn-sm">Просмотреть</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Нет зарегистрированных
                пользователей.
            </div>
        <?php endif; ?>
    </div>
    <?php
    include '../includes/footer.php';
    exit;
}

// Иначе — пользователь выбран, показываем его результаты
$userId = (int)$_GET['user_id'];
$allowedTypes = ['light_reaction', 'sound_reaction', 'color_reaction', 'visual_arithmetic', 'sound_arithmetic', 'moving_object_simple', 'moving_object_complex', 'analog_tracking', 'pursuit_tracking', 'schulte_table', 'number_memorization', 'analogies_test'];
$testType = isset($_GET['type']) && in_array($_GET['type'], $allowedTypes) ? $_GET['type'] : '';

// Получаем имя выбранного пользователя (для заголовка и ссылки «назад»)
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header("HTTP/1.0 404 Not Found");
    echo "Пользователь не найден.";
    exit;
}

// Получение сессий
$sql = "SELECT ts.*, u.name 
        FROM test_sessions ts 
        JOIN users u ON ts.user_id = u.id
        WHERE ts.user_id = :user_id";
if ($testType) {
    $sql .= " AND ts.test_type = :type";
}
$sql .= " ORDER BY ts.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_filter([
    ':user_id' => $userId,
    $testType ? ':type' : null => $testType
]));
$results = $stmt->fetchAll();

// Подготовка данных для графиков
$graphData = [];
if ($testType) {
    $gq = "SELECT created_at, average_time, accuracy
           FROM test_sessions
           WHERE user_id = :user_id AND test_type = :type
           ORDER BY created_at ";
    $gs = $pdo->prepare($gq);
    $gs->execute([':user_id' => $userId, ':type' => $testType]);
    $graphData = $gs->fetchAll();
}

// Рендерим страницу результатов
$pageTitle = "Результаты: " . htmlspecialchars($user['name']);
include '../includes/header.php';
?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="expert_results.php" class="btn btn-primary">&larr; К списку
            пользователей</a>
        <h1 class="mb-0">
            Результаты <?php echo htmlspecialchars($user['name']); ?></h1>
        <div></div> <!-- placeholder для выравнивания -->
    </div>

    <form class="mb-4" method="get">
        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
        <div class="row g-2">
            <div class="col-auto">
                <label for="type" class="form-label">Фильтр по типу
                    теста:</label>
                <select name="type" id="type" class="form-select"
                        onchange="this.form.submit()">
                    <option value="">Все типы</option>
                    <?php
                    $labels = [
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
                    ];
                    foreach ($allowedTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php if ($testType === $type) echo 'selected'; ?>>
                            <?php echo $labels[$type]; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($testType): ?>
                <div class="col-auto d-flex align-items-end">
                    <a href="?user_id=<?php echo $userId; ?>"
                       class="btn btn-outline-secondary">Сбросить фильтр</a>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($testType && count($graphData) >= 2): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Графики для
                    «<?php echo $labels[$testType]; ?>»</h5>
                <canvas id="timeChart"></canvas>
                <?php if (array_filter(array_column($graphData, 'accuracy'))): ?>
                    <div class="mt-4">
                        <canvas id="accuracyChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($results)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Сессии тестирования</h5>
            </div>
            <div class="card-body p-0 table-responsive">
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
                    <?php foreach ($results as $i => $row): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo $labels[$row['test_type']] ?? $row['test_type']; ?></td>
                            <td><?php echo $row['average_time'] !== null ? round($row['average_time'], 2) . ' сек' : '—'; ?></td>
                            <td><?php echo $row['accuracy'] !== null ? round($row['accuracy'], 1) . '%' : '—'; ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">У этого пользователя нет результатов
            тестов.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const graphData = <?php echo json_encode($graphData)?>;
    const labels = graphData.map(i => i.created_at);
    const timeData = graphData.map(i => i.average_time);
    const accuracyData = graphData.map(i => i.accuracy);

    new Chart(
        document.getElementById('timeChart'),
        {
            type: 'line', data: {
                labels, datasets: [{
                    label: 'Среднее время (сек)',
                    data: timeData,
                    backgroundColor: 'rgba(54,162,235,0.2)',
                    borderColor: 'rgba(54,162,235,1)',
                    borderWidth: 1
                }]
            }
        }
    );

    if (accuracyData.some(a => a !== null)) {
        new Chart(
            document.getElementById('accuracyChart'),
            {
                type: 'line', data: {
                    labels, datasets: [{
                        label: 'Точность (%)',
                        data: accuracyData,
                        backgroundColor: 'rgba(255,99,132,0.2)',
                        borderColor: 'rgba(255,99,132,1)',
                        borderWidth: 1
                    }]
                }
            }
        );
    }
</script>
<?php include '../includes/footer.php'; ?>
