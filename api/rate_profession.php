<?php
// Включение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

// Проверка авторизации и роли эксперта
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'expert') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен. Вы должны войти в систему как эксперт.']);
    exit();
}

// Проверка, что форма была отправлена методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен. Ожидается POST-запрос.']);
    exit();
}

// Получение данных из формы
$profession_id = isset($_POST['profession_id']) ? intval($_POST['profession_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Валидация данных
if ($profession_id <= 0) {
    header('Location: /expert/index.php?error=' . urlencode("Неверный ID профессии."));
    exit();
}

if ($rating < 1 || $rating > 5) {
    header('Location: /expert/index.php?error=' . urlencode("Оценка должна быть от 1 до 5."));
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Получение подключения к базе данных
    $pdo = getDbConnection();
    
    // Начало транзакции
    $pdo->beginTransaction();
    
    // Проверка существования профессии
    $stmt = $pdo->prepare("SELECT id FROM professions WHERE id = ?");
    $stmt->execute([$profession_id]);
    
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        header('Location: /expert/index.php?error=' . urlencode("Профессия с указанным ID не найдена."));
        exit();
    }
    
    // Проверка, существует ли уже оценка от этого эксперта
    $stmt = $pdo->prepare("SELECT id FROM expert_ratings WHERE expert_id = ? AND profession_id = ?");
    $stmt->execute([$userId, $profession_id]);
    $existing_rating = $stmt->fetch();
    
    if ($existing_rating) {
        // Обновление существующей оценки
        $stmt = $pdo->prepare("
            UPDATE expert_ratings 
            SET rating = ?, comment = ?, updated_at = NOW() 
            WHERE expert_id = ? AND profession_id = ?
        ");
        $stmt->execute([$rating, $comment, $userId, $profession_id]);
        
        $message = "Оценка успешно обновлена.";
    } else {
        // Вставка новой оценки
        $stmt = $pdo->prepare("
            INSERT INTO expert_ratings (expert_id, profession_id, rating, comment, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $profession_id, $rating, $comment]);
        
        $message = "Оценка успешно добавлена.";
    }
    
    // Обновление средней оценки профессии
    $stmt = $pdo->prepare("
        UPDATE professions p
        SET demand_level = (
            SELECT AVG(rating)
            FROM expert_ratings
            WHERE profession_id = p.id
        )
        WHERE id = ?
    ");
    $stmt->execute([$profession_id]);
    
    // Фиксация транзакции
    $pdo->commit();
    
    // Запись в лог для отладки
    error_log("Эксперт ID: $userId оценил профессию ID: $profession_id с оценкой: $rating");
    
    // Перенаправление со статусом успеха
    header('Location: /expert/index.php?success=' . urlencode($message));
    exit();
    
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Запись ошибки в лог
    error_log("Ошибка при оценке профессии: " . $e->getMessage());
    
    // Перенаправление с сообщением об ошибке
    header('Location: /expert/index.php?error=' . urlencode("Произошла ошибка при сохранении оценки. Пожалуйста, попробуйте позже."));
    exit();
}
?> 