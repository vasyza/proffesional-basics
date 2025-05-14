<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$username = $_SESSION['user_name'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$isPublic = $input['is_public'] ?? false;

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("UPDATE users SET ispublic = ? WHERE id = ?");
    $stmt->execute([$isPublic ? 1 : 0, $_SESSION['user_id']]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
