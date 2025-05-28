<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    $criterionId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($criterionId) {
        // Get specific criterion
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as created_by_name 
            FROM pvk_criteria c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$criterionId]);
        $criterion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$criterion) {
            http_response_code(404);
            echo json_encode(['error' => 'Критерий не найден']);
            exit;
        }
        
        echo json_encode($criterion);
    } else {
        // Get all criteria
        $stmt = $pdo->query("
            SELECT c.*, u.name as created_by_name 
            FROM pvk_criteria c
            LEFT JOIN users u ON c.created_by = u.id
            ORDER BY c.name
        ");
        $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($criteria);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
