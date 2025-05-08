<?php
session_start();
require_once '../api/config.php';

// Авторизация
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'expert') {
    header("Location: /auth/login.php");
    exit;
}

// Поиск пользователя
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $pdo = getDbConnection();

    $sql = "SELECT id, name FROM users WHERE role = 'user'";

    if ($searchQuery) {
        $sql .= " AND name ILIKE :searchQuery";
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);

    if ($searchQuery) {
        $searchParam = '%' . $searchQuery . '%';
        $stmt->bindParam(':searchQuery', $searchParam, PDO::PARAM_STR);
    }

    $stmt->execute();
    $users = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

$pageTitle = "Выбор пользователя для тестов";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Выбрать пользователя для тестов</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form class="mb-4" method="get">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Поиск по имени пользователя" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn btn-primary">Поиск</button>
        </div>
    </form>

    <?php if (count($users) > 0): ?>
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Пользователи</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Имя пользователя</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td>
                                        <a href="pick_tests.php?user_id=<?php echo $user['id']; ?>" class="btn btn-outline-primary">Выбрать тесты</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Пользователи не найдены.</div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
