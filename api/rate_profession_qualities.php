<?php
session_start();
require_once 'config.php';

// Проверка авторизации и роли эксперта
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'expert') {
    header("Location: /auth/login.php");
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /expert/index.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

// Получение данных
$professionId = isset($_POST['profession_id']) ? intval($_POST['profession_id']) : 0;
$qualities = isset($_POST['qualities']) ? $_POST['qualities'] : [];

// Проверка наличия ID профессии
if ($professionId <= 0) {
    header('Location: /expert/index.php?error=' . urlencode("Неверный ID профессии"));
    exit;
}

// Проверка наличия выбранных качеств
if (empty($qualities) || count($qualities) < 5) {
    header('Location: /expert/rate_professional_qualities.php?profession_id=' . $professionId . '&error=' . urlencode("Необходимо выбрать минимум 5 качеств"));
    exit;
}

// Ограничение количества качеств
if (count($qualities) > 10) {
    header('Location: /expert/rate_professional_qualities.php?profession_id=' . $professionId . '&error=' . urlencode("Можно выбрать максимум 10 качеств"));
    exit;
}

$expertId = $_SESSION['user_id'];

try {
    $pdo = getDbConnection();
    
    // Проверка существования профессии
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professions WHERE id = ?");
    $stmt->execute([$professionId]);
    
    if ($stmt->fetchColumn() == 0) {
        header('Location: /expert/index.php?error=' . urlencode("Профессия не найдена"));
        exit;
    }
    
    // Начало транзакции
    $pdo->beginTransaction();
    
    // Удаление предыдущих оценок
    $stmt = $pdo->prepare("DELETE FROM profession_quality_ratings WHERE profession_id = ? AND expert_id = ?");
    $stmt->execute([$professionId, $expertId]);
    
    // Подготовка запроса для вставки новых оценок
    $stmt = $pdo->prepare("
        INSERT INTO profession_quality_ratings 
        (profession_id, quality_id, expert_id, rating, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    
    // Добавление новых оценок
    foreach ($qualities as $qualityId => $data) {
        $qualityId = intval($qualityId);
        $rating = isset($data['rating']) ? intval($data['rating']) : 5;
        
        // Проверка диапазона рейтинга
        if ($rating < 1) $rating = 1;
        if ($rating > 10) $rating = 10;
        
        // Проверка существования качества
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM professional_qualities WHERE id = ?");
        $checkStmt->execute([$qualityId]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $stmt->execute([$professionId, $qualityId, $expertId, $rating]);
        }
    }
    
    // Обновление качеств в сводной таблице для профессии
    updateProfessionQualities($pdo, $professionId);
    
    // Фиксация транзакции
    $pdo->commit();
    
    // Перенаправление на страницу успеха
    header('Location: /expert/rate_professional_qualities.php?profession_id=' . $professionId . '&success=' . urlencode("Ваши оценки успешно сохранены"));
    
} catch (PDOException $e) {
    // Откат транзакции
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Ошибка при сохранении оценок ПВК: " . $e->getMessage());
    header('Location: /expert/rate_professional_qualities.php?profession_id=' . $professionId . '&error=' . urlencode("Ошибка при сохранении оценок: " . $e->getMessage()));
}

/**
 * Обновляет сводную таблицу профессионально важных качеств для профессии
 * на основе оценок экспертов
 */
function updateProfessionQualities($pdo, $professionId) {
    // Удаление существующих записей для профессии
    $deleteStmt = $pdo->prepare("DELETE FROM combined_profession_quality_ratings WHERE profession_id = ?");
    $deleteStmt->execute([$professionId]);

    // Получение качеств, которые выбрали хотя бы 30% экспертов, вместе с их средней оценкой
    $stmt = $pdo->prepare("
        WITH expert_counts AS (
            SELECT 
                quality_id,
                COUNT(DISTINCT expert_id) AS expert_count,
                AVG(rating) AS avg_rating
            FROM 
                profession_quality_ratings
            WHERE 
                profession_id = ?
            GROUP BY 
                quality_id
        ),
        total_experts_cte AS (
            SELECT 
                COUNT(DISTINCT expert_id) AS total_experts
            FROM 
                profession_quality_ratings
            WHERE 
                profession_id = ?
        )
        SELECT 
            ec.quality_id,
            ec.avg_rating
        FROM 
            expert_counts ec
        CROSS JOIN 
            total_experts_cte te
        WHERE 
            ec.expert_count >= (te.total_experts * 0.3)
    ");

    $stmt->execute([$professionId, $professionId]);
    $consensusQualities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Добавление согласованных качеств в таблицу
    if (!empty($consensusQualities)) {
        $insertStmt = $pdo->prepare("
            INSERT INTO combined_profession_quality_ratings (profession_id, quality_id, average_rating, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        foreach ($consensusQualities as $quality) {
            $insertStmt->execute([
                $professionId,
                $quality['quality_id'],
                round($quality['avg_rating'], 2) // округляем до двух знаков после запятой
            ]);
        }
    }
}
?> 