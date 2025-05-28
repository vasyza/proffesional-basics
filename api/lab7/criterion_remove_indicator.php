<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin/expert role
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'expert')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin or expert role required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['indicator_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Indicator ID is required']);
    exit;
}

$indicator_id = intval($input['indicator_id']);

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Check if the indicator exists
    $stmt = $pdo->prepare("SELECT id FROM criterion_test_indicators WHERE id = ?");
    $stmt->execute([$indicator_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Indicator not found']);
        exit;
    }
    
    // Remove the indicator
    $stmt = $pdo->prepare("DELETE FROM criterion_test_indicators WHERE id = ?");
    $stmt->execute([$indicator_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test indicator removed successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
