<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /request_consultation.php?error=" . urlencode("Неверный метод запроса"));
    exit;
}

$userId = $_SESSION['user_id'];

$consultantId = isset($_POST['consultant_id']) ? intval($_POST['consultant_id']) : 0;
$professionId = isset($_POST['profession_id']) ? intval($_POST['profession_id']) : 0;
$topic = trim($_POST['topic'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($consultantId <= 0 || $professionId <= 0 || empty($topic)) {
    header("Location: /request_consultation.php?error=" . urlencode("Пожалуйста, заполните все обязательные поля"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Проверка существования консультанта
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'consultant'");
    $stmt->execute([$consultantId]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: /request_consultation.php?error=" . urlencode("Консультант не найден"));
        exit;
    }

    // Проверка существования профессии
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professions WHERE id = ?");
    $stmt->execute([$professionId]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: /request_consultation.php?error=" . urlencode("Профессия не найдена"));
        exit;
    }

    // Добавление запроса на консультацию
    $stmt = $pdo->prepare("
        INSERT INTO consultations (user_id, consultant_id, profession_id, topic, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $consultantId, $professionId, $topic, $message]);

    header("Location: /cabinet.php?success=" . urlencode("Запрос успешно отправлен!"));
    exit;

} catch (PDOException $e) {
    header("Location: /request_consultation.php?error=" . urlencode("Ошибка базы данных: " . $e->getMessage()));
    exit;
}