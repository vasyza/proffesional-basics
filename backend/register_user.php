<?php

require_once __DIR__ . '/help_funcs.php';
require_once __DIR__ . '/db_managers.php';

clearValidationErrors();

// данные из post в переменные
$name = $_POST['name'];
$login = $_POST['login'];
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm-password']);
$gender = (int) trim($_POST['gender']);
$birth_date = $_POST['birth_date'];

$logf = fopen('logs/log.txt', 'a');
fwrite($logf, "\nregistration:\n");

$s = join(' ', array($name, $login, $password, $confirm_password, $gender, $birth_date));
fwrite($logf, $s . "\n");
fwrite($logf, gettype($gender));

// валидация

if (empty ($name)) {
    setValidationError('name', 'Пустое имя!');
}

if (empty ($login)) {
    setValidationError('login', 'Пустой логин!');
}
if (empty ($password)) {
    setValidationError('password', 'Пустой пароль!');
}
if ($password != $confirm_password){
    setValidationError('password', 'Пароль не совпадает!');
}

if (hasValidationErrors()) {
    // setUserMenuDisplay(true);
    // echo "error";

    fwrite($logf, join(' ', $_SESSION['validation']) . "\n");
    fclose($logf);

    redirect('../pages/auth.php');
} 

try{
    addUserData($name, $login, 0, $password, $birth_date, $gender);
} catch (\Exception $e){
    setMessage('error', 'Произошла ошибка, возможно, этот логин уже занят.');
    fwrite($logf, $e->getMessage());

    fclose($logf);

    redirect('../pages/auth.php');
}

$user = getUserByLogin($login);
$_SESSION['user']['id'] = $user['id'];

fwrite($logf, getUsers() . "register.\n");
fclose($logf);

// щас находится в backend/register_user.php, значит надо в ../pages/main.php
redirect('../pages/main.php');
