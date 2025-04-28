<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

// Подключение к базе данных
try {
    $pdo = getDbConnection();

    // Получение списка экспертов
    $stmt = $pdo->prepare("
        SELECT id, name, bio, created_at
        FROM users
        WHERE role = 'expert'
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $experts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

// Заголовок страницы
$pageTitle = "Эксперты портала";
include 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Наши эксперты</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (count($experts) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Список экспертов</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Имя</th>
                                <th>О себе</th>
                                <th>Дата регистрации</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($experts as $index => $expert): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($expert['name']); ?></td>
                                    <td><?php echo htmlspecialchars($expert['bio'] ?? 'Не указано'); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($expert['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            На данный момент нет зарегистрированных экспертов.
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="/index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> Вернуться на главную страницу
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
