<?php
// Включение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

// Проверка авторизации и роли консультанта
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'consultant') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен. Вы должны войти в систему как консультант.']);
    exit();
}

// Проверка, что форма была отправлена методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен. Ожидается POST-запрос.']);
    exit();
}

// Получение данных из формы
$consultation_id = isset($_POST['consultation_id']) ? intval($_POST['consultation_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Валидация данных
if ($consultation_id <= 0) {
    header('Location: /consultant/index.php?error=' . urlencode("Неверный ID консультации."));
    exit();
}

if (!in_array($action, ['schedule', 'complete', 'cancel'])) {
    header('Location: /consultant/index.php?error=' . urlencode("Неверное действие с консультацией."));
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Получение подключения к базе данных
    $pdo = getDbConnection();
    
    // Начало транзакции
    $pdo->beginTransaction();
    
    // Проверка существования консультации и прав консультанта
    $stmt = $pdo->prepare("
        SELECT id, status FROM consultations 
        WHERE id = ? AND consultant_id = ?
    ");
    $stmt->execute([$consultation_id, $userId]);
    $consultation = $stmt->fetch();
    
    if (!$consultation) {
        $pdo->rollBack();
        header('Location: /consultant/index.php?error=' . urlencode("Консультация не найдена или вы не являетесь её консультантом."));
        exit();
    }
    
    // Выполнение действия в зависимости от типа
    switch ($action) {
        case 'schedule':
            // Запланировать консультацию
            $scheduled_at = isset($_POST['scheduled_at']) ? $_POST['scheduled_at'] : '';
            $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 60;
            $consultant_notes = isset($_POST['consultant_notes']) ? trim($_POST['consultant_notes']) : '';
            
            if (empty($scheduled_at)) {
                $pdo->rollBack();
                header('Location: /consultant/index.php?error=' . urlencode("Не указаны дата и время консультации."));
                exit();
            }
            
            // Проверка формата даты
            $scheduled_time = strtotime($scheduled_at);
            if ($scheduled_time === false || $scheduled_time < time()) {
                $pdo->rollBack();
                header('Location: /consultant/index.php?error=' . urlencode("Дата и время консультации должны быть в будущем."));
                exit();
            }
            
            // Обновление статуса консультации
            $stmt = $pdo->prepare("
                UPDATE consultations 
                SET status = 'scheduled', 
                    scheduled_at = ?, 
                    duration = ?,
                    consultant_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$scheduled_at, $duration, $consultant_notes, $consultation_id]);
            
            // Отправка уведомления пользователю (заглушка)
            // В реальном проекте здесь будет код для отправки email
            
            $message = "Консультация успешно запланирована.";
            break;
            
        case 'complete':
            // Завершить консультацию
            $completion_notes = isset($_POST['completion_notes']) ? trim($_POST['completion_notes']) : '';
            
            if (empty($completion_notes)) {
                $pdo->rollBack();
                header('Location: /consultant/index.php?error=' . urlencode("Необходимо указать итоги консультации."));
                exit();
            }
            
            // Обновление статуса консультации
            $stmt = $pdo->prepare("
                UPDATE consultations 
                SET status = 'completed', 
                    completion_notes = ?,
                    completed_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$completion_notes, $consultation_id]);
            
            // Отправка уведомления пользователю с просьбой оценить консультацию (заглушка)
            // В реальном проекте здесь будет код для отправки email
            
            $message = "Консультация отмечена как завершенная.";
            break;
            
        case 'cancel':
            // Отменить консультацию
            $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
            
            if (empty($cancel_reason)) {
                $pdo->rollBack();
                header('Location: /consultant/index.php?error=' . urlencode("Необходимо указать причину отмены."));
                exit();
            }
            
            // Обновление статуса консультации
            $stmt = $pdo->prepare("
                UPDATE consultations 
                SET status = 'cancelled', 
                    cancel_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$cancel_reason, $consultation_id]);
            
            // Отправка уведомления пользователю об отмене (заглушка)
            // В реальном проекте здесь будет код для отправки email
            
            $message = "Консультация отменена.";
            break;
    }
    
    // Фиксация транзакции
    $pdo->commit();
    
    // Запись в лог для отладки
    error_log("Консультант ID: $userId обновил статус консультации ID: $consultation_id на: $action");
    
    // Перенаправление со статусом успеха
    header('Location: /consultant/index.php?success=' . urlencode($message));
    exit();
    
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Запись ошибки в лог
    error_log("Ошибка при обновлении консультации: " . $e->getMessage());
    
    // Перенаправление с сообщением об ошибке
    header('Location: /consultant/index.php?error=' . urlencode("Произошла ошибка при обновлении консультации. Пожалуйста, попробуйте позже."));
    exit();
}
?> 