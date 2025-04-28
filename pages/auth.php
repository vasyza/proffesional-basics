<?php
require_once dirname(__DIR__) . "/backend/config.php";
require_once dirname(__DIR__) . "/backend/help_funcs.php";
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная</title>
    <link rel="icon" href="../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/auth.css">
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>

    <div class="registration-buttons">
        <button class="registration-button" onclick="document.getElementById('registrationForm').style.display='block';document.getElementById('loginForm').style.display='none';">Регистрация</button>
        <button class="login-button" onclick="document.getElementById('registrationForm').style.display='none';document.getElementById('loginForm').style.display='block';">Авторизация</button>
    </div>

    <div class="container" id="registrationForm">
        <form action="../backend/register_user.php" method="post">
            <label for="login">Логин:</label><br>
            <input type="text" id="login" name="login"><br>

            <label for="name">Имя:</label><br>
            <input type="text" id="name" name="name"><br>
            
            <label for="gender">Пол:</label><br>
            <select id="gender" name="gender">
                <option value="1">Мужской</option>
                <option value="2">Женский</option>
            </select><br>

            <label for="birthdate">Дата рождения:</label><br>
            <input type="date" id="birth_date" name="birth_date"><br>

            <label for="password">Пароль:</label><br>
            <input type="password" id="password" name="password"><br>

            <label for="confirm-password">Подтверждение пароля:</label><br>
            <input type="password" id="confirm-password" name="confirm-password"><br>

            <button type="submit">Зарегистрироваться</button>

            <?php if (hasValidationErrors()): ?>
                <br><br><label>Ошибка в введённых значениях</label>
            <?php endif; ?>
        </form>
    </div>

    <div class="container" id="loginForm">
        <form action="../backend/login_user.php" method="post">
            <label for="login">Логин:</label><br>
            <input type="text" id="login" name="login"><br>

            <label for="password">Пароль:</label><br>
            <input type="password" id="password" name="password"><br>

            <button type="submit">Войти</button>

            <?php if (hasValidationErrors()): ?>
                <br><br><label>Ошибка в введённых значениях</label>
            <?php endif; ?>
        </form>
    </div>

</body>

</html>