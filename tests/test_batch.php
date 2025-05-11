<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$batchId = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;

// Проверка наличия партии тестов
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, created_at FROM test_batches WHERE id = ? AND user_id = ? AND isFinished = FALSE");
    $stmt->execute([$batchId, $userId]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!isset($batch['created_at'])) {
        header("Location: /tests/index.php");
        exit;
    }

    if (!$batch) {
        echo "<div class='alert alert-danger'>Партия тестов не найдена или уже завершена.</div>";
        exit;
    }

    // Получаем тесты
    $testsStmt = $pdo->prepare("SELECT tb.test_type, tn.name AS test_name
                                FROM tests_in_batches tb
                                LEFT JOIN test_names tn ON tb.test_type = tn.test_type
                                WHERE tb.batch_id = ?
                                ORDER BY tb.id ASC");
    $testsStmt->execute([$batchId]);
    $tests = $testsStmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Ошибка базы данных: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Получение завершённых тестов
$completedTests = [];
foreach ($tests as $test) {
    // Проверяем, если тест завершён после создания партии
    $stmt = $pdo->prepare("SELECT id FROM test_sessions WHERE user_id = ? AND test_type = ? AND created_at >= ? LIMIT 1");
    $stmt->execute([$userId, $test['test_type'], $batch['created_at']]);
    $completedTest = $stmt->fetch();

    if ($completedTest) {
        // Обновляем isFinished для этого теста
        $updateStmt = $pdo->prepare("UPDATE tests_in_batches SET isFinished = TRUE WHERE batch_id = ? AND test_type = ?");
        $updateStmt->execute([$batchId, $test['test_type']]);
        $completedTests[] = $test['test_type'];
    }
}

// Получение следующего теста
$nextTest = null;
foreach ($tests as $test) {
    // Проверяем, если тест завершён после создания партии
    $stmt = $pdo->prepare("SELECT id FROM test_sessions WHERE user_id = ? AND test_type = ? AND created_at >= ? LIMIT 1");
    $stmt->execute([$userId, $test['test_type'], $batch['created_at']]);
    $completedTest = $stmt->fetch();

    if ($completedTest) {
        // Обновляем isFinished для этого теста
        $updateStmt = $pdo->prepare("UPDATE tests_in_batches SET isFinished = TRUE WHERE batch_id = ? AND test_type = ?");
        $updateStmt->execute([$batchId, $test['test_type']]);
        $completedTests[] = $test['test_type'];
    }
}

include_once '../includes/header.php';

// Завершение партии тестов
if (isset($_POST['finish_batch'])) {
    try {
        $finishStmt = $pdo->prepare("UPDATE test_batches SET isFinished = TRUE WHERE id = ?");
        $finishStmt->execute([$batchId]);
        header("Location: /tests/index.php");
        exit;
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Ошибка при завершении партии тестов: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Проверяем, если все тесты завершены
if (count($completedTests) === count($tests)) {
    $nextTest = null;
} else {
    foreach ($tests as $test) {
        if (!in_array($test['test_type'], $completedTests)) {
            $nextTest = $test['test_type'];
            break;
        }
    }
}
?>

<div class="container py-4">
    <h2>Назначенные тесты<?php echo $batchId; ?></h2>
    <ul class="list-group mb-3">
        <?php foreach ($tests as $test): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?php echo htmlspecialchars($test['test_name'] ?: $test['test_type']); ?>
                <?php if (in_array($test['test_type'], $completedTests)): ?>
                    <span class="text-success">Пройдено &#x2713;</span>
                <?php else: ?>
                    <span class="text-danger">Не пройдено</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($nextTest): ?>
        <a href="/tests/sensorimotor/<?php echo $nextTest; ?>.php?batch_id=<?php echo $batchId; ?>" class="btn btn-primary">Пройти следующий тест</a>
    <?php else: ?>
        <form method="post" action="">
    <button type="submit" name="finish_batch" class="btn btn-success">Завершить</button>
</form>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>
