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
$username = $_SESSION['user_name'] ?? '';

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
$averageTime = $input['average_time'] ?? null;
$accuracy = $input['accuracy'] ?? null;

if (($testType === 'moving_object_simple' || $testType === 'moving_object_complex')) {
    if ($averageTime === null || $accuracy === null) {
        echo json_encode(['success' => false, 'message' => "Недостаточно данных для $testType (average_time или accuracy не переданы)"]);
        exit;
    }
}

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $respondentTableMap = [
        'light_reaction' => 'light_respondents',
        'sound_reaction' => 'sound_respondents',
        'color_reaction' => 'color_respondents',
        'sound_arithmetic' => 's_arith_respondents',
        'visual_arithmetic' => 'v_arith_respondents',
        'moving_object_simple' => 'moving_object_simple_respondents',
        'moving_object_complex' => 'moving_object_complex_respondents',
    ];

    // Создание записи о тестировании
    $stmt = $pdo->prepare("
        INSERT INTO test_sessions (user_id, test_type, average_time, accuracy, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $testType, $averageTime, $accuracy]);
    $sessionId = $pdo->lastInsertId();

    // Сохранение результатов каждой попытки
    $stmt = $pdo->prepare("
        INSERT INTO test_attempts (session_id, trial_number, stimulus_value, response_value, reaction_time, is_correct)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($results as $result) {
        $trialNumber = $result['trial_number'];

        // Значение стимула (число, цвет и т.д.)
        $stimulusValue = null;
        if (isset($result['number'])) {
            $stimulusValue = $result['number'];
        } elseif (isset($result['color'])) {
            $stimulusValue = $result['color'];
        } elseif (isset($result['stimulus_value'])) {
            $stimulusValue = $result['stimulus_value'];
        }

        $responseValue = $result['response'] ?? null;
        $reactionTime = $result['reaction_time'];
        $isCorrect = isset($result['is_correct']) ? ($result['is_correct'] ? 1 : 0) : null;
        if ($trialNumber === null || $reactionTime === null || $isCorrect === null) {
            continue;
        }

        $stmt->execute([$sessionId, $trialNumber, $stimulusValue, $responseValue, $reactionTime, $isCorrect]);
    }

    $normalizedResult = null;
    if ($averageTime !== null) {
        $stmtNorm = $pdo->prepare("
            SELECT 
                AVG(average_time) as group_avg_time,
                STDDEV(average_time) as group_std_time
            FROM test_sessions
            WHERE test_type = ? AND average_time IS NOT NULL
        ");
        $stmtNorm->execute([$testType]);
        $groupStats = $stmtNorm->fetch();

        if ($groupStats && $groupStats['group_avg_time'] !== null && $groupStats['group_std_time'] !== null && $groupStats['group_std_time'] > 0) {
            $groupAvg = (float)$groupStats['group_avg_time'];
            $groupStd = (float)$groupStats['group_std_time'];

            if ($averageTime >= $groupAvg + $groupStd) {
                $normalizedResult = 1;
            } elseif ($averageTime <= $groupAvg - $groupStd) {
                $normalizedResult = 3;
            } else {
                $normalizedResult = 2;
            }
        } elseif ($groupStats && $groupStats['group_avg_time'] !== null && ($groupStats['group_std_time'] == 0)) {
            // Если стандартное отклонение 0 или null (мало данных или все одинаковые),
            // можно установить средний результат или специфическое значение.
            $normalizedResult = 2;
        }
    }

    if ($normalizedResult !== null) {
        $stmtUpdateNorm = $pdo->prepare("
            UPDATE test_sessions 
            SET normalized_result = ? 
            WHERE id = ?
        ");
        $stmtUpdateNorm->execute([$normalizedResult, $sessionId]);
    }

    // Обновление записи о приглашении на тест (эта логика остается)
    if (!empty($input['batch_id'])) {
        $_SESSION['invitation_id'] = $input['batch_id']; // Используем batch_id как invitation_id
    }

    if (isset($_SESSION['invitation_id'])) {
        $invitationId = $_SESSION['invitation_id'];
        // invitation_id действительно существует в test_batches как id
        $checkBatchStmt = $pdo->prepare("SELECT id FROM test_batches WHERE id = ?");
        $checkBatchStmt->execute([$invitationId]);
        if ($checkBatchStmt->fetch()) {
            $stmtInvite = $pdo->prepare("
                INSERT INTO invitation_tests (invitation_id, test_session_id)
                VALUES (?, ?)
                ON CONFLICT (invitation_id, test_session_id) DO NOTHING
            ");
            $stmtInvite->execute([$invitationId, $sessionId]);

            $stmtCountTests = $pdo->prepare("
                SELECT COUNT(DISTINCT itt.test_type) as total_count
                FROM invitation_test_types itt
                WHERE itt.invitation_id = ?
            ");
            $stmtCountTests->execute([$invitationId]);
            $totalTestsInBatch = $stmtCountTests->fetchColumn();

            $stmtCompletedTests = $pdo->prepare("
                SELECT COUNT(DISTINCT ts.test_type) as completed_count
                FROM invitation_tests it
                JOIN test_sessions ts ON it.test_session_id = ts.id
                WHERE it.invitation_id = ?
            ");
            $stmtCompletedTests->execute([$invitationId]);
            $completedTestsInBatch = $stmtCompletedTests->fetchColumn();

            if ($completedTestsInBatch >= $totalTestsInBatch) {
                $stmtUpdateInvitation = $pdo->prepare("UPDATE test_batches SET isFinished = TRUE WHERE id = ?");
                $stmtUpdateInvitation->execute([$invitationId]);
                unset($_SESSION['invitation_id']);
            }
        } else {
            unset($_SESSION['invitation_id']);
        }
    }

    // Фиксация транзакции
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'normalized_result' => $normalizedResult,
        'group_stats' => $groupStats ?? null
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка: ' . $e->getMessage()
    ]);
}
