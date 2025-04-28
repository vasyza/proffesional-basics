<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

// Подключение к базе данных
try {
    $pdo = getDbConnection();

    // Получаем все профессионально важные качества
    $stmt = $pdo->prepare("
        SELECT id, name, category, description
        FROM professional_qualities
        ORDER BY category ASC, name ASC
    ");
    $stmt->execute();
    $qualities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

// Заголовок страницы
$pageTitle = "Профессионально важные качества";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Профессионально важные качества (ПВК)</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (count($qualities) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Список качеств</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Название качества</th>
                                <th>Категория</th>
                                <th>Описание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qualities as $index => $quality): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($quality['name']); ?></td>
                                    <td><?php echo htmlspecialchars($quality['category'] ?? 'Не указана'); ?></td>
                                    <td><?php echo htmlspecialchars($quality['description'] ?? 'Нет описания'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Пока нет добавленных профессионально важных качеств.
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> Вернуться в панель эксперта
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
