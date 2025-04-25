<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Подключение к базе данных
try {
    $pdo = getDbConnection();

    // Получаем данные пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        // Если пользователь не найден (что странно, но на всякий случай)
        session_destroy();
        header("Location: /auth/login.php");
        exit;
    }

    // Получаем группы пользователя
    // $stmt = $pdo->prepare("
    //     SELECT sg.id, sg.name, gm.role
    //     FROM student_groups sg
    //     JOIN group_members gm ON sg.id = gm.group_id
    //     WHERE gm.user_id = ?
    // ");
    // $stmt->execute([$userId]);
    // $user_groups = $stmt->fetchAll();

    // Для экспертов - получаем их оценки профессий
    $expert_ratings = [];
    if ($userRole == 'expert') {
        $stmt = $pdo->prepare("
            SELECT er.*, p.title as profession_title
            FROM expert_ratings er
            JOIN professions p ON er.profession_id = p.id
            WHERE er.expert_id = ?
            ORDER BY er.created_at DESC
        ");
        $stmt->execute([$userId]);
        $expert_ratings = $stmt->fetchAll();
    }

    // Для консультантов - получаем их консультации
    $consultations = [];
    if ($userRole == 'consultant') {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as user_name
            FROM consultations c
            JOIN users u ON c.user_id = u.id
            WHERE c.consultant_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        $consultations = $stmt->fetchAll();
    } else {
        // Для обычных пользователей - получаем их запросы на консультации
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as consultant_name
            FROM consultations c
            LEFT JOIN users u ON c.consultant_id = u.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        $consultations = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    die("Ошибка при подключении к базе данных: " . $e->getMessage());
}

// Подключение заголовка
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div style="width: 100px; height: 100px;"
                            class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto profile-img">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['login']); ?></p>

                    <?php if ($userRole): ?>
                        <div class="mb-2">
                            <?php
                            $roleBadge = '';
                            switch ($userRole) {
                                case 'admin':
                                    $roleBadge = '<span class="badge bg-danger">Администратор</span>';
                                    break;
                                case 'expert':
                                    $roleBadge = '<span class="badge bg-success">Эксперт</span>';
                                    break;
                                case 'consultant':
                                    $roleBadge = '<span class="badge bg-info">Консультант</span>';
                                    break;
                                default:
                                    $roleBadge = '<span class="badge bg-secondary">Пользователь</span>';
                                    break;
                            }
                            echo $roleBadge;
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="/edit_profile.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Редактировать профиль
                        </a>
                    </div>
                </div>
            </div>

            <!-- <?php if ($user_groups && count($user_groups) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Мои группы</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($user_groups as $group): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="/groups.php?id=<?php echo $group['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </a>
                                    <span
                                        class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($group['role']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="/groups.php" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-users me-1"></i> Все группы
                        </a>
                    </div>
                </div>
            <?php endif; ?> -->
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Информация пользователя</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Имя:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Логин:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($user['login']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Роль:</div>
                        <div class="col-md-8">
                            <?php
                            $roleText = '';
                            switch ($userRole) {
                                case 'admin':
                                    $roleText = 'Администратор';
                                    break;
                                case 'expert':
                                    $roleText = 'Эксперт';
                                    break;
                                case 'consultant':
                                    $roleText = 'Консультант';
                                    break;
                                default:
                                    $roleText = 'Пользователь';
                                    break;
                            }
                            echo $roleText;
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 fw-bold">Дата регистрации:</div>
                        <div class="col-md-8"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>
            </div>

            <?php if (count($consultations) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <?php echo $userRole == 'consultant' ? 'Консультации пользователей' : 'Мои запросы на консультации'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Тема</th>
                                        <th>
                                            <?php echo $userRole == 'consultant' ? 'Пользователь' : 'Консультант'; ?>
                                        </th>
                                        <th>Дата</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consultations as $consultation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($consultation['topic']); ?></td>
                                            <td>
                                                <?php
                                                if ($userRole == 'consultant') {
                                                    echo htmlspecialchars($consultation['user_name']);
                                                } else {
                                                    echo $consultation['consultant_id']
                                                        ? htmlspecialchars($consultation['consultant_name'])
                                                        : '<span class="text-muted">Не назначен</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($consultation['created_at'])); ?></td>
                                            <td>
                                                <?php
                                                $statusBadge = '';
                                                switch ($consultation['status']) {
                                                    case 'pending':
                                                        $statusBadge = '<span class="badge bg-warning text-dark">В ожидании</span>';
                                                        break;
                                                    case 'accepted':
                                                        $statusBadge = '<span class="badge bg-info">Принята</span>';
                                                        break;
                                                    case 'completed':
                                                        $statusBadge = '<span class="badge bg-success">Завершена</span>';
                                                        break;
                                                    case 'cancelled':
                                                        $statusBadge = '<span class="badge bg-danger">Отменена</span>';
                                                        break;
                                                    default:
                                                        $statusBadge = '<span class="badge bg-secondary">Неизвестно</span>';
                                                        break;
                                                }
                                                echo $statusBadge;
                                                ?>
                                            </td>
                                            <td>
                                                <a href="/consultation.php?id=<?php echo $consultation['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($userRole != 'consultant'): ?>
                        <div class="card-footer bg-white">
                            <a href="/consultations.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Запросить новую консультацию
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($userRole == 'expert' && count($expert_ratings) > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Мои оценки профессий</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Профессия</th>
                                        <th>Оценка</th>
                                        <th>Дата</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expert_ratings as $rating): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rating['profession_title']); ?></td>
                                            <td>
                                                <div class="rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $rating['rating']): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('d.m.Y', strtotime($rating['created_at'])); ?></td>
                                            <td>
                                                <a href="/professions.php?id=<?php echo $rating['profession_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($userRole == 'admin'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Панель администратора</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="/admin/users.php" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-users me-2"></i>
                                    Управление пользователями
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="/admin/professions.php" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-briefcase me-2"></i>
                                    Управление профессиями
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="/admin/consultations.php" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-comments me-2"></i>
                                    Управление консультациями
                                </a>
                            </div>
                            <!-- <div class="col-md-6 mb-3">
                                <a href="/admin/manage_groups.php" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-user-friends me-2"></i>
                                    Управление группами
                                </a>
                            </div> -->
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Подключение подвала
include 'includes/footer.php';
?>