<?php
session_start();
require_once 'config.php'; // Assuming this has getDbConnection()
header('Content-Type: application/json');

// Function to shuffle array associatively (preserve keys if needed, though not strictly here)
function shuffle_assoc(&$array) {
    $keys = array_keys($array);
    shuffle($keys);
    $new = [];
    foreach ($keys as $key) {
        $new[$key] = $array[$key];
    }
    $array = $new;
    return true;
}

$response = ['success' => false, 'message' => 'Invalid request'];

$testMainCategory = $_GET['test_main_category'] ?? null;
// $userId = $_SESSION['user_id'] ?? null; // User ID might be used later

if (!$testMainCategory) {
    echo json_encode($response);
    exit;
}

// Define which specific test types to select for each main category and how many items
$categoryConfig = [
    'attention' => [
        'tests_to_select' => ['attention_stroop', 'attention_find_letter'], // Select 2 specific attention tests
        'items_per_test' => 15, // Total items for each specific test type
        'num_specific_tests' => 2 // Number of specific test types to pick from the list if more are available
    ],
    'memory' => [
        'tests_to_select' => ['memory_visual_recall', 'memory_word_list'], // Select 2 specific memory tests
        'items_per_test' => 12, // Total items
        'num_specific_tests' => 2
    ],
    'thinking' => [
        'tests_to_select' => ['thinking_odd_one_out', 'thinking_syllogism', 'thinking_series_completion'], // Select 3 specific thinking tests
        'items_per_test' => 9,  // Total items
        'num_specific_tests' => 3
    ]
];

if (!array_key_exists($testMainCategory, $categoryConfig)) {
    $response['message'] = 'Invalid test category.';
    echo json_encode($response);
    exit;
}

$currentConfig = $categoryConfig[$testMainCategory];
$selectedTestTypes = $currentConfig['tests_to_select']; // In this version, we pre-select them.
// Future: Randomly pick 'num_specific_tests' from a larger pool available for the category.

$itemsPerDifficulty = $currentConfig['items_per_test'] / 3;

$outputTests = [];

try {
    $pdo = getDbConnection();

    foreach ($selectedTestTypes as $testTypeFk) {
        $testDetailsStmt = $pdo->prepare("SELECT name FROM test_names WHERE test_type = ?");
        $testDetailsStmt->execute([$testTypeFk]);
        $testDetails = $testDetailsStmt->fetch(PDO::FETCH_ASSOC);

        if (!$testDetails) continue; // Skip if test_type not in test_names

        $currentTestOutput = [
            'test_type' => $testTypeFk,
            'test_name' => $testDetails['name'],
            'items' => []
        ];

        // Fetch items for each difficulty level
        $difficultyLevels = [1, 2, 3];
        $fetchedItems = [];

        foreach ($difficultyLevels as $level) {
            $stmt = $pdo->prepare(
                "SELECT id, item_content, difficulty_level, sub_category 
                 FROM cognitive_test_items 
                 WHERE test_type_fk = :test_type_fk AND difficulty_level = :difficulty_level 
                 ORDER BY RANDOM() 
                 LIMIT :limit"
            );
            $stmt->bindValue(':test_type_fk', $testTypeFk, PDO::PARAM_STR);
            $stmt->bindValue(':difficulty_level', $level, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$itemsPerDifficulty, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $fetchedItems = array_merge($fetchedItems, $items);
        }
        
        shuffle($fetchedItems); // Shuffle all collected items for this test type
        
        // Attempt to parse item_content if it's JSON
        foreach ($fetchedItems as &$item) {
            $decodedContent = json_decode($item['item_content'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $item['item_content'] = $decodedContent;
            }
        }
        unset($item); // Unset reference

        $currentTestOutput['items'] = $fetchedItems;
        $outputTests[] = $currentTestOutput;
    }

    if (empty($outputTests)) {
        $response['message'] = 'No tests or items found for the category: ' . $testMainCategory . '. Ensure cognitive_test_items table is populated.';
    } else {
        $response = [
            'success' => true,
            'test_main_category' => $testMainCategory,
            'tests' => $outputTests
        ];
    }

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log($response['message']); // Log error
} catch (Exception $e) {
    $response['message'] = 'General error: ' . $e->getMessage();
    error_log($response['message']); // Log error
}

echo json_encode($response);

?>
