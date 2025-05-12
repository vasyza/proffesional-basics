<?php
session_start();
require_once 'config.php';

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/login.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

// Получение данных из формы
$request_id = filter_var($_POST['request_id'] ?? 0, FILTER_VALIDATE_INT);
$status = clean_input($_POST['status'] ?? '');
$comment = clean_input($_POST['description'] ?? '');

// Валидация данных
if (!$request_id || !in_array($status, ['approved', 'rejected', 'pending'])) {
    header('Location: /admin/role_requests.php?error=' . urlencode("Некорректные данные"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Получение текущего описания и запрошенной роли
    $stmt = $pdo->prepare("
        SELECT description, user_id, requested_role 
        FROM role_requests 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $request_id]);
    $requestData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requestData) {
        header('Location: /admin/role_requests.php?error=' . urlencode("Запрос не найден"));
        exit;
    }

    $currentDescription = $requestData['description'];
    $userId = $requestData['user_id'];
    $requestedRole = $requestData['requested_role'];

    // Формирование нового описания
    if (!empty($comment)) {
        $newDescription = $currentDescription . "\n\nКомментарий от администратора:\n" . $comment;
    } else {
        $newDescription = $currentDescription;
    }

    // Начало транзакции
    $pdo->beginTransaction();

    // Обновление запроса
    $stmt = $pdo->prepare("
        UPDATE role_requests 
        SET status = :status, description = :description, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $status,
        ':description' => $newDescription,
        ':id' => $request_id
    ]);

    // Если статус - "approved", обновляем роль пользователя
    if ($status === 'approved') {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET role = :role 
            WHERE id = :user_id
        ");
        $stmt->execute([
            ':role' => $requestedRole,
            ':user_id' => $userId
        ]);
    }

    // Завершение транзакции
    $pdo->commit();

    header('Location: /admin/role_requests.php?success=' . urlencode("Запрос успешно обновлен"));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Ошибка при обновлении запроса: " . $e->getMessage());
    header('Location: /admin/role_requests.php?error=' . urlencode("Ошибка базы данных"));
    exit;
}
?>
