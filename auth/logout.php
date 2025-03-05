<?php
session_start();

// Очищаем все переменные сессии
$_SESSION = array();

// Если есть куки сессии, удаляем их
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Удаляем куки user_id
setcookie('user_id', '', time() - 3600, '/');

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header("Location: /");
exit;
?>