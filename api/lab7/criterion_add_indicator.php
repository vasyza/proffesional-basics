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
    
    if (!$input || !isset($input['criterion_id']) || !isset($input['test_type']) || !isset($input['indicator_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные входные данные']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    // Check if criterion exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pvk_criteria WHERE id = ?");
    $stmt->execute([$input['criterion_id']]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Критерий не найден']);
        exit;
    }
    
    // Validate assessment direction
    $allowedDirections = ['higher_is_better', 'lower_is_better'];
    if (!in_array($input['assessment_direction'], $allowedDirections)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверное направление оценки']);
        exit;
    }
    
    // Validate cutoff operator if provided
    $allowedOperators = ['>=', '<=', '>', '<', '==', '!='];
    if (isset($input['cutoff_comparison_operator']) && !in_array($input['cutoff_comparison_operator'], $allowedOperators)) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный оператор сравнения']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO criterion_test_indicators 
        (criterion_id, test_type, indicator_name, indicator_weight, assessment_direction, cutoff_value, cutoff_comparison_operator) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (criterion_id, test_type, indicator_name) 
        DO UPDATE SET 
            indicator_weight = EXCLUDED.indicator_weight,
            assessment_direction = EXCLUDED.assessment_direction,
            cutoff_value = EXCLUDED.cutoff_value,
            cutoff_comparison_operator = EXCLUDED.cutoff_comparison_operator
    ");
    
    $stmt->execute([
        $input['criterion_id'],
        $input['test_type'],
        $input['indicator_name'],
        $input['indicator_weight'] ?? 1.0,
        $input['assessment_direction'],
        $input['cutoff_value'] ?? null,
        $input['cutoff_comparison_operator'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Индикатор успешно добавлен к критерию'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
