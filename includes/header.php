<?php
// Проверка, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Портал ИТ-профессий'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/progress-bar.css">
    <!-- Custom JavaScript -->
    <script src="/js/test-progress.js"></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card-profession {
            transition: transform 0.3s;
        }
        .card-profession:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <li class="nav-item">
                        <a class="nav-link" href="/groups.php">Рабочие группы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/group_roles.php">Роли в группах</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Личный кабинет
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="/cabinet.php">Профиль</a></li>
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a class="dropdown-item" href="/admin/index.php">Панель администратора</a></li>
                                <?php elseif ($userRole === 'expert'): ?>
                                    <li><a class="dropdown-item" href="/expert/index.php">Панель эксперта</a></li>
                                <?php elseif ($userRole === 'consultant'): ?>
                                    <li><a class="dropdown-item" href="/consultant/index.php">Панель консультанта</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/auth/logout.php">Выход</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="/auth/login.php">Вход</a>
                        <a class="nav-link" href="/auth/register.php">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content py-5"  id="main-content"> 