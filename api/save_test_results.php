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

$cognitiveTests = ['schulte_table', 'number_memorization', 'analogies_test'];
if (in_array($testType, $cognitiveTests)) {
    if ($accuracy === null) {
        echo json_encode(['success' => false, 'message' => "Недостаточно данных для $testType (accuracy не передана)"]);
        exit;
    }
}

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
        'pursuit_tracking' => 'pursuit_tracking_respondents',
        'schulte_table' => 'schulte_table_respondents',
        'number_memorization' => 'number_memorization_respondents',
        'analogies_test' => 'analogies_test_respondents'
    ];

    if (key_exists($testType, $respondentTableMap) && !empty($username)) {
        $respondentTable = $respondentTableMap[$testType];
        $stmtCheckRespondent = $pdo->prepare("SELECT id FROM " . $respondentTable . " WHERE user_name = ?");
        $stmtCheckRespondent->execute([$username]);

        if ($stmtCheckRespondent->rowCount() == 0) {
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

    $timeParam = $averageTime;
    if (in_array($testType, ['schulte_table', 'number_memorization', 'analogies_test']) && $averageTime === null) {
        // Allow null average_time if it's a cognitive test where it might not be the primary metric or is calculated differently.
        // For example, Schulte table total time, or average time per correct item.
    }

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

        // For some cognitive tests, reaction_time might not be per trial, or stimulus/response might be complex
        if ($trialNumber === null) {
            // For tests like Schulte where results might be a summary
            if ($testType === 'schulte_table' && isset($result['table_results'])) {
                // Example: Save each table's time as a separate "attempt" or log differently
                foreach ($result['table_results'] as $table_idx => $table_time) {
                    $stmt->execute([$sessionId, $table_idx + 1, "table_" . ($table_idx + 1), null, $table_time, 1]); // Assuming all tables are "correctly" completed
                }
                continue; // Skip the generic loop for this specific format
            } else if ($testType === 'number_memorization' && isset($result['numbers_shown'])) {
                // Save detailed attempt for number memorization
                $stimulusValue = json_encode(['shown' => $result['numbers_shown'], 'recalled' => $result['numbers_recalled']]);
                $isCorrect = $result['is_correct'] ? 1 : 0;
                // reaction_time here might be time taken to recall, or null if not applicable per trial
            } else if ($testType === 'analogies_test' && isset($result['analogy_pair'])) {
                $stimulusValue = json_encode($result['analogy_pair']);
                $isCorrect = $result['is_correct'] ? 1 : 0;
                // reaction_time here is for this specific analogy
            } else {
                // If trialNumber is null for other tests, or missing essential data, skip.
                if ($reactionTime === null || $isCorrect === null) continue;
            }
        } else {
            if ($reactionTime === null || $isCorrect === null) {
                // For cognitive tests, allow null reaction time if 'is_correct' is primary
                if (!in_array($testType, $cognitiveTests) || $isCorrect === null) {
                    continue;
                }
            }
        }

        $stmt->execute([$sessionId, $trialNumber, $stimulusValue, $responseValue, $reactionTime, $isCorrect]);
    }

    $normalizedResult = null;
    // Accuracy-based normalization (more common for cognitive tests)
    if ($accuracy !== null) {
        $stmtNorm = $pdo->prepare("
            SELECT AVG(accuracy) as group_avg_accuracy, STDDEV(accuracy) as group_std_accuracy
            FROM test_sessions WHERE test_type = ? AND accuracy IS NOT NULL
        ");
        $stmtNorm->execute([$testType]);
        $groupStats = $stmtNorm->fetch();

        if ($groupStats && $groupStats['group_avg_accuracy'] !== null && $groupStats['group_std_accuracy'] !== null && $groupStats['group_std_accuracy'] > 0) {
            $groupAvg = (float)$groupStats['group_avg_accuracy'];
            $groupStd = (float)$groupStats['group_std_accuracy'];
            if ($accuracy >= $groupAvg + $groupStd) $normalizedResult = 3; // High
            elseif ($accuracy <= $groupAvg - $groupStd) $normalizedResult = 1; // Low
            else $normalizedResult = 2; // Average
        } elseif ($groupStats && $groupStats['group_avg_accuracy'] !== null && ($groupStats['group_std_accuracy'] == 0)) {
            if ($accuracy == $groupStats['group_avg_accuracy']) $normalizedResult = 2;
            else if ($accuracy > $groupStats['group_avg_accuracy']) $normalizedResult = 3;
            else $normalizedResult = 1;
        }
    }

    if ($normalizedResult === null && $averageTime !== null) {
        $stmtNorm = $pdo->prepare("
            SELECT AVG(average_time) as group_avg_time, STDDEV(average_time) as group_std_time
            FROM test_sessions WHERE test_type = ? AND average_time IS NOT NULL
        ");
        $stmtNorm->execute([$testType]);
        $groupStatsTime = $stmtNorm->fetch(); // Fetch into a new variable to avoid overwriting $groupStats

        if ($groupStatsTime && $groupStatsTime['group_avg_time'] !== null && $groupStatsTime['group_std_time'] !== null && $groupStatsTime['group_std_time'] > 0) {
            $groupAvg = (float)$groupStatsTime['group_avg_time'];
            $groupStd = (float)$groupStatsTime['group_std_time'];
            // For time, lower is better
            if ($averageTime <= $groupAvg - $groupStd) $normalizedResult = 3; // High (good performance)
            elseif ($averageTime >= $groupAvg + $groupStd) $normalizedResult = 1; // Low (poor performance)
            else $normalizedResult = 2; // Average
        } elseif ($groupStatsTime && $groupStatsTime['group_avg_time'] !== null && ($groupStatsTime['group_std_time'] == 0)) {
            if ($averageTime == $groupStatsTime['group_avg_time']) $normalizedResult = 2;
            else if ($averageTime < $groupStatsTime['group_avg_time']) $normalizedResult = 3; // Better
            else $normalizedResult = 1; // Worse
        }
    }
    if ($normalizedResult === null) $normalizedResult = 2; // Default if no stats or single user

    if ($normalizedResult !== null) {
        $stmtUpdateNorm = $pdo->prepare("UPDATE test_sessions SET normalized_result = ? WHERE id = ?");
        $stmtUpdateNorm->execute([$normalizedResult, $sessionId]);
    }

    // Обновление записи о приглашении на тест (эта логика остается)
    if (!empty($input['batch_id'])) {
        $_SESSION['invitation_id'] = $input['batch_id'];
    }

    if (isset($_SESSION['invitation_id'])) {
        $invitationId = $_SESSION['invitation_id'];
        $checkBatchStmt = $pdo->prepare("SELECT id FROM test_batches WHERE id = ?");
        $checkBatchStmt->execute([$invitationId]);
        if ($checkBatchStmt->fetch()) {
            $stmtInvite = $pdo->prepare("
                INSERT INTO invitation_tests (invitation_id, test_session_id)
                VALUES (?, ?)
                ON CONFLICT (invitation_id, test_session_id) DO NOTHING
            ");
            $stmtInvite->execute([$invitationId, $sessionId]);

            $stmtCountTestsInBatch = $pdo->prepare("
                SELECT COUNT(DISTINCT test_type) as total_tests
                FROM tests_in_batches
                WHERE batch_id = ?
            ");
            $stmtCountTestsInBatch->execute([$invitationId]);
            $totalTestsInBatch = $stmtCountTestsInBatch->fetchColumn();

            $stmtCompletedTestsForBatch = $pdo->prepare("
                SELECT COUNT(DISTINCT ts.test_type) as completed_count
                FROM invitation_tests it
                JOIN test_sessions ts ON it.test_session_id = ts.id
                WHERE it.invitation_id = ? AND ts.user_id = ?
            ");
            $stmtCompletedTestsForBatch->execute([$invitationId, $userId]);
            $completedTestsCountForBatch = $stmtCompletedTestsForBatch->fetchColumn();


            if ($completedTestsCountForBatch >= $totalTestsInBatch) {
                $stmtUpdateInvitation = $pdo->prepare("UPDATE test_batches SET isFinished = TRUE WHERE id = ?");
                $stmtUpdateInvitation->execute([$invitationId]);
            }        }
    }

    // Автоматический расчет ПВК после сохранения результатов теста
    try {
        // Подключаем сервис оценки ПВК
        require_once 'PvkAssessmentService.php';
        $assessmentService = new PvkAssessmentService($pdo);
        
        // Получаем все профессии для текущего пользователя
        $professionStmt = $pdo->query("SELECT id FROM professions ORDER BY id");
        $professions = $professionStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Рассчитываем ПВК оценки для каждой профессии
        foreach ($professions as $professionId) {
            try {
                $assessmentService->calculateAndSaveAssessment($userId, $professionId);
            } catch (Exception $e) {
                // Игнорируем ошибки расчета ПВК, чтобы не блокировать сохранение результатов
                error_log("PVK assessment error for user $userId, profession $professionId: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        // Игнорируем ошибки ПВК если сервис недоступен
        error_log("PVK assessment service error: " . $e->getMessage());
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
