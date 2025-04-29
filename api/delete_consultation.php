<?php
session_start();
require_once 'config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Получение ID консультации
$consultationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($consultationId <= 0) {
    header("Location: /admin/consultations.php?error=" . urlencode("Неверный ID консультации"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Проверка существования консультации
    $stmt = $pdo->prepare("SELECT * FROM consultations WHERE id = ?");
    $stmt->execute([$consultationId]);
    $consultation = $stmt->fetch();

    if (!$consultation) {
        header("Location: /admin/consultations.php?error=" . urlencode("Консультация не найдена"));
        exit;
    }

    // Удаление консультации
    $stmt = $pdo->prepare("DELETE FROM consultations WHERE id = ?");
    $stmt->execute([$consultationId]);

    header("Location: /admin/consultations.php?success=" . urlencode("Консультация успешно удалена"));
    exit;

} catch (PDOException $e) {
    header("Location: /admin/consultations.php?error=" . urlencode("Ошибка при удалении: " . $e->getMessage()));
    exit;
}
