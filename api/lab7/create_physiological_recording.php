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

// Required fields validation
$required_fields = ['user_id', 'session_name', 'recording_type', 'duration_seconds'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
}

$user_id = intval($input['user_id']);
$session_name = trim($input['session_name']);
$recording_type = trim($input['recording_type']);
$duration_seconds = intval($input['duration_seconds']);
$test_session_id = isset($input['test_session_id']) ? intval($input['test_session_id']) : null;
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Validate recording type
$allowed_types = ['EEG', 'ECG', 'EMG', 'EOG', 'GSR', 'PPG', 'MIXED'];
if (!in_array($recording_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid recording type. Allowed: ' . implode(', ', $allowed_types)]);
    exit;
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Check if test_session_id exists (if provided)
    if ($test_session_id) {
        $stmt = $pdo->prepare("SELECT id FROM test_sessions WHERE id = ? AND user_id = ?");
        $stmt->execute([$test_session_id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Test session not found or does not belong to the specified user']);
            exit;
        }
    }
    
    // Insert physiological recording session
    $stmt = $pdo->prepare("
        INSERT INTO physiological_recordings 
        (user_id, test_session_id, session_name, recording_type, duration_seconds, notes, recorded_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $test_session_id,
        $session_name,
        $recording_type,
        $duration_seconds,
        $notes,
        $_SESSION['user_id']
    ]);
    
    $recording_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'recording_id' => $recording_id,
        'message' => 'Physiological recording session created successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
