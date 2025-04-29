<?php
session_start();
require_once 'config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Получение ID качества из URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: /admin/manage_qualities.php?error=' . urlencode('Неверный ID качества'));
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Начало транзакции
    $pdo->beginTransaction();
    
    // Получение имени качества для лога
    $stmt = $pdo->prepare("SELECT name FROM professional_qualities WHERE id = ?");
    $stmt->execute([$id]);
    $quality = $stmt->fetch();
    
    if (!$quality) {
        header('Location: /admin/manage_qualities.php?error=' . urlencode('Качество не найдено'));
        exit;
    }
    
    // Удаление связанных оценок
    $stmt = $pdo->prepare("DELETE FROM profession_quality_ratings WHERE quality_id = ?");
    $stmt->execute([$id]);
    
    // Удаление связей с профессиями
    // $stmt = $pdo->prepare("DELETE FROM professional_qualities WHERE quality_id = ?");
    // $stmt->execute([$id]);
    
    // Удаление самого качества
    $stmt = $pdo->prepare("DELETE FROM professional_qualities WHERE id = ?");
    $stmt->execute([$id]);
    
    // Фиксация транзакции
    $pdo->commit();
    
    // Логирование операции
    error_log("Удалено качество ID:{$id}, '{$quality['name']}' пользователем ID:{$_SESSION['user_id']}");
    
    // Перенаправление с сообщением об успехе
    header('Location: /admin/manage_qualities.php?success=' . urlencode('Качество успешно удалено'));
    
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Ошибка при удалении качества: " . $e->getMessage());
    header('Location: /admin/manage_qualities.php?error=' . urlencode('Ошибка при удалении качества: ' . $e->getMessage()));
}
?> 