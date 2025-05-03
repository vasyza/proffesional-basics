<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Ошибочка');
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT isPublic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo json_encode(['isPublic' => (bool)$result['isPublic']]);
    } else {
        header('Ошибочка');
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>