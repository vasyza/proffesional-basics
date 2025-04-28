<?php

require_once __DIR__ . '/help_funcs.php';
require_once __DIR__ . '/db_managers.php';

clearValidationErrors();

// данные из post в переменные
$login = $_POST['login'];
$password = $_POST['password'];

$logf = fopen('logs/log.txt', 'a');
fwrite($logf, "\nauthorization:\n");
fwrite($logf, $login . ' ' . $password . "\n");

// валидация
if (empty ($login)) {
    setValidationError('login', 'Пустой login!');
}
if (empty ($password)) {
    setValidationError('password', 'Пустой пароль!');
}


$user = getUserByLogin($login);

// вход

// проверка пароля
if (!hasValidationErrors() && $password != $user['password']) {
    setMessage('error', 'Неверный пароль');

    fwrite($logf, join(" ", $_SESSION['message']) . "\n");
    fclose($logf);

    redirect('../pages/auth.php');
}

// если в валидации какие-то ошибки, то обратно

if (hasValidationErrors()) {
    // setUserMenuDisplay(true);
    setOldValue('login', $login);

    fwrite($logf, join(' ', $_SESSION['validation']) . "\n");
    fclose($logf);

    redirect('../pages/auth.php');
}

// успешный заход
fwrite($logf, $user['id'] . " log in.\n");
fclose($logf);

$_SESSION['user']['id'] = $user['id'];

// setUserMenuDisplay(false);
redirect('../pages/tests.php');