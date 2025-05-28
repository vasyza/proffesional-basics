<?php
require_once '../config.php';
require_once '../PvkAssessmentService.php';
session_start();

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id']) || !isset($input['profession_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные входные данные']);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    $professionId = (int)$input['profession_id'];
    
    // Check if user can calculate assessments for this user (admin/expert or self)
    if ($_SESSION['user_id'] != $userId && !in_array($_SESSION['user_role'], ['admin', 'expert'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Доступ запрещен']);
        exit;
    }
    
    $assessmentService = new PvkAssessmentService();
    $result = $assessmentService->calculatePvkLevels($userId, $professionId);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера: ' . $e->getMessage()]);
}
?>
