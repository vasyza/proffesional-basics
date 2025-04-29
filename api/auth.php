<?php
// Подключение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

$login = filter_var(trim($_POST['login']), FILTER_SANITIZE_STRING);
$pass = filter_var(trim($_POST['pass']), FILTER_SANITIZE_STRING);

if (mb_strlen($login) < 3 || mb_strlen($login) > 90) {
    header('Location: /auth/login.php?error=' . urlencode("Недопустимая длина логина. Длина логина - от 3 до 90 символов"));
    exit();
}
if (mb_strlen($pass) < 6 || mb_strlen($pass) > 16) {
    header('Location: /auth/login.php?error=' . urlencode("Недопустимая длина пароля. Длина пароля - от 6 до 16 символов"));
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