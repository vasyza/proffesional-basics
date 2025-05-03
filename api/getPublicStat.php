<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
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
        header('HTTP/1.1 404 Not Found');
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>