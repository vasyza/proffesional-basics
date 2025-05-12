<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Подключение к базе данных
try {
    $pdo = getDbConnection();

    // Получаем статистику для панели администратора

    // Количество пользователей
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // Количество профессий
    $stmt = $pdo->query("SELECT COUNT(*) FROM professions");
    $total_professions = $stmt->fetchColumn();

    // Количество групп
    $stmt = $pdo->query("SELECT COUNT(*) FROM student_groups");
    $total_groups = $stmt->fetchColumn();

    // Количество консультаций
    $stmt = $pdo->query("SELECT COUNT(*) FROM consultations");
    $total_consultations = $stmt->fetchColumn();

    // Последние 5 зарегистрированных пользователей
    $stmt = $pdo->query("
        SELECT * FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_users = $stmt->fetchAll();

    // Последние 5 созданных профессий
    $stmt = $pdo->query("
        SELECT p.*, u.name as creator_name 
        FROM professions p
        LEFT JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recent_professions = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Ошибка при подключении к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/professions.php">Каталог профессий</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="/groups.php">Рабочие группы</a>
                    </li> -->
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Панель администратора
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/admin/index.php">Обзор</a></li>
                            <li><a class="dropdown-item" href="/admin/users.php">Пользователи</a></li>
                            <li><a class="dropdown-item" href="/admin/professions.php">Профессии</a></li>
                            <li><a class="dropdown-item" href="/admin/consultations.php">Консультации</a></li>
                            <!-- <li><a class="dropdown-item" href="/admin/manage_groups.php">Группы</a></li> -->
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/cabinet.php">Личный кабинет</a></li>
                            <li><a class="dropdown-item" href="/auth/logout.php">Выход</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h1>Панель администратора</h1>
                    <!-- <a href="/api/init_db.php" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Вы уверены, что хотите переинициализировать базу данных? Это может привести к потере данных!');">
                        <i class="fas fa-database me-1"></i> Инициализировать БД
                    </a> -->
                </div>
                <p class="lead">Управление порталом ИТ-профессий</p>
            </div>
            <div class="col-md-6 mb-3">
    <a href="manage_qualities.php" class="btn btn-outline-primary btn-lg w-100">
        <i class="fas fa-list me-2"></i>
        ПВК (Список качеств)
    </a>
</div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-users fa-3x"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Пользователи</h5>
                                <h2 class="mb-0"><?php echo $total_users; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="/admin/users.php" class="text-white text-decoration-none">
                            Управление пользователями <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-briefcase fa-3x"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Профессии</h5>
                                <h2 class="mb-0"><?php echo $total_professions; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="/admin/professions.php" class="text-white text-decoration-none">
                            Управление профессиями <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-comments fa-3x"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Консультации</h5>
                                <h2 class="mb-0"><?php echo $total_consultations; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="/admin/consultations.php" class="text-white text-decoration-none">
                            Управление консультациями <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
    <div class="card bg-warning text-dark mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-user-tag fa-3x"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0">Запросы на роли</h5>
                    <h2 class="mb-0">🔍</h2>
                </div>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top-0">
            <a href="/admin/role_requests.php" class="text-dark text-decoration-none">
                Управление запросами <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
            <!-- <div class="col-md-3">
                <div class="card bg-warning text-dark mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-user-friends fa-3x"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Группы</h5>
                                <h2 class="mb-0"><?php echo $total_groups; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="/admin/manage_groups.php" class="text-dark text-decoration-none">
                            Управление группами <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div> -->
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Новые пользователи</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_users) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Имя</th>
                                            <th>Логин</th>
                                            <th>Роль</th>
                                            <th>Дата регистрации</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['login']); ?></td>
                                                <td>
                                                    <?php
                                                    $roleBadge = '';
                                                    switch ($user['role']) {
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
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Нет новых пользователей</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="/admin/users.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-users me-1"></i> Все пользователи
                        </a>
                        <a href="/admin/user_add.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus me-1"></i> Добавить пользователя
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Новые профессии</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_professions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Название</th>
                                            <th>Создатель</th>
                                            <th>Дата создания</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_professions as $profession): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($profession['title']); ?></td>
                                                <td><?php echo $profession['creator_name'] ? htmlspecialchars($profession['creator_name']) : '<span class="text-muted">Система</span>'; ?>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($profession['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Нет новых профессий</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="/admin/professions.php" class="btn btn-success btn-sm">
                            <i class="fas fa-briefcase me-1"></i> Все профессии
                        </a>
                        <a href="/admin/profession_add.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Добавить профессию
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Портал ИТ-профессий</h5>
                    <p>Ваш проводник в мире информационных технологий</p>
                </div>
                <div class="col-md-3">
                    <h5>Ссылки</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white">Главная</a></li>
                        <li><a href="/professions.php" class="text-white">Каталог профессий</a></li>
                        <!-- <li><a href="/groups.php" class="text-white">Рабочие группы</a></li> -->
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Контакты</h5>
                    <ul class="list-unstyled">
                        <li><a href="mailto:info@itportal.ru" class="text-white">info@itportal.ru</a></li>
                        <li><a href="tel:+7123456789" class="text-white">+7 (123) 456-789</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">© 2025 Портал ИТ-профессий. Все права защищены.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
