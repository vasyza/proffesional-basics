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

$user_id = $_SESSION['user_id'];
$profession_id = isset($_GET['profession_id']) ? intval($_GET['profession_id']) : null;

try {
    $pdo = getDbConnection();
    
    $query = "
        SELECT 
            upa.id,
            upa.user_id,
            upa.profession_id,
            upa.pvk_id,
            upa.assessment_score,
            upa.assessment_level,
            upa.last_calculated,
            p.title as profession_name,
            pq.name as pvk_name,
            pq.description as pvk_description
        FROM user_pvk_assessments upa
        JOIN professions p ON upa.profession_id = p.id
        JOIN professional_qualities pq ON upa.pvk_id = pq.id
        WHERE upa.user_id = ?
    ";
    
    $params = [$user_id];
    
    if ($profession_id) {
        $query .= " AND upa.profession_id = ?";
        $params[] = $profession_id;
    }
    
    $query .= " ORDER BY upa.last_calculated DESC, p.title, pq.name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group assessments by profession
    $grouped_assessments = [];
    foreach ($assessments as $assessment) {
        $prof_id = $assessment['profession_id'];
        if (!isset($grouped_assessments[$prof_id])) {
            $grouped_assessments[$prof_id] = [
                'profession_id' => $prof_id,
                'profession_name' => $assessment['profession_name'],
                'pvk_assessments' => []
            ];
        }
        
        $grouped_assessments[$prof_id]['pvk_assessments'][] = [
            'pvk_id' => $assessment['pvk_id'],
            'pvk_name' => $assessment['pvk_name'],
            'pvk_description' => $assessment['pvk_description'],
            'assessment_score' => $assessment['assessment_score'],
            'assessment_level' => $assessment['assessment_level'],
            'last_calculated' => $assessment['last_calculated']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'assessments' => array_values($grouped_assessments)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
