<?php
// Подключение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

$login = filter_var(trim($_POST['login']), FILTER_SANITIZE_STRING);
$pass = filter_var(trim($_POST['pass']), FILTER_SANITIZE_STRING);

if (mb_strlen($login) < 5 || mb_strlen($login) > 90) {
    header('Location: /auth/login.php?error=' . urlencode("Недопустимая длина логина"));
    exit();
}
if (mb_strlen($pass) < 8 || mb_strlen($pass) > 16) {
    header('Location: /auth/login.php?error=' . urlencode("Недопустимая длина пароля"));
    exit();
}

$pass = md5($pass . "hiferhifurie");

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = :login AND pass = :pass");
    $stmt->execute([
        ':login' => $login,
        ':pass' => $pass
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        header('Location: /auth/login.php?error=' . urlencode("Неверный логин или пароль"));
        exit();
    }

    // Устанавливаем данные пользователя в сессию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];

    // Оставляем cookie как запасной вариант
    setcookie('user_id', $user['id'], time() + 3600, "/");

    header('Location: /');

} catch (PDOException $e) {
    header('Location: /auth/login.php?error=' . urlencode("Ошибка подключения к базе данных: " . $e->getMessage()));
    exit();
}
?>