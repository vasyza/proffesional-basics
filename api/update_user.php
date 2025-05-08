<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Проверка данных формы
if (!isset($_POST['id'], $_POST['name'], $_POST['login'], $_POST['role'], $_POST['age'], $_POST['gender'])) {
    header("Location: /admin/users.php?error=" . urlencode('Не все данные были переданы.'));
    exit;
}

$userId = intval($_POST['id']);
$name = trim($_POST['name']);
$login = trim($_POST['login']);
$role = trim($_POST['role']);
$age = filter_var(trim($_POST['age']), FILTER_VALIDATE_INT, ['options' => ['min_range' => 12]]);
$gender = trim($_POST['gender']);
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';

// Нельзя обновлять самого себя через этот скрипт
if ($userId == $_SESSION['user_id']) {
    header("Location: /admin/users.php?error=" . urlencode('Вы не можете редактировать себя здесь.'));
    exit;
}

// Валидация основных данных
if (strlen($name) < 1 || strlen($login) < 3) {
    header("Location: /admin/users.php?error=" . urlencode('Некорректные данные имени или логина.'));
    exit;
}

if (!in_array($role, ['admin', 'expert', 'consultant', 'user'])) {
    header("Location: /admin/users.php?error=" . urlencode('Некорректная роль.'));
    exit;
}

if ($age === false) {
    header("Location: /admin/users.php?error=" . urlencode('Некорректный возраст (должен быть от 12 лет).'));
    exit;
}

if (!in_array($gender, ['мужской', 'женский'])) {
    header("Location: /admin/users.php?error=" . urlencode('Некорректный пол.'));
    exit;
}

try {
    $pdo = getDbConnection();

    // Проверка, что логин уникален (кроме текущего пользователя)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ? AND id != ?");
    $stmt->execute([$login, $userId]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        header("Location: /admin/users.php?error=" . urlencode('Этот логин уже занят другим пользователем.'));
        exit;
    }

    // Обновление данных
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = ?, login = ?, role = ?, bio = ?, age = ?, gender = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$name, $login, $role, $bio, $age, $gender, $userId]);

    header("Location: /admin/users.php?success=" . urlencode('Данные пользователя успешно обновлены.'));
    exit;

} catch (PDOException $e) {
    header("Location: /admin/users.php?error=" . urlencode('Ошибка базы данных: ' . $e->getMessage()));
    exit;
}
?>