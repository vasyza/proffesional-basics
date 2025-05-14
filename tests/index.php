<?php
session_start();

// Завершение партии тестов
if (isset($_POST['finish_batch'])) {
    require_once '../api/config.php';
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT id FROM test_batches WHERE user_id = ? AND isFinished = FALSE ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $activeBatch = $stmt->fetch();

        if ($activeBatch) {
            $finishStmt = $pdo->prepare("UPDATE test_batches SET isFinished = TRUE WHERE id = ?");
            $finishStmt->execute([$activeBatch['id']]);
        }

        header("Location: /tests/index.php");
        exit;
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Ошибка базы данных: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

require_once '../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? '';

// Получаем текущее состояние публичности из light_respondents
$isPublicProfile = false;
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT isPublic FROM light_respondents WHERE user_name = ? ORDER BY test_date DESC LIMIT 1");
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC); // Явно указываем тип выборки

    // Проверяем наличие ключа в результате
    if ($result && array_key_exists('ispublic', $result)) {
        $isPublicProfile = (bool)$result['ispublic'];
    }
} catch (PDOException $e) {
    error_log("Ошибка при получении настроек публичности: " . $e->getMessage());
}

$pageTitle = "Тесты сенсомоторных реакций";
include_once '../includes/header.php';

// Проверка назначенных тестов для текущего пользователя
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT b.id, b.created_at, b.expert_id, u.name AS expert_name
                           FROM test_batches b
                           JOIN users u ON b.expert_id = u.id
                           WHERE b.user_id = ? AND b.isFinished = FALSE
                           ORDER BY b.created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $activeBatch = $stmt->fetch();

    $testsStmt = $pdo->prepare("SELECT tb.id, tb.test_type, tn.name AS test_name
                                FROM tests_in_batches tb 
                                LEFT JOIN test_names tn ON tb.test_type = tn.test_type
                                WHERE tb.batch_id = ?
                                ORDER BY tb.id ");
    if ($activeBatch) {
        $testsStmt->execute([$activeBatch['id']]);
        $tests = $testsStmt->fetchAll();
    } else {
        $tests = [];
    }
} catch (PDOException $e) {
    $activeBatch = null;
    $tests = [];
}

// Проверка завершенных назначенных тестов
$completedTests = [];
if ($activeBatch) {
    foreach ($tests as $test) {
        $stmt = $pdo->prepare("SELECT id FROM test_sessions WHERE user_id = ? AND test_type = ? AND created_at >= ? LIMIT 1");
        $stmt->execute([$userId, $test['test_type'], $activeBatch['created_at']]);
        $completed = $stmt->fetch();

        if ($completed) {
            $completedTests[] = $test['test_type'];

            $updateStmt = $pdo->prepare("UPDATE tests_in_batches SET isFinished = TRUE WHERE id = ?");
            $updateStmt->execute([$test['id']]);
        }
    }

    // Проверка, завершены ли все назначенные тесты
    if (count($completedTests) === count($tests)) {
        $showFinishButton = true;
    }
}

?>

<div class="container py-4">
    <!-- Тумблер публичного профиля -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Настройки профиля</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                       id="publicProfileToggle" <?= $isPublicProfile ? 'checked' : '' ?>>
                <label class="form-check-label" for="publicProfileToggle">Публичный
                    профиль</label>
            </div>
        </div>
        <div class="card-body">
            <p>Когда тумблер включен, ваши результаты тестов будут видны другим
                пользователям.</p>
        </div>
    </div>

    <script>
        // Обработчик изменения тумблера
        document.getElementById('publicProfileToggle').addEventListener('change', function () {
            const isPublic = this.checked;

            fetch('/api/update_profile_visibility.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    is_public: isPublic
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Ошибка при сохранении настроек');
                        this.checked = !this.checked; // Возвращаем в предыдущее состояние
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка соединения');
                    this.checked = !this.checked;
                });
        });
    </script>

    <div class="container py-4">
        <?php
        if ($activeBatch): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Назначенные вам тесты
                        от <?php echo htmlspecialchars($activeBatch['expert_name']); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group mb-3">
                        <?php foreach ($tests as $test): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($test['test_name'] ?: $test['test_type']); ?>
                                <?php if (in_array($test['test_type'], $completedTests)): ?>
                                    <span class="text-success">Пройдено &#x2713;</span>
                                <?php else: ?>
                                    <span class="text-danger">Не пройдено</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (isset($showFinishButton)): ?>
                        <form method="post" action="">
                            <button type="submit" name="finish_batch"
                                    class="btn btn-success">Завершить тесты
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="/tests/test_batch.php?batch_id=<?php echo $activeBatch['id']; ?>"
                           class="btn btn-primary">Пройти тесты</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Простые сенсомоторные реакции</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                                        <h5 class="card-title">Реакция на
                                            свет</h5>
                                        <a href="/tests/sensorimotor/light_reaction.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-volume-up fa-3x text-info mb-3"></i>
                                        <h5 class="card-title">Реакция на
                                            звук</h5>
                                        <a href="/tests/sensorimotor/sound_reaction.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Сложные сенсомоторные реакции</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-palette fa-3x text-success mb-3"></i>
                                        <h5 class="card-title">Реакция на разные
                                            цвета</h5>
                                        <a href="/tests/sensorimotor/color_reaction.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-headphones fa-3x text-danger mb-3"></i>
                                        <h5 class="card-title">Звуковой сигнал и
                                            арифметика</h5>
                                        <a href="/tests/sensorimotor/sound_arithmetic.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calculator fa-3x text-primary mb-3"></i>
                                        <h5 class="card-title">Визуальная
                                            арифметика</h5>
                                        <a href="/tests/sensorimotor/visual_arithmetic.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Реакция на движущийся объект</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-running fa-3x text-success mb-3"></i>
                                        <h5 class="card-title">Простая реакция
                                            на движущийся объект</h5>
                                        <a href="/tests/moving_object/simple_reaction.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-project-diagram fa-3x text-info mb-3"></i>
                                        <h5 class="card-title">Сложная реакция
                                            на движущиеся объекты</h5>
                                        <a href="/tests/moving_object/complex_reaction.php"
                                           class="btn btn-primary">Пройти
                                            тест</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($userRole === 'admin' || $userRole === 'expert'): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Инструменты для
                                экспертов/администраторов</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                            <h5 class="card-title">Назначение
                                                тестов пользователям</h5>
                                            <p class="card-text">Назначение
                                                пользователям тестов для
                                                прохождения.</p>
                                            <a href="../expert/add_tests_batch.php"
                                               class="btn btn-success">Назначить
                                                тесты</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                                            <h5 class="card-title">Результаты
                                                тестирований</h5>
                                            <p class="card-text">Просмотр
                                                статистики и результатов
                                                прохождения тестов
                                                пользователями.</p>
                                            <a href="/tests/expert_results.php"
                                               class="btn btn-success">Просмотр
                                                результатов</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Раздел с результатами пользователя -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Мои результаты</h5>
                    </div>
                    <div class="card-body">
                        <p>Здесь вы можете просмотреть свои результаты по всем
                            пройденным тестам.</p>
                        <a href="/tests/my_results.php" class="btn btn-info">Просмотреть
                            мои результаты</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/footer.php'; ?>
