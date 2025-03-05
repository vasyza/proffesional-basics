<?php
// Включение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/recover.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

// Получение и валидация данных
$login = filter_var(trim($_POST['login']), FILTER_SANITIZE_STRING);

// Проверка наличия логина
if (empty($login)) {
    header('Location: /auth/recover.php?error=' . urlencode("Необходимо указать логин"));
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Проверка существования пользователя с таким логином
    $stmt = $pdo->prepare("SELECT id, name, login FROM users WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // По соображениям безопасности не сообщаем, что пользователь не найден
        header('Location: /auth/recover.php?success=' . urlencode("Если указанный логин существует в нашей системе, инструкции по восстановлению пароля будут отправлены."));
        exit;
    }
    
    // Генерация токена для сброса пароля
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 3600); // Срок действия 1 час
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Сохранение токена в базе данных
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens (user_id, token, expiry, created_at)
        VALUES (:user_id, :token, :expiry, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $user['id'],
        ':token' => $token,
        ':expiry' => $expiry
    ]);
    
    // Создание ссылки для сброса пароля
    $resetLink = "http://{$_SERVER['HTTP_HOST']}/auth/reset.php?token=" . $token;
    
    // В реальном проекте здесь должна быть отправка сообщения пользователю
    // Поскольку по ТЗ логика с email удалена, выводим ссылку на экран
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    // Логирование (для отладки)
    error_log("Сформирован запрос на восстановление пароля для {$login}. Ссылка: {$resetLink}");
    
    // Перенаправление на страницу с успешным сообщением
    header('Location: /auth/recover.php?success=' . urlencode("Инструкции по восстановлению пароля сформированы. " . 
                                                           "В реальном проекте ссылка была бы отправлена вам. " . 
                                                           "Ссылка для восстановления: " . $resetLink));
    exit;
    
} catch (PDOException $e) {
    // Отменяем транзакцию в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Логирование ошибки
    error_log("Ошибка при восстановлении пароля: " . $e->getMessage());
    
    // Перенаправление с сообщением об ошибке
    header('Location: /auth/recover.php?error=' . urlencode("Произошла ошибка. Пожалуйста, попробуйте позже."));
    exit;
}
?> 