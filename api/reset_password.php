<?php
// Включение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/login.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

// Получение и валидация данных
$token = filter_var(trim($_POST['token']), FILTER_SANITIZE_STRING);
$userId = filter_var(trim($_POST['user_id']), FILTER_SANITIZE_NUMBER_INT);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);

// Проверка наличия всех обязательных полей
if (empty($token) || empty($userId) || empty($password) || empty($confirm_password)) {
    header('Location: /auth/reset.php?token=' . urlencode($token) . '&error=' . urlencode("Все поля обязательны для заполнения"));
    exit;
}

// Проверка длины пароля
if (strlen($password) < 6) {
    header('Location: /auth/reset.php?token=' . urlencode($token) . '&error=' . urlencode("Пароль должен содержать не менее 6 символов"));
    exit;
}

// Проверка совпадения паролей
if ($password !== $confirm_password) {
    header('Location: /auth/reset.php?token=' . urlencode($token) . '&error=' . urlencode("Пароли не совпадают"));
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Проверка валидности токена
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.login
        FROM password_reset_tokens t
        JOIN users u ON t.user_id = u.id
        WHERE t.token = ?
          AND t.user_id = ?
          AND t.used = 0
          AND t.expiry > NOW()
    ");
    $stmt->execute([$token, $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: /auth/login.php?error=' . urlencode("Ссылка для сброса пароля недействительна или устарела"));
        exit;
    }
    
    // Хеширование пароля (в реальном проекте лучше использовать password_hash)
    $hashed_password = md5($password);
    
    // Обновление пароля пользователя
    $stmt = $pdo->prepare("UPDATE users SET pass = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $userId]);
    
    // Отметка токена как использованного
    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1, updated_at = NOW() WHERE token = ?");
    $stmt->execute([$token]);
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    // Перенаправление на страницу входа
    header('Location: /auth/login.php?success=' . urlencode("Пароль успешно изменен. Теперь вы можете войти в систему."));
    exit;
    
} catch (PDOException $e) {
    // Отменяем транзакцию в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Логирование ошибки
    error_log("Ошибка при сбросе пароля: " . $e->getMessage());
    
    // Перенаправление с сообщением об ошибке
    header('Location: /auth/reset.php?token=' . urlencode($token) . '&error=' . urlencode("Произошла ошибка при сбросе пароля"));
    exit;
}
?> 