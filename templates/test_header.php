<?php

require_once(dirname(__DIR__) . "/backend/config.php");
require_once(ROOT . "/backend/db_managers.php");

// $_SESSION['user'] = getUserById(1);
$user = currentUser();
?>

<header class="header">
    <div class="user-icon">
        <?php if (!$user) : ?>
        <a href="../../pages/auth.php">
            <?php endif; ?>
            <button class="user-icon-button" id="user-icon-button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </button>
            <?php if (!$user) : ?>
        </a>
    <?php endif; ?>
    </div>
    <?php if ($user) : ?>
        <p id="heading-text"><?php echo $user['name'] ?></p>
        <a href="../backend/logout.php" id="end-heading-text">Выйти</a>
    <?php else : ?>
        <label for="user-icon-button" id="heading-text">Войти / Зарегистрироваться</label>
    <?php endif; ?>
</header>
<nav class="nav" style="margin-top: 50px;">
    <a href="../main.php" class="nav-link" id="nav_main">
        <h3>Главная</h3>
    </a>
    <a href="../tests.php" class="nav-link" id="nav_tests">
        <h3>Тесты</h3>
    </a>
    <a href="../professions.php" class="nav-link" id="nav_tests">
        <h3>Профессии</h3>
    </a>

    <?php if ($user) : ?>
        <a href="../my_stats.php" class="nav-link" id="nav_my_stats">
            <h3>Моя статистика</h3>
        </a>
    <?php endif; ?>
    <?php if ($user && $user['role_id'] > 0) : ?>
        <a href="../general_stats.php" class="nav-link" id="nav_gen_stats">
            <h3>Общая статистика</h3>
        </a>
    <?php endif; ?>
    <?php if ($user && $user['role_id'] > 1) : ?>
        <a href="../admin_userlist.php" class="nav-link" id="nav_userlist">
            <h3>admin: Список пользователей</h3>
        </a>
        <a href="../admin_weight_change.php" class="nav-link" id="nav_userlist">
            <h3>admin: Корректировка весов</h3>
        </a>
    <?php endif; ?>

    <!-- <a href="my_stats.php" class="nav-link">
        <h3>Моя статистика</h3>
    </a>
    <a href="general_stats.php" class="nav-link">
        <h3>Общая статистика</h3>
    </a>
    <a href="admin_userlist.php" class="nav-link">
        <h3>admin: Список пользователей</h3>
    </a> -->
</nav>