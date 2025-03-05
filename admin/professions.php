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

// Получение категории из URL
$category = isset($_GET['category']) ? $_GET['category'] : '';

try {
    $pdo = getDbConnection();
    
    // Получение категорий профессий
    $stmt = $pdo->query("SELECT DISTINCT type FROM professions ORDER BY type");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Получение списка профессий с фильтрацией
    $sql = "SELECT p.*, 
                  u.name as creator_name, 
                  COUNT(DISTINCT er.id) as ratings_count,
                  COALESCE(AVG(er.rating), 0) as avg_rating
           FROM professions p
           LEFT JOIN users u ON p.created_by = u.id
           LEFT JOIN expert_ratings er ON p.id = er.profession_id
           WHERE 1=1";
    
    if (!empty($category)) {
        $sql .= " AND p.type = :category";
    }
    
    $sql .= " GROUP BY p.id, u.name";
    
    $stmt = $pdo->prepare($sql);
    
    if (!empty($category)) {
        $stmt->bindParam(':category', $category);
    }
    
    $stmt->execute();
    $professions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
    $professions = [];
    $categories = [];
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Управление профессиями</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo !empty($category) ? htmlspecialchars($category) : 'Все категории'; ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                    <li><a class="dropdown-item" href="/admin/professions.php">Все категории</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li><a class="dropdown-item" href="/admin/professions.php?category=<?php echo urlencode($cat); ?>"><?php echo htmlspecialchars($cat); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <a href="/admin/profession_add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить профессию
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Список профессий</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Наименование</th>
                        <th>Категория</th>
                        <th>Оценок</th>
                        <th>ПВК</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($professions) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">Профессии не найдены</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($professions as $profession): ?>
                            <tr>
                                <td><?php echo $profession['id']; ?></td>
                                <td><?php echo htmlspecialchars($profession['title']); ?></td>
                                <td><?php echo htmlspecialchars($profession['type'] ?: 'Не указана'); ?></td>
                                <td><?php echo $profession['ratings_count']; ?></td>
                                <td><?php echo $profession['avg_rating']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/profession_edit.php?id=<?php echo $profession['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/profession_pvk.php?id=<?php echo $profession['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-list"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="confirmDelete(<?php echo $profession['id']; ?>, '<?php echo htmlspecialchars(addslashes($profession['title'])); ?>')">
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
                Вы действительно хотите удалить профессию <span id="professionTitle"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Удалить</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('professionTitle').textContent = title;
    document.getElementById('deleteLink').href = '/api/delete_profession.php?id=' + id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include_once '../includes/admin_footer.php'; ?> 