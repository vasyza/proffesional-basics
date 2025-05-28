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
    
    if (!$input || !isset($input['name']) || !isset($input['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные входные данные']);
        exit;
    }
    
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO pvk_criteria (name, description, created_by) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $input['name'],
        $input['description'],
        $_SESSION['user_id']
    ]);
    
    $criterionId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'criterion_id' => $criterionId,
        'message' => 'Критерий успешно создан'
    ]);
    
} catch (PDOException $e) {
    if ($e->getCode() === '23505') { // Unique constraint violation
        http_response_code(409);
        echo json_encode(['error' => 'Критерий с таким названием уже существует']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
