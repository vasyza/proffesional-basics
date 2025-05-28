<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

// Check authorization (admin or expert)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'expert'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['profession_id']) || !isset($input['criterion_id']) || !isset($input['criterion_weight'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные входные данные']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    // Check if profession and criterion exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professions WHERE id = ?");
    $stmt->execute([$input['profession_id']]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Профессия не найдена']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pvk_criteria WHERE id = ?");
    $stmt->execute([$input['criterion_id']]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Критерий не найден']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO profession_to_criteria (profession_id, criterion_id, criterion_weight) 
        VALUES (?, ?, ?)
        ON CONFLICT (profession_id, criterion_id) 
        DO UPDATE SET criterion_weight = EXCLUDED.criterion_weight
    ");
    
    $stmt->execute([
        $input['profession_id'],
        $input['criterion_id'],
        $input['criterion_weight']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Критерий успешно привязан к профессии'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
