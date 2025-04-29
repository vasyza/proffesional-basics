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

// Получение статуса из URL
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    $pdo = getDbConnection();

    // Формирование SQL-запроса с учетом фильтра
    $sql = "
    SELECT c.*, 
           u_consultant.name as consultant_name,
           u_student.name as student_name,
           p.title as profession_title
    FROM consultations c
    JOIN users u_consultant ON c.consultant_id = u_consultant.id
    JOIN users u_student ON c.user_id = u_student.id
    JOIN professions p ON c.profession_id = p.id
    WHERE 1=1
";

    if (!empty($status)) {
        $sql .= " AND c.status = :status";
    }

    $sql .= " ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);

    if (!empty($status)) {
        $stmt->bindParam(':status', $status);
    }

    $stmt->execute();
    $consultations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
    $consultations = [];
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Управление консультациями</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php
                    $statusText = '';
                    switch ($status) {
                        case 'pending':
                            $statusText = 'Ожидает';
                            break;
                        case 'scheduled':
                            $statusText = 'Запланирована';
                            break;
                        case 'completed':
                            $statusText = 'Завершена';
                            break;
                        case 'cancelled':
                            $statusText = 'Отменена';
                            break;
                        default:
                            $statusText = 'Все статусы';
                    }
                    echo $statusText;
                    ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                    <li><a class="dropdown-item" href="/admin/consultations.php">Все статусы</a></li>
                    <li><a class="dropdown-item" href="/admin/consultations.php?status=pending">Ожидает</a></li>
                    <li><a class="dropdown-item" href="/admin/consultations.php?status=scheduled">Запланирована</a></li>
                    <li><a class="dropdown-item" href="/admin/consultations.php?status=completed">Завершена</a></li>
                    <li><a class="dropdown-item" href="/admin/consultations.php?status=cancelled">Отменена</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Список консультаций</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата создания</th>
                        <th>Студент</th>
                        <th>Консультант</th>
                        <th>Профессия</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($consultations) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">Консультации не найдены</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td><?php echo $consultation['id']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($consultation['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($consultation['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($consultation['consultant_name']); ?></td>
                                <td><?php echo htmlspecialchars($consultation['profession_title']); ?></td>
                                <td>
                                    <span class="badge <?php
                                                        echo $consultation['status'] === 'completed' ? 'bg-success' : ($consultation['status'] === 'scheduled' ? 'bg-primary' : ($consultation['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'));
                                                        ?>">
                                        <?php
                                        switch ($consultation['status']) {
                                            case 'pending':
                                                echo 'Ожидает';
                                                break;
                                            case 'scheduled':
                                                echo 'Запланирована';
                                                break;
                                            case 'completed':
                                                echo 'Завершена';
                                                break;
                                            case 'cancelled':
                                                echo 'Отменена';
                                                break;
                                            default:
                                                echo $consultation['status'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/consultation.php?id=<?php echo $consultation['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger"
                                            onclick="confirmDelete(<?php echo $consultation['id']; ?>)">
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
                Вы действительно хотите удалить консультацию?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Удалить</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        document.getElementById('deleteLink').href = '/api/delete_consultation.php?id=' + id;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>

<?php include_once '../includes/admin_footer.php'; ?>