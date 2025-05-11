<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$username = $_SESSION['user_name'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
$isPublic = $input['is_public'] ?? false;

try {
    $pdo = getDbConnection();
    
    // Обновляем все записи пользователя в light_respondents
    $stmt = $pdo->prepare("UPDATE light_respondents SET isPublic = ? WHERE user_name = ?");
    $stmt->execute([$isPublic ? 1 : 0, $username]);

    // Обновляем все записи пользователя в sound_respondents
    $stmt = $pdo->prepare("UPDATE sound_respondents SET isPublic = ? WHERE user_name = ?");
    $stmt->execute([$isPublic ? 1 : 0, $username]);

    // Обновляем все записи пользователя в color_respondents
    $stmt = $pdo->prepare("UPDATE color_respondents SET isPublic = ? WHERE user_name = ?");
    $stmt->execute([$isPublic ? 1 : 0, $username]);

    // Обновляем все записи пользователя в s_arith_respondents
    $stmt = $pdo->prepare("UPDATE s_arith_respondents SET isPublic = ? WHERE user_name = ?");
    $stmt->execute([$isPublic ? 1 : 0, $username]);

    // Обновляем все записи пользователя в v_arith_respondents
    $stmt = $pdo->prepare("UPDATE v_arith_respondents SET isPublic = ? WHERE user_name = ?");
    $stmt->execute([$isPublic ? 1 : 0, $username]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}