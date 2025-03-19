<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: /auth/login.php");
    exit;
}

$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

$pageTitle = "Тесты сенсомоторных реакций";
include_once '../includes/header.php';

// Проверка активных приглашений на тесты для текущего пользователя
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, g.name as group_name 
        FROM test_invitations i
        JOIN groups g ON i.group_id = g.id
        WHERE i.user_id = ? AND i.is_completed = 0
    ");
    $stmt->execute([$userId]);
    $invitations = $stmt->fetchAll();
} catch (PDOException $e) {
    $invitations = [];
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Тесты сенсомоторных реакций</h1>

            <?php if (!empty($invitations)): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Приглашения на тесты</h5>
                    </div>
                    <div class="card-body">
                        <p>У вас есть активные приглашения на прохождение тестов:</p>
                        <div class="list-group">
                            <?php foreach ($invitations as $invitation): ?>
                                <a href="/tests/invitation.php?id=<?php echo $invitation['id']; ?>"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">Приглашение от группы
                                            "<?php echo htmlspecialchars($invitation['group_name']); ?>"</h5>
                                        <p class="mb-1">Создано:
                                            <?php echo date('d.m.Y H:i', strtotime($invitation['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">Пройти тесты</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
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
                                            <h5 class="card-title">Реакция на свет</h5>
                                            <p class="card-text">Тест измеряет скорость вашей реакции на световой
                                                стимул.</p>
                                            <a href="/tests/sensorimotor/light_reaction.php"
                                                class="btn btn-primary">Пройти тест</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-volume-up fa-3x text-info mb-3"></i>
                                            <h5 class="card-title">Реакция на звук</h5>
                                            <p class="card-text">Тест измеряет скорость вашей реакции на звуковой
                                                стимул.</p>
                                            <a href="/tests/sensorimotor/sound_reaction.php"
                                                class="btn btn-primary">Пройти тест</a>
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
                                            <h5 class="card-title">Реакция на разные цвета</h5>
                                            <p class="card-text">Тест измеряет скорость вашей реакции на различные
                                                цветовые стимулы.</p>
                                            <a href="/tests/sensorimotor/color_reaction.php"
                                                class="btn btn-primary">Пройти тест</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-headphones fa-3x text-danger mb-3"></i>
                                            <h5 class="card-title">Звуковой сигнал и арифметика</h5>
                                            <p class="card-text">Тест на скорость реакции при определении чётности числа
                                                на слух.</p>
                                            <a href="/tests/sensorimotor/sound_arithmetic.php"
                                                class="btn btn-primary">Пройти тест</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calculator fa-3x text-primary mb-3"></i>
                                            <h5 class="card-title">Визуальная арифметика</h5>
                                            <p class="card-text">Тест на скорость реакции при определении чётности числа
                                                визуально.</p>
                                            <a href="/tests/sensorimotor/visual_arithmetic.php"
                                                class="btn btn-primary">Пройти тест</a>
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
                                <h5 class="mb-0">Инструменты для экспертов/администраторов</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                                <h5 class="card-title">Управление приглашениями</h5>
                                                <p class="card-text">Создание и отслеживание приглашений для прохождения
                                                    тестов.</p>
                                                <a href="/tests/manage_invitations.php"
                                                    class="btn btn-success">Управление</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                                                <h5 class="card-title">Результаты тестирований</h5>
                                                <p class="card-text">Просмотр статистики и результатов прохождения тестов
                                                    пользователями.</p>
                                                <a href="/tests/results.php" class="btn btn-success">Просмотр
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
                            <p>Здесь вы можете просмотреть свои результаты по всем пройденным тестам.</p>
                            <a href="/tests/my_results.php" class="btn btn-info">Просмотреть мои результаты</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>