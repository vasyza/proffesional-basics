<?php
require_once(dirname(__DIR__) . "/backend/config.php");
require_once(ROOT . "/backend/db_managers.php");

// $_SESSION['user'] = getUserById(1);
$user = currentUser();
?>
<nav class="nav">
    <div style="display: flex; flex: 1; align-items: center; justify-content: center">
        <a href="/pages/main.php" class="nav-link" id="nav_main">
            <h3>Главная</h3>
        </a>
        <a href="/pages/tests.php" class="nav-link" id="nav_tests">
            <h3>Тесты</h3>
        </a>
        <a href="/pages/professions.php" class="nav-link" id="nav_tests">
            <h3>Профессии</h3>
        </a>

        <?php if ($user) : ?>
            <a href="/pages/my_stats.php" class="nav-link" id="nav_my_stats">
                <h3>Моя статистика</h3>
            </a>
        <?php endif; ?>
        <?php if ($user && $user['role_id'] > 0) : ?>
            <a href="/pages/general_stats.php" class="nav-link" id="nav_gen_stats">
                <h3>Общая статистика</h3>
            </a>
        <?php endif; ?>
        <?php if ($user && $user['role_id'] > 1) : ?>
            <a href="/pages/admin_userlist.php" class="nav-link" id="nav_userlist">
                <h3>admin: Список пользователей</h3>
            </a>
            <a href="/pages/admin_weight_change.php" class="nav-link" id="nav_userlist">
                <h3>admin: Корректировка весов</h3>
            </a>
        <?php endif; ?>
    </div>
    <div style="display: flex; align-items: center; column-gap: 6px; padding-right: 32px; position:absolute; right: 0; bottom: 12px">
        <?php if ($user) : ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <p style="font-weight: 500"><?php echo $user['name'] ?></p>
            <a href="/backend/logout.php" style="padding: 8px 12px">Выйти</a>
        <?php else : ?>
            <a id="heading-text" href="/pages/auth.php" style="padding: 8px 12px">Войти / Зарегистрироваться</a>
        <?php endif; ?>
    </div>

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