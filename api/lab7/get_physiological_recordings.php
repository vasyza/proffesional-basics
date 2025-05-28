<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$recording_id = isset($_GET['recording_id']) ? intval($_GET['recording_id']) : null;

// Check permissions: users can only see their own data, admins/experts can see all
if (!in_array($_SESSION['role'], ['admin', 'expert'])) {
    $user_id = $_SESSION['user_id'];
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    $query = "
        SELECT 
            pr.id,
            pr.user_id,
            pr.test_session_id,
            pr.session_name,
            pr.recording_type,
            pr.duration_seconds,
            pr.notes,
            pr.created_at,
            u.name as user_name,
            ts.test_type,
            ts.created_at as test_date,
            COUNT(pdp.id) as data_points_count
        FROM physiological_recordings pr
        JOIN users u ON pr.user_id = u.id
        LEFT JOIN test_sessions ts ON pr.test_session_id = ts.id
        LEFT JOIN physiological_data_points pdp ON pr.id = pdp.recording_id
    ";
    
    $params = [];
    $where_conditions = [];
    
    if ($user_id) {
        $where_conditions[] = "pr.user_id = ?";
        $params[] = $user_id;
    }
    
    if ($recording_id) {
        $where_conditions[] = "pr.id = ?";
        $params[] = $recording_id;
    }
    
    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $query .= " GROUP BY pr.id ORDER BY pr.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $recordings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'recordings' => $recordings
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
