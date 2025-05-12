<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Получение фильтра по статусу из URL
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$validStatuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'all';
}

try {
    $pdo = getDbConnection();

    // Базовый запрос
    $sql = "
        SELECT rr.id, rr.user_id, rr.requested_role, rr.description, rr.status, rr.created_at, u.name 
        FROM role_requests rr
        JOIN users u ON rr.user_id = u.id
    ";

    // Применение фильтра по статусу
    if ($statusFilter !== 'all') {
        $sql .= " WHERE rr.status = :status";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $statusFilter, PDO::PARAM_STR);
    } else {
        $stmt = $pdo->prepare($sql);
    }

    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Ошибка при получении списка запросов: " . $e->getMessage());
    $requests = [];
}

$pageTitle = "Список запросов на роль";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Список запросов на роль</h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Фильтр по статусу -->
    <div class="mb-4">
        <form method="GET" class="d-flex align-items-center">
            <label for="status" class="me-2">Фильтр по статусу:</label>
            <select name="status" id="status" class="form-select me-2" style="width: 200px;">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Все</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>В ожидании</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Одобрено</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Отклонено</option>
            </select>
            <button type="submit" class="btn btn-primary">Фильтровать</button>
        </form>
    </div>

    <?php if (count($requests) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Пользователь</th>
                        <th>Роль</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['name']); ?></td>
                            <td><?php echo htmlspecialchars($request['requested_role']); ?></td>
                            <td><?php echo htmlspecialchars(mb_strimwidth($request['description'], 0, 50, '...')); ?></td>
                            <td><?php echo htmlspecialchars($request['status']); ?></td>
                            <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                            <td>
                                <a href="manage_request.php?id=<?php echo $request['id']; ?>" class="btn btn-outline-primary btn-sm">Управление</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Запросы не найдены.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
