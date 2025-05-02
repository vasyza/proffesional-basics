<?php
session_start();
require_once 'api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$consultationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($consultationId <= 0) {
    header("Location: /cabinet.php?error=" . urlencode("Некорректный ID консультации"));
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.name AS user_name, 
               u.login AS user_login, 
               cons.name AS consultant_name, 
               p.title AS profession_title
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN users cons ON c.consultant_id = cons.id
        LEFT JOIN professions p ON c.profession_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$consultationId]);
    $consultation = $stmt->fetch();

    if (!$consultation) {
        header("Location: /cabinet.php?error=" . urlencode("Консультация не найдена"));
        exit;
    }

    if ($userRole !== 'admin' && $consultation['user_id'] != $userId && $consultation['consultant_id'] != $userId) {
        header("Location: /cabinet.php?error=" . urlencode("У вас нет доступа к этой консультации"));
        exit;
    }

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

$pageTitle = "Детали консультации";
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Детали консультации</h5>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <strong>Тема:</strong> <?php echo htmlspecialchars($consultation['topic']); ?>
                    </div>

                    <div class="mb-3">
                        <strong>Профессия:</strong> 
                        <?php echo $consultation['profession_title'] ? htmlspecialchars($consultation['profession_title']) : '<span class="text-muted">Не указана</span>'; ?>
                    </div>

                    <div class="mb-3">
                        <strong>Статус:</strong>
                        <?php
                        switch ($consultation['status']) {
                            case 'pending':
                                echo '<span class="badge bg-warning text-dark">В ожидании</span>';
                                break;
                            case 'accepted':
                                echo '<span class="badge bg-info">Принята</span>';
                                break;
                            case 'completed':
                                echo '<span class="badge bg-success">Завершена</span>';
                                break;
                            case 'cancelled':
                                echo '<span class="badge bg-danger">Отменена</span>';
                                break;
                            default:
                                echo '<span class="badge bg-secondary">Неизвестно</span>';
                                break;
                        }
                        ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Студент:</strong> 
                            <?php echo htmlspecialchars($consultation['user_name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Консультант:</strong>
                            <?php echo $consultation['consultant_id'] ? htmlspecialchars($consultation['consultant_name']) : '<span class="text-muted">Не назначен</span>'; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Дата создания запроса:</strong><br>
                        <?php echo date('d.m.Y H:i', strtotime($consultation['created_at'])); ?>
                    </div>

                    <?php if (!empty($consultation['scheduled_at'])): ?>
                        <div class="mb-3">
                            <strong>Запланированная дата консультации:</strong><br>
                            <?php echo date('d.m.Y H:i', strtotime($consultation['scheduled_at'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($consultation['completed_at'])): ?>
                        <div class="mb-3">
                            <strong>Дата завершения консультации:</strong><br>
                            <?php echo date('d.m.Y H:i', strtotime($consultation['completed_at'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <strong>Описание вопроса:</strong><br>
                        <?php echo nl2br(htmlspecialchars($consultation['message'] ?? 'Нет описания')); ?>
                    </div>

                    <?php if (!empty($consultation['notes'])): ?>
                        <div class="mb-3">
                            <strong>Примечания студента:</strong><br>
                            <?php echo nl2br(htmlspecialchars($consultation['notes'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($consultation['consultant_notes'])): ?>
                        <div class="mb-3">
                            <strong>Комментарий консультанта:</strong><br>
                            <?php echo nl2br(htmlspecialchars($consultation['consultant_notes'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($consultation['status'] === 'completed'): ?>
                        <div class="mb-3">
                            <strong>Итоги консультации:</strong><br>
                            <?php echo nl2br(htmlspecialchars($consultation['completion_notes'] ?? 'Нет итогов')); ?>
                        </div>

                        <!-- <div class="mb-3">
                            <strong>Оценка:</strong>
                            <?php
                            if ($consultation['rating']) {
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<i class="fas ' . ($i <= $consultation['rating'] ? 'fa-star text-warning' : 'fa-star text-muted') . '"></i>';
                                }
                                echo " ({$consultation['rating']}/5)";
                            } else {
                                echo '<span class="text-muted">Нет оценки</span>';
                            }
                            ?>
                        </div> -->

                        <?php if (!empty($consultation['user_feedback'])): ?>
                            <div class="mb-3">
                                <strong>Отзыв студента:</strong><br>
                                <?php echo nl2br(htmlspecialchars($consultation['user_feedback'])); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($consultation['status'] === 'cancelled' && !empty($consultation['cancel_reason'])): ?>
                        <div class="mb-3">
                            <strong>Причина отмены:</strong><br>
                            <?php echo nl2br(htmlspecialchars($consultation['cancel_reason'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="/cabinet.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Назад
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
