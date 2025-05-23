<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Ошибочка');
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$isPublic = isset($data['isPublic']) ? (int)$data['isPublic'] : 0;

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE users SET ispublic = ? WHERE id = ?");
    $stmt->execute([$isPublic, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('Ошибочка');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
