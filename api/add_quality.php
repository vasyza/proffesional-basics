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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if (empty($name)) {
    header('Location: /admin/manage_qualities.php?error=' . urlencode('Необходимо указать наименование качества'));
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Проверка на дубликаты
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM professional_qualities WHERE name = ?");
    $stmt->execute([$name]);
    
    if ($stmt->fetchColumn() > 0) {
        header('Location: /admin/manage_qualities.php?error=' . urlencode('Качество с таким наименованием уже существует'));
        exit;
    }
    
    // Добавление нового качества
    $stmt = $pdo->prepare("
        INSERT INTO professional_qualities (name, category, description)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$name, $category, $description]);
    
    // Перенаправление с сообщением об успехе
    header('Location: /admin/manage_qualities.php?success=' . urlencode('Качество успешно добавлено'));
    
} catch (PDOException $e) {
    error_log("Ошибка при добавлении качества: " . $e->getMessage());
    header('Location: /admin/manage_qualities.php?error=' . urlencode('Ошибка при добавлении качества: ' . $e->getMessage()));
}
?> 