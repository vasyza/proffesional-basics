<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$userId = $_SESSION['user_id'];

// Получение тела запроса
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Проверка данных
if (!isset($input['test_type']) || !isset($input['results'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
    exit;
}

$testType = $input['test_type'];
$results = $input['results'];
$averageTime = isset($input['average_time']) ? $input['average_time'] : null;
$accuracy = isset($input['accuracy']) ? $input['accuracy'] : null;

try {
    $pdo = getDbConnection();

    // Начало транзакции
    $pdo->beginTransaction();

    // 1. Создание записи о тестировании
    $stmt = $pdo->prepare("
        INSERT INTO test_sessions (user_id, test_type, average_time, accuracy, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $testType, $averageTime, $accuracy]);
    $sessionId = $pdo->lastInsertId();

    // 2. Сохранение результатов каждой попытки
    $stmt = $pdo->prepare("
        INSERT INTO test_attempts (session_id, trial_number, stimulus_value, response_value, reaction_time, is_correct)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($results as $result) {
        $trialNumber = $result['trial'];

        // Значение стимула (число, цвет и т.д.)
        $stimulusValue = null;
        if (isset($result['number'])) {
            $stimulusValue = $result['number'];
        } elseif (isset($result['color'])) {
            $stimulusValue = $result['color'];
        }

        // Значение ответа
        $responseValue = isset($result['response']) ? $result['response'] : null;

        // Время реакции
        $reactionTime = $result['time'];

        // Правильность ответа
        $isCorrect = isset($result['correct']) ? ($result['correct'] ? 1 : 0) : null;

        $stmt->execute([$sessionId, $trialNumber, $stimulusValue, $responseValue, $reactionTime, $isCorrect]);
    }

    // 3. Обновление записи о приглашении на тест, если тест был запущен по приглашению
    if (isset($_SESSION['invitation_id'])) {
        $invitationId = $_SESSION['invitation_id'];
        $stmt = $pdo->prepare("
            INSERT INTO invitation_tests (invitation_id, test_session_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$invitationId, $sessionId]);

        // Проверка, все ли тесты из приглашения выполнены
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed_count, 
                   (SELECT COUNT(*) FROM invitation_test_types WHERE invitation_id = ?) as total_count
            FROM invitation_tests it
            JOIN test_sessions ts ON it.test_session_id = ts.id
            WHERE it.invitation_id = ?
        ");
        $stmt->execute([$invitationId, $invitationId]);
        $testCounts = $stmt->fetch();

        // Если все тесты выполнены, помечаем приглашение как завершенное
        if ($testCounts['completed_count'] >= $testCounts['total_count']) {
            $stmt = $pdo->prepare("UPDATE test_invitations SET is_completed = 1 WHERE id = ?");
            $stmt->execute([$invitationId]);

            // Очищаем сессию от ID приглашения
            unset($_SESSION['invitation_id']);
        }
    }

    // Фиксация транзакции
    $pdo->commit();

    echo json_encode(['success' => true, 'session_id' => $sessionId]);

} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>