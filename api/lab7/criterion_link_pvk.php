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
    
    if (!$input || !isset($input['criterion_id']) || !isset($input['pvk_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные входные данные']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    // Check if criterion and PVK exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pvk_criteria WHERE id = ?");
    $stmt->execute([$input['criterion_id']]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Критерий не найден']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professional_qualities WHERE id = ?");
    $stmt->execute([$input['pvk_id']]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'ПВК не найдено']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO criterion_to_pvk (criterion_id, pvk_id) 
        VALUES (?, ?)
        ON CONFLICT (criterion_id, pvk_id) DO NOTHING
    ");
    
    $stmt->execute([
        $input['criterion_id'],
        $input['pvk_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ПВК успешно привязано к критерию'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
