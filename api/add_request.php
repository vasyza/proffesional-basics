<?php
session_start();
require_once 'config.php';

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/login.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

$requested_role = clean_input($_POST['requested_role'] ?? '');
$description = clean_input($_POST['description'] ?? '');

if (!in_array($requested_role, ['expert', 'consultant'])) {
    header('Location: /role_request.php?error=' . urlencode("Неверная роль"));
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM role_requests WHERE user_id = :user_id AND status = 'pending'");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    
    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        header('Location: /role_request.php?error=' . urlencode("Вы уже отправили запрос на изменение роли"));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO role_requests (user_id, requested_role, description, status, created_at, updated_at)
        VALUES (:user_id, :requested_role, :description, 'pending', NOW(), NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':requested_role' => $requested_role,
        ':description' => $description
    ]);

    header('Location: /role_request.php?success=' . urlencode("Запрос на изменение роли успешно отправлен"));
    exit;

} catch (PDOException $e) {
    error_log("Ошибка: " . $e->getMessage());
    header('Location: /role_request.php?error=' . urlencode("Database error: " . $e->getMessage()));
    exit;
}
?>
