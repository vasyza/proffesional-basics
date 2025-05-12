<?php
session_start();
require_once '../api/config.php';

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Получение ID запроса из URL
$request_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$request_id) {
    header('Location: /admin/role_requests.php?error=' . urlencode("Некорректный ID запроса"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Получение данных о запросе
    $stmt = $pdo->prepare("
        SELECT rr.id, rr.user_id, rr.requested_role, rr.description, rr.status, rr.created_at, u.name 
        FROM role_requests rr
        JOIN users u ON rr.user_id = u.id
        WHERE rr.id = :id
    ");
    $stmt->execute([':id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header('Location: /admin/role_requests.php?error=' . urlencode("Запрос не найден"));
        exit;
    }

} catch (PDOException $e) {
    error_log("Ошибка получения запроса: " . $e->getMessage());
    header('Location: /admin/role_requests.php?error=' . urlencode("Ошибка базы данных"));
    exit;
}

$pageTitle = "Управление запросом на роль";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Управление запросом на роль</h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5>Запрос №<?php echo $request['id']; ?> — <?php echo htmlspecialchars($request['name']); ?></h5>
        </div>
        <div class="card-body">
            <p><strong>Роль:</strong> <?php echo htmlspecialchars($request['requested_role']); ?></p>
            <p><strong>Описание:</strong><br><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
            <p><strong>Статус:</strong> <?php echo htmlspecialchars($request['status']); ?></p>
            <p><strong>Дата создания:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
        </div>
    </div>

    <form action="../api/update_request.php" method="POST">
        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

        <div class="mb-3">
            <label for="status" class="form-label">Выберите статус:</label>
            <select name="status" id="status" class="form-select" required>
                <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>В ожидании</option>
                <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Одобрено</option>
                <option value="rejected" <?php echo $request['status'] === 'rejected' ? 'selected' : ''; ?>>Отклонено</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="comment" class="form-label">Комментарий администратора:</label>
            <textarea name="description" id="comment" class="form-control" rows="4" placeholder="Добавьте комментарий..."></textarea>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-success">Обновить запрос</button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
