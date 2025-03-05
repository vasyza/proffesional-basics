<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Получение сообщений
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

try {
    $pdo = getDbConnection();
    
    // Получение всех пользователей
    $stmt = $pdo->query("
        SELECT u.*, 
               COALESCE(COUNT(er.id), 0) as ratings_count,
               COALESCE(COUNT(c.id), 0) as consultations_count
        FROM users u
        LEFT JOIN expert_ratings er ON u.id = er.expert_id
        LEFT JOIN consultations c ON u.id = c.consultant_id
        GROUP BY u.id
        ORDER BY u.login ASC
    ");
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
    $users = [];
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Управление пользователями</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список пользователей</h5>
            <a href="/admin/user_add.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus me-1"></i>Добавить пользователя
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Логин</th>
                            <th>Роль</th>
                            <th>Дата регистрации</th>
                            <th>Активность</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['login']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo match($user['role']) {
                                            'admin' => 'bg-danger',
                                            'expert' => 'bg-primary',
                                            'consultant' => 'bg-info text-dark',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo match($user['role']) {
                                            'admin' => 'Администратор',
                                            'expert' => 'Эксперт',
                                            'consultant' => 'Консультант',
                                            default => 'Студент'
                                        }; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['role'] == 'expert'): ?>
                                        <span data-bs-toggle="tooltip" title="Оценки профессий">
                                            <i class="fas fa-star text-warning me-1"></i><?php echo $user['ratings_count']; ?>
                                        </span>
                                    <?php elseif ($user['role'] == 'consultant'): ?>
                                        <span data-bs-toggle="tooltip" title="Консультации">
                                            <i class="fas fa-comments text-info me-1"></i><?php echo $user['consultations_count']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" data-bs-toggle="tooltip" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения удаления -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить пользователя <span id="userName"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Удалить</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('userName').textContent = name;
    document.getElementById('deleteLink').href = '/api/delete_user.php?id=' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include_once '../includes/admin_footer.php'; ?> 