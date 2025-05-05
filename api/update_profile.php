<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /cabinet.php?error=" . urlencode("Неверный метод запроса"));
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
$newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Валидация данных
if (empty($name)) {
    header("Location: /edit_profile.php?error=" . urlencode("Имя не может быть пустым"));
    exit;
}

if (empty($gender) || !in_array($gender, ['мужской', 'женский'])) {
    header("Location: /edit_profile.php?error=" . urlencode("Укажите корректный пол"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Получение текущего пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: /edit_profile.php?error=" . urlencode("Пользователь не найден"));
        exit;
    }

    // Подготовка запроса на обновление
    $updateFields = [
        "name" => $name,
        "bio" => $bio,
        "gender" => $gender
    ];

    // Обработка изменения пароля
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        // Проверка пароля
        $currentPasswordHash = md5($currentPassword . "hiferhifurie");

        if ($currentPasswordHash !== $user['pass']) {
            header("Location: /edit_profile.php?error=" . urlencode("Неверный текущий пароль"));
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            header("Location: /edit_profile.php?error=" . urlencode("Новый пароль и подтверждение не совпадают"));
            exit;
        }

        if (strlen($newPassword) < 6) {
            header("Location: /edit_profile.php?error=" . urlencode("Новый пароль должен содержать минимум 6 символов"));
            exit;
        }

        $newPasswordHash = md5($newPassword . "hiferhifurie");
        $updateFields['pass'] = $newPasswordHash;
    }

    // Формирование динамического запроса
    $setParts = [];
    $params = [];
    foreach ($updateFields as $field => $value) {
        $setParts[] = "$field = ?";
        $params[] = $value;
    }
    $params[] = $userId;

    $sql = "UPDATE users SET " . implode(", ", $setParts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $updateStmt = $pdo->prepare($sql);
    $updateStmt->execute($params);

    // Обновляем данные в сессии
    $_SESSION['user_name'] = $name;
    if (isset($newPasswordHash)) {
        $_SESSION['user_pass_updated'] = time();
    }

    header("Location: /edit_profile.php?success=" . urlencode("Профиль успешно обновлен"));
    exit;

} catch (PDOException $e) {
    error_log("Ошибка при обновлении профиля: " . $e->getMessage());
    header("Location: /edit_profile.php?error=" . urlencode("Ошибка базы данных: " . $e->getMessage()));
    exit;
}