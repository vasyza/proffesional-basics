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

if ($testType === 'analog_tracking' || $testType === 'pursuit_tracking') {
    if ($accuracy === null) { // Для тестов слежения точность важнее среднего времени
        echo json_encode(['success' => false, 'message' => "Недостаточно данных для $testType (accuracy не передана)"]);
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
        'analog_tracking' => 'analog_tracking_respondents',
        'pursuit_tracking' => 'pursuit_tracking_respondents'
    ];

    if (key_exists($testType, $respondentTableMap) && !empty($username)) {
        $respondentTable = $respondentTableMap[$testType];
        $stmtCheckRespondent = $pdo->prepare("SELECT id FROM " . $respondentTable . " WHERE user_name = ?");
        $stmtCheckRespondent->execute([$username]);

        if ($stmtCheckRespondent->rowCount() == 0) {
            $insertStmt = $pdo->prepare("
                INSERT INTO " . $respondentTable . " (user_name, test_date)
                SELECT ?, NOW() FROM users WHERE id = ? LIMIT 1
            ");

            $insertStmt = $pdo->prepare("
                INSERT INTO " . $respondentTable . " (user_name, test_date)
                VALUES (?, NOW())
            ");
            $insertStmt->execute([$username]);
        } else {
            $updateStmt = $pdo->prepare("UPDATE " . $respondentTable . " SET test_date = NOW() WHERE user_name = ?");
            $updateStmt->execute([$username]);
        }
    }

    // Создание записи о тестировании
    $stmt = $pdo->prepare("
        INSERT INTO test_sessions (user_id, test_type, average_time, accuracy, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([$userId, $testType, ($testType === 'analog_tracking' || $testType === 'pursuit_tracking' ? null : $averageTime), $accuracy]);
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
        $reactionTime = key_exists('reaction_time', $result) ? $result['reaction_time'] : null;
        $isCorrect = isset($result['is_correct']) ? ($result['is_correct'] ? 1 : 0) : null;
        if ($trialNumber === null || $reactionTime === null || $isCorrect === null) {
            continue;
        }

        $stmt->execute([$sessionId, $trialNumber, $stimulusValue, $responseValue, $reactionTime, $isCorrect]);
    }

    $normalizedResult = null;
    if ($accuracy !== null) {
        $stmtNorm = $pdo->prepare("
            SELECT
                AVG(accuracy) as group_avg_accuracy,
                STDDEV(accuracy) as group_std_accuracy
            FROM test_sessions
            WHERE test_type = ? AND accuracy IS NOT NULL
        ");
        $stmtNorm->execute([$testType]);
        $groupStats = $stmtNorm->fetch();

        if ($groupStats && $groupStats['group_avg_accuracy'] !== null && $groupStats['group_std_accuracy'] !== null && $groupStats['group_std_accuracy'] > 0) {
            $groupAvg = (float)$groupStats['group_avg_accuracy'];
            $groupStd = (float)$groupStats['group_std_accuracy'];

            if ($accuracy >= $groupAvg + $groupStd) { // Лучше среднего
                $normalizedResult = 3;
            } elseif ($accuracy <= $groupAvg - $groupStd) { // Хуже среднего
                $normalizedResult = 1;
            } else { // В пределах среднего
                $normalizedResult = 2;
            }
        } elseif ($groupStats && $groupStats['group_avg_accuracy'] !== null && ($groupStats['group_std_accuracy'] == 0)) {
            $normalizedResult = 2; // Средний результат, если мало данных
        }

        if ($normalizedResult !== null) {
            $stmtUpdateNorm = $pdo->prepare("UPDATE test_sessions SET normalized_result = ? WHERE id = ?");
            $stmtUpdateNorm->execute([$normalizedResult, $sessionId]);
        }
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
            $normalizedResult = 2;
        }

        if ($normalizedResult !== null) {
            $stmtUpdateNorm = $pdo->prepare("
            UPDATE test_sessions 
            SET normalized_result = ? 
            WHERE id = ?
        ");
            $stmtUpdateNorm->execute([$normalizedResult, $sessionId]);
        }
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
