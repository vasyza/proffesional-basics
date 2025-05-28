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

if (!isset($input['profession_id']) || !isset($input['criterion_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Profession ID and Criterion ID are required']);
    exit;
}

$profession_id = intval($input['profession_id']);
$criterion_id = intval($input['criterion_id']);

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Check if the link exists
    $stmt = $pdo->prepare("SELECT id FROM profession_to_criteria WHERE profession_id = ? AND criterion_id = ?");
    $stmt->execute([$profession_id, $criterion_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Link not found']);
        exit;
    }
    
    // Remove the link
    $stmt = $pdo->prepare("DELETE FROM profession_to_criteria WHERE profession_id = ? AND criterion_id = ?");
    $stmt->execute([$profession_id, $criterion_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Criterion unlinked from profession successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
