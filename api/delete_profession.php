<?php
session_start();
require_once 'config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

$professionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($professionId <= 0) {
    header("Location: /admin/professions.php?error=" . urlencode("Неверный ID профессии"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Проверяем, существует ли профессия
    $stmt = $pdo->prepare("SELECT * FROM professions WHERE id = ?");
    $stmt->execute([$professionId]);
    $profession = $stmt->fetch();

    if (!$profession) {
        header("Location: /admin/professions.php?error=" . urlencode("Профессия не найдена"));
        exit;
    }

    // Удаляем профессию
    $stmt = $pdo->prepare("DELETE FROM professions WHERE id = ?");
    $stmt->execute([$professionId]);

    header("Location: /admin/professions.php?success=" . urlencode("Профессия успешно удалена"));
    exit;

} catch (PDOException $e) {
    header("Location: /admin/professions.php?error=" . urlencode("Ошибка удаления: " . $e->getMessage()));
    exit;
}
