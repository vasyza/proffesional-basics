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
    $professionId = isset($_GET['profession_id']) ? (int)$_GET['profession_id'] : null;
    
    if (!$professionId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID профессии не указан']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT ptc.*, c.name as criterion_name, c.description as criterion_description
        FROM profession_to_criteria ptc
        JOIN pvk_criteria c ON ptc.criterion_id = c.id
        WHERE ptc.profession_id = ?
        ORDER BY c.name
    ");
    
    $stmt->execute([$professionId]);
    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($criteria);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
