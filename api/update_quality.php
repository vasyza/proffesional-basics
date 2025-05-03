<?php
session_start();
require_once 'config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/manage_qualities.php?error=' . urlencode('Неверный метод запроса'));
    exit;
}

// Получение и валидация данных
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if ($id <= 0) {
    header('Location: /admin/manage_qualities.php?error=' . urlencode('Неверный ID качества'));
    exit;
}

if (empty($name)) {
    header('Location: /admin/edit_quality.php?id=' . $id . '&error=' . urlencode('Необходимо указать наименование качества'));
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Проверка на существование качества
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professional_qualities WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetchColumn() === 0) {
        header('Location: /admin/manage_qualities.php?error=' . urlencode('Качество не найдено'));
        exit;
    }
    
    // Проверка на дубликаты имени
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professional_qualities WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    
    if ($stmt->fetchColumn() > 0) {
        header('Location: /admin/edit_quality.php?id=' . $id . '&error=' . urlencode('Качество с таким наименованием уже существует'));
        exit;
    }
    
    // Обновление качества
    $stmt = $pdo->prepare("
        UPDATE professional_qualities
        SET name = ?, category = ?, description = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $category, $description, $id]);
    
    // Перенаправление с сообщением об успехе
    header('Location: /admin/manage_qualities.php?success=' . urlencode('Качество успешно обновлено'));
    
} catch (PDOException $e) {
    error_log("Ошибка при обновлении качества: " . $e->getMessage());
    header('Location: /admin/edit_quality.php?id=' . $id . '&error=' . urlencode('Ошибка при обновлении качества: ' . $e->getMessage()));
}
?> 