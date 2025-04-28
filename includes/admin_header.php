<?php 
// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора | Портал ИТ-профессий</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="/admin/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Панель управления
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="/admin/users.php">
                        <i class="fas fa-users me-1"></i>Пользователи
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'professions.php' ? 'active' : ''; ?>" href="/admin/professions.php">
                        <i class="fas fa-briefcase me-1"></i>Профессии
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_qualities.php' ? 'active' : ''; ?>" href="/admin/manage_qualities.php">
                        <i class="fas fa-clipboard-list me-1"></i>ПВК
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_groups.php' ? 'active' : ''; ?>" href="/admin/manage_groups.php">
                        <i class="fas fa-users-cog me-1"></i>Рабочие группы
                    </a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'consultations.php' ? 'active' : ''; ?>" href="/admin/consultations.php">
                        <i class="fas fa-comments me-1"></i>Консультации
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user-shield me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Выход
                </a>
            </div>
        </div>
    </div>
</nav> 