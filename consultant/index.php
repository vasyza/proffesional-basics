<?php
session_start();
?>
<?php if (isset($_SESSION['db_error'])): ?>
    <div class="alert alert-danger text-center" style="margin: 0; padding: 1rem; font-weight: bold;">
        <?php echo htmlspecialchars($_SESSION['db_error']); ?>
    </div>
<?php endif; ?>
<?php
//session_start();
require_once '../api/config.php';

// Проверка авторизации и роли консультанта
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'consultant') {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Подключение к базе данных
try {
    $pdo = getDbConnection();

    // Получаем данные о консультациях
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.name as user_name, 
               u.login as user_login, 
               p.title as profession_title
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        JOIN professions p ON c.profession_id = p.id
        WHERE c.consultant_id = ? AND c.status IN ('pending', 'scheduled')
        ORDER BY 
            CASE 
                WHEN c.status = 'scheduled' THEN 0
                WHEN c.status = 'pending' THEN 1
            END,
            c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $consultations = $stmt->fetchAll();

    // Получаем историю консультаций
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.name as user_name, 
               u.login as user_login, 
               p.title as profession_title
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        JOIN professions p ON c.profession_id = p.id
        WHERE c.consultant_id = ? AND c.status IN ('completed', 'cancelled')
        ORDER BY c.updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $history = $stmt->fetchAll();

    // Получаем данные консультанта
    $stmt = $pdo->prepare("
        SELECT c.specialization, c.experience, c.education,
               COUNT(cons.id) as consultations_count,
               COALESCE(AVG(cons.rating), 0) as avg_rating
        FROM consultants c
        LEFT JOIN consultations cons ON c.user_id = cons.consultant_id
        WHERE c.user_id = ?
        GROUP BY c.specialization, c.experience, c.education
    ");
    $stmt->execute([$userId]);
    $consultant_data = $stmt->fetch();

    // Получение статистики консультаций
    $stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM consultations 
        WHERE consultant_id = ?
    ");
    $stats->execute([$userId]);
    $consultationStats = $stats->fetch();

    // Получение ожидающих консультаций
    $pending = $pdo->prepare("
        SELECT c.*, u.name as user_name, p.title as profession_title 
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        JOIN professions p ON c.profession_id = p.id
        WHERE c.consultant_id = ? AND c.status = 'pending'
        ORDER BY c.created_at DESC
    ");
    $pending->execute([$userId]);
    $pendingConsultations = $pending->fetchAll();

    // Получение запланированных консультаций
    $scheduled = $pdo->prepare("
        SELECT c.*, u.name as user_name, p.title as profession_title 
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        JOIN professions p ON c.profession_id = p.id
        WHERE c.consultant_id = ? AND c.status = 'scheduled'
        ORDER BY c.scheduled_at ASC
    ");
    $scheduled->execute([$userId]);
    $scheduledConsultations = $scheduled->fetchAll();

    // Получение завершённых консультаций
    $completed = $pdo->prepare("
        SELECT c.*, u.name as user_name, p.title as profession_title 
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        JOIN professions p ON c.profession_id = p.id
        WHERE c.consultant_id = ? AND c.status = 'completed'
        ORDER BY c.scheduled_at ASC
    ");
    $completed->execute([$userId]);
    $completedConsultations = $completed->fetchAll();

    // Получение отменённых консультаций
    $cancelled = $pdo->prepare("
        SELECT c.*, u.name as user_name, p.title as profession_title 
        FROM consultations c
        JOIN users u ON c.user_id = u.id
        JOIN professions p ON c.profession_id = p.id
        WHERE c.consultant_id = ? AND c.status = 'cancelled'
        ORDER BY c.scheduled_at ASC
    ");
    $cancelled->execute([$userId]);
    $cancelledConsultations = $cancelled->fetchAll();

} catch (PDOException $e) {
    //$error = "Ошибка базы данных: " . $e->getMessage();
    if (isset($_SESSION['db_error'])) {
        unset($_SESSION['db_error']);
    }
    else{
        $errorMessage = $e->getMessage();
        $_SESSION['db_error'] = "Ошибка базы данных: " . $errorMessage;
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель консультанта - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .consultant-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 20px;
            margin-bottom: 20px;
        }

        .consultant-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
            padding: 10px;
            border-radius: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }

        .stat-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .stat-scheduled {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .stat-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .consultation-card {
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .consultation-header {
            padding: 15px;
            color: #fff;
        }

        .consultation-pending .consultation-header {
            background-color: #ffc107;
        }

        .consultation-scheduled .consultation-header {
            background-color: #17a2b8;
        }

        .consultation-completed .consultation-header {
            background-color: #28a745;
        }

        .consultation-cancelled .consultation-header {
            background-color: #6c757d;
        }

        .nav-tabs .nav-link {
            border-radius: 0.5rem 0.5rem 0 0;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-bottom-color: #f8f9fa;
        }

        .tab-content {
            background-color: #f8f9fa;
            border-radius: 0 0 0.5rem 0.5rem;
            padding: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/#about">О портале</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/professions.php">Каталог профессий</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/groups.php">Рабочие группы</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Личный кабинет
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/cabinet.php">Профиль</a></li>
                            <li><a class="dropdown-item active" href="/consultant/index.php">Панель консультанта</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/auth/logout.php">Выход</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1 class="mb-4">Панель консультанта</h1>

        <div class="row">
            <div class="col-md-4">
                <!-- Карточка консультанта -->
                <div class="consultant-card">
                    <h2>Здравствуйте, <?php echo htmlspecialchars($userName); ?>!</h2>
                    <p>Ваша специализация:
                        <strong><?php echo htmlspecialchars($consultant_data['specialization'] ?? 'Не указана'); ?></strong>
                    </p>
                    <p>Опыт работы:
                        <strong><?php echo htmlspecialchars($consultant_data['experience'] ?? 'Не указан'); ?></strong>
                    </p>
                    <p>Образование:
                        <strong><?php echo htmlspecialchars($consultant_data['education'] ?? 'Не указано'); ?></strong>
                    </p>

                    <div class="consultant-stats">
                        <div class="stat-item stat-pending">
                            <div class="stat-value"><?php echo intval($consultationStats['pending'] ?? 0); ?></div>
                            <div class="stat-label">Ожидают</div>
                        </div>
                        <div class="stat-item stat-scheduled">
                            <div class="stat-value"><?php echo intval($consultationStats['scheduled'] ?? 0); ?></div>
                            <div class="stat-label">Запланировано</div>
                        </div>
                        <div class="stat-item stat-completed">
                            <div class="stat-value"><?php echo intval($consultationStats['completed'] ?? 0); ?></div>
                            <div class="stat-label">Завершено</div>
                        </div>
                    </div>
                </div>

                <!-- Календарь консультаций -->
                <div class="consultant-card">
                    <h3>Ближайшие консультации</h3>
                    <?php if (count($scheduledConsultations) > 0): ?>
                        <ul class="list-group">
                            <?php foreach (array_slice($scheduledConsultations, 0, 3) as $consultation): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($consultation['topic']); ?></strong>
                                        <small class="d-block text-muted">
                                            <?php echo htmlspecialchars($consultation['user_name']); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-info rounded-pill">
                                        <?php echo date('d.m.Y H:i', strtotime($consultation['scheduled_at'])); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($consultations) > 3): ?>
                            <div class="text-center mt-3">
                                <a href="#scheduled" data-bs-toggle="tab" class="btn btn-outline-primary btn-sm">Показать
                                    все</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">У вас нет запланированных консультаций.</p>
                    <?php endif; ?>
                </div>

                <!-- Рейтинг консультанта -->
                <div class="consultant-card">
                    <h3>Ваш рейтинг</h3>
                    <div class="text-center">
                        <div class="display-4"><?php echo number_format($consultant_data['avg_rating'] ?? 0, 1); ?>
                        </div>
                        <div class="stars fs-3 text-warning">
                            <?php
                            $rating = round($consultant_data['avg_rating'] ?? 0);
                            for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas <?php echo ($i <= $rating) ? 'fa-star' : 'fa-star-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-muted mt-2">Основано на
                            <?php echo intval($consultant_data['consultations_count'] ?? 0); ?> отзывах
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Вкладки с консультациями -->
                <ul class="nav nav-tabs" id="consultationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending" role="tab"
                            aria-controls="pending" aria-selected="true">
                            Ожидают (<?php echo count($pendingConsultations); ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="scheduled-tab" data-bs-toggle="tab" href="#scheduled" role="tab"
                            aria-controls="scheduled" aria-selected="false">
                            Запланированные (<?php echo count($scheduledConsultations); ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="completed-tab" data-bs-toggle="tab" href="#completed" role="tab"
                            aria-controls="completed" aria-selected="false">
                            Завершенные (<?php echo count($completedConsultations); ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="cancelled-tab" data-bs-toggle="tab" href="#cancelled" role="tab"
                            aria-controls="cancelled" aria-selected="false">
                            Отмененные (<?php echo count($cancelledConsultations); ?>)
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="consultationTabsContent">
                    <!-- Ожидающие консультации -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <?php if (count($pendingConsultations) > 0): ?>
                            <?php foreach ($pendingConsultations as $consultation): ?>
                                <div class="card consultation-card consultation-pending">
                                    <div class="consultation-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="mb-0"><?php echo htmlspecialchars($consultation['topic']); ?></h4>
                                            <span class="badge bg-warning">Ожидает</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Студент:</strong>
                                                    <?php echo htmlspecialchars($consultation['user_name']); ?></p>
                                                <p><strong>Дата запроса:</strong>
                                                    <?php echo date('d.m.Y H:i', strtotime($consultation['created_at'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Тема:</strong>
                                                    <?php echo htmlspecialchars($consultation['topic']); ?></p>
                                                <p><strong>Описание:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($consultation['message'])); ?></p>
                                            </div>
                                        </div>
                                        <hr>
                                        <form action="/api/update_consultation.php" method="post" class="row g-3">
                                            <input type="hidden" name="consultation_id"
                                                value="<?php echo $consultation['id']; ?>">
                                            <input type="hidden" name="action" value="schedule">

                                            <div class="col-md-6">
                                                <label for="scheduled_at_<?php echo $consultation['id']; ?>"
                                                    class="form-label">Дата и время консультации</label>
                                                <input type="datetime-local" class="form-control"
                                                    id="scheduled_at_<?php echo $consultation['id']; ?>" name="scheduled_at"
                                                    required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="duration_<?php echo $consultation['id']; ?>"
                                                    class="form-label">Длительность (минут)</label>
                                                <select class="form-select" id="duration_<?php echo $consultation['id']; ?>"
                                                    name="duration">
                                                    <option value="30">30 минут</option>
                                                    <option value="45">45 минут</option>
                                                    <option value="60" selected>60 минут</option>
                                                    <option value="90">90 минут</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="consultant_notes_<?php echo $consultation['id']; ?>"
                                                    class="form-label">Ваш комментарий</label>
                                                <textarea class="form-control"
                                                    id="consultant_notes_<?php echo $consultation['id']; ?>"
                                                    name="consultant_notes" rows="3"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">Подтвердить консультацию</button>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                                    data-bs-target="#cancelModal<?php echo $consultation['id']; ?>">
                                                    Отклонить
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Модальное окно для отмены -->
                                <div class="modal fade" id="cancelModal<?php echo $consultation['id']; ?>" tabindex="-1"
                                    aria-labelledby="cancelModalLabel<?php echo $consultation['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="cancelModalLabel<?php echo $consultation['id']; ?>">
                                                    Отклонение консультации</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <form action="/api/update_consultation.php" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="consultation_id"
                                                        value="<?php echo $consultation['id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">

                                                    <div class="mb-3">
                                                        <label for="cancel_reason_<?php echo $consultation['id']; ?>"
                                                            class="form-label">Причина отклонения</label>
                                                        <textarea class="form-control"
                                                            id="cancel_reason_<?php echo $consultation['id']; ?>"
                                                            name="cancel_reason" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Отмена</button>
                                                    <button type="submit" class="btn btn-danger">Отклонить консультацию</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">У вас нет ожидающих консультаций.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Запланированные консультации -->
                    <div class="tab-pane fade" id="scheduled" role="tabpanel" aria-labelledby="scheduled-tab">
                        <?php if (count($scheduledConsultations) > 0): ?>
                            <?php foreach ($scheduledConsultations as $consultation): ?>
                                <div class="card consultation-card consultation-scheduled">
                                    <div class="consultation-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="mb-0"><?php echo htmlspecialchars($consultation['topic']); ?></h4>
                                            <span class="badge bg-info">Запланирована</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Студент:</strong>
                                                    <?php echo htmlspecialchars($consultation['user_name']); ?></p>
                                                <p><strong>Дата и время:</strong> <span
                                                        class="text-primary"><?php echo date('d.m.Y H:i', strtotime($consultation['scheduled_at'])); ?></span>
                                                </p>
                                                <p><strong>Длительность:</strong>
                                                    <?php echo htmlspecialchars($consultation['duration']); ?> минут</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Тема:</strong>
                                                    <?php echo htmlspecialchars($consultation['topic']); ?></p>
                                                <p><strong>Описание от студента:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($consultation['message'])); ?></p>
                                                <?php if (!empty($consultation['consultant_notes'])): ?>
                                                    <p><strong>Ваш комментарий:</strong></p>
                                                    <p><?php echo nl2br(htmlspecialchars($consultation['consultant_notes'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                                data-bs-target="#completeModal<?php echo $consultation['id']; ?>">
                                                Отметить как завершенную
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#cancelScheduledModal<?php echo $consultation['id']; ?>">
                                                Отменить
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Модальное окно для завершения -->
                                <div class="modal fade" id="completeModal<?php echo $consultation['id']; ?>" tabindex="-1"
                                    aria-labelledby="completeModalLabel<?php echo $consultation['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"
                                                    id="completeModalLabel<?php echo $consultation['id']; ?>">Завершение
                                                    консультации</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <form action="/api/update_consultation.php" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="consultation_id"
                                                        value="<?php echo $consultation['id']; ?>">
                                                    <input type="hidden" name="action" value="complete">

                                                    <div class="mb-3">
                                                        <label for="completion_notes_<?php echo $consultation['id']; ?>"
                                                            class="form-label">Итоги консультации</label>
                                                        <textarea class="form-control"
                                                            id="completion_notes_<?php echo $consultation['id']; ?>"
                                                            name="completion_notes" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Отмена</button>
                                                    <button type="submit" class="btn btn-success">Завершить
                                                        консультацию</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Модальное окно для отмены запланированной консультации -->
                                <div class="modal fade" id="cancelScheduledModal<?php echo $consultation['id']; ?>"
                                    tabindex="-1" aria-labelledby="cancelScheduledModalLabel<?php echo $consultation['id']; ?>"
                                    aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"
                                                    id="cancelScheduledModalLabel<?php echo $consultation['id']; ?>">Отмена
                                                    запланированной консультации</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <form action="/api/update_consultation.php" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="consultation_id"
                                                        value="<?php echo $consultation['id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">

                                                    <div class="mb-3">
                                                        <label for="cancel_reason_scheduled_<?php echo $consultation['id']; ?>"
                                                            class="form-label">Причина отмены</label>
                                                        <textarea class="form-control"
                                                            id="cancel_reason_scheduled_<?php echo $consultation['id']; ?>"
                                                            name="cancel_reason" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Отмена</button>
                                                    <button type="submit" class="btn btn-danger">Отменить консультацию</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">У вас нет запланированных консультаций.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Завершенные консультации -->
                    <div class="tab-pane fade" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                        <?php if (count($completedConsultations) > 0): ?>
                            <?php foreach ($completedConsultations as $consultation): ?>
                                <div class="card consultation-card consultation-completed mb-3">
                                    <div class="consultation-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="mb-0"><?php echo htmlspecialchars($consultation['topic']); ?></h4>
                                            <span class="badge bg-success">Завершена</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Студент:</strong>
                                                    <?php echo htmlspecialchars($consultation['user_name']); ?></p>
                                                <p><strong>Дата проведения:</strong>
                                                    <?php echo date('d.m.Y H:i', strtotime($consultation['completed_at'])); ?>
                                                </p>
                                                <p><strong>Рейтинг:</strong>
                                                    <?php if ($consultation['rating']): ?>
                                                        <span class="text-warning">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i
                                                                    class="fas <?php echo ($i <= $consultation['rating']) ? 'fa-star' : 'fa-star-o'; ?>"></i>
                                                            <?php endfor; ?>
                                                        </span>
                                                        (<?php echo $consultation['rating']; ?>/5)
                                                    <?php else: ?>
                                                        <span class="text-muted">Нет оценки</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Итоги консультации:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($consultation['completion_notes'])); ?></p>
                                                <?php if (!empty($consultation['user_feedback'])): ?>
                                                    <p><strong>Отзыв студента:</strong></p>
                                                    <p><?php echo nl2br(htmlspecialchars($consultation['user_feedback'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">У вас нет завершенных консультаций.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Отмененные консультации -->
                    <div class="tab-pane fade" id="cancelled" role="tabpanel" aria-labelledby="cancelled-tab">
                        <?php if (count($cancelledConsultations) > 0): ?>
                            <?php foreach ($cancelledConsultations as $consultation): ?>
                                <div class="card consultation-card consultation-cancelled mb-3">
                                    <div class="consultation-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="mb-0"><?php echo htmlspecialchars($consultation['topic']); ?></h4>
                                            <span class="badge bg-secondary">Отменена</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Студент:</strong>
                                                    <?php echo htmlspecialchars($consultation['user_name']); ?></p>
                                                <p><strong>Дата запроса:</strong>
                                                    <?php echo date('d.m.Y H:i', strtotime($consultation['created_at'])); ?></p>
                                                <?php if ($consultation['scheduled_at']): ?>
                                                    <p><strong>Планировалась на:</strong>
                                                        <?php echo date('d.m.Y H:i', strtotime($consultation['scheduled_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Причина отмены:</strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($consultation['cancel_reason'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">У вас нет отмененных консультаций.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Портал ИТ-профессий</h5>
                    <p>Информационная платформа о профессиях в ИТ-сфере, способствующая профессиональной ориентации,
                        выбору карьерного пути и профессиональному развитию.</p>
                </div>
                <div class="col-md-3">
                    <h5>Навигация</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Главная</a></li>
                        <li><a href="/professions.php" class="text-white">Каталог профессий</a></li>
                        <li><a href="/groups.php" class="text-white">Рабочие группы</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Контакты</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@it-professions.ru</li>
                        <li><i class="fas fa-phone me-2"></i> +7 (999) 123-45-67</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">© 2025 Портал ИТ-профессий. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
