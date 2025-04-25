<!-- <?php
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
    
    // Получение всех групп
    $stmt = $pdo->query("
        SELECT g.*, 
               COUNT(DISTINCT gm.user_id) as members_count,
               COUNT(DISTINCT p.id) as professions_count
        FROM student_groups g
        LEFT JOIN group_members gm ON g.id = gm.group_id
        LEFT JOIN group_professions gp ON g.id = gp.group_id
        LEFT JOIN professions p ON gp.profession_id = p.id
        GROUP BY g.id
        ORDER BY g.name ASC
    ");
    $groups = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
    $groups = [];
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Управление рабочими группами</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Список рабочих групп</h5>
                <a href="/admin/group_add.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Добавить группу
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Наименование</th>
                        <th>Описание</th>
                        <th>Участников</th>
                        <th>Профессий</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($groups) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">Рабочие группы не найдены</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><?php echo $group['id']; ?></td>
                                <td><?php echo htmlspecialchars($group['name']); ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($group['description'], 0, 100) . (mb_strlen($group['description']) > 100 ? '...' : '')); ?></td>
                                <td><?php echo $group['members_count']; ?></td>
                                <td><?php echo $group['professions_count']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/group_edit.php?id=<?php echo $group['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/admin/group_members.php?id=<?php echo $group['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="/admin/group_professions.php?id=<?php echo $group['id']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-list"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="confirmDelete(<?php echo $group['id']; ?>, '<?php echo htmlspecialchars(addslashes($group['name'])); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить группу <span id="groupName"></span>?
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
    document.getElementById('groupName').textContent = name;
    document.getElementById('deleteLink').href = '/api/delete_group.php?id=' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include_once '../includes/admin_footer.php'; ?>  -->