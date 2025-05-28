<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting Laboratory Work 7 database setup...\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/../sql/lab7_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolons but be careful with function definitions
    $statements = explode(';', $sql);
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if already exists errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'duplicate key') === false) {
                    throw $e;
                }
                echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
    $pdo->commit();
    
    echo "\n✅ Laboratory Work 7 database setup completed successfully!\n";
    echo "New tables created:\n";
    echo "- pvk_criteria\n";
    echo "- profession_to_criteria\n";
    echo "- criterion_to_pvk\n";
    echo "- criterion_test_indicators\n";
    echo "- physiological_recordings\n";
    echo "- physiological_data_points\n";
    echo "- user_pvk_assessments\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
