<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo "Доступ запрещен.";
    exit;
}

// Проверка наличия id пользователя в запросе
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Неверный запрос.";
    exit;
}

$userIdToDelete = intval($_GET['id']);

try {
    $pdo = getDbConnection();

    // Нельзя удалить самого себя (дополнительная проверка безопасности)
    if ($userIdToDelete == $_SESSION['user_id']) {
        http_response_code(400);
        echo "Нельзя удалить свой собственный аккаунт.";
        exit;
    }

    // Проверка: существует ли пользователь
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userIdToDelete]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo "Пользователь не найден.";
        exit;
    }

    // Удаление пользователя
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userIdToDelete]);

    // Опционально можно добавить редирект после успешного удаления
    header("Location: /admin/users.php?success=" . urlencode("Пользователь успешно удален."));
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo "Ошибка базы данных: " . $e->getMessage();
    exit;
}
?>
