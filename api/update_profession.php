<?php
session_start();
require_once 'config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Проверка входных данных
if (
    !isset($_POST['id']) ||
    !isset($_POST['title']) ||
    !isset($_POST['type']) ||
    !isset($_POST['description']) ||
    !isset($_POST['salary_range'])
) {
    header("Location: /admin/professions.php?error=" . urlencode("Некорректные данные формы"));
    exit;
}

$id = intval($_POST['id']);
$title = trim($_POST['title']);
$type = trim($_POST['type']);
$description = trim($_POST['description']);
$salary_range = trim($_POST['salary_range']);

if ($id <= 0 || empty($title) || empty($description)) {
    header("Location: /admin/professions.php?error=" . urlencode("Обязательные поля не заполнены"));
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        UPDATE professions
        SET title = ?, type = ?, description = ?, salary_range = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$title, $type, $description, $salary_range, $id]);

    header("Location: /admin/professions.php?success=" . urlencode("Профессия обновлена"));
    exit;

} catch (PDOException $e) {
    header("Location: /admin/professions.php?error=" . urlencode("Ошибка базы данных: " . $e->getMessage()));
    exit;
}
