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
if (!isset($input['recording_id']) || !isset($input['data_points']) || !is_array($input['data_points'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Recording ID and data_points array are required']);
    exit;
}

$recording_id = intval($input['recording_id']);
$data_points = $input['data_points'];

if (empty($data_points)) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one data point is required']);
    exit;
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Check if recording exists and belongs to current expert
    $stmt = $pdo->prepare("SELECT id, recording_type FROM physiological_recordings WHERE id = ?");
    $stmt->execute([$recording_id]);
    $recording = $stmt->fetch();
    
    if (!$recording) {
        http_response_code(404);
        echo json_encode(['error' => 'Recording not found']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Prepare insert statement for data points
    $stmt = $pdo->prepare("
        INSERT INTO physiological_data_points 
        (recording_id, timestamp_ms, channel, value, unit, quality_indicator) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $inserted_count = 0;
    foreach ($data_points as $point) {
        // Validate data point structure
        if (!isset($point['timestamp_ms']) || !isset($point['value'])) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Each data point must have timestamp_ms and value']);
            exit;
        }
        
        $timestamp_ms = intval($point['timestamp_ms']);
        $channel = isset($point['channel']) ? trim($point['channel']) : 'default';
        $value = floatval($point['value']);
        $unit = isset($point['unit']) ? trim($point['unit']) : 'V';
        $quality_indicator = isset($point['quality_indicator']) ? floatval($point['quality_indicator']) : 1.0;
        
        $stmt->execute([
            $recording_id,
            $timestamp_ms,
            $channel,
            $value,
            $unit,
            $quality_indicator
        ]);
        
        $inserted_count++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'inserted_points' => $inserted_count,
        'message' => "Successfully uploaded $inserted_count data points"
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>
