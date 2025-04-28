<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Проверка аутентификации и прав доступа (только админы могут добавлять профессии)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Доступ запрещен. Только администраторы могут добавлять профессии.'
    ]);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается. Используйте POST.'
    ]);
    exit;
}

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);

// Если данные отправлены через form-data, а не JSON
if (empty($data)) {
    $data = $_POST;
}

// Проверка наличия обязательных полей
$requiredFields = ['title', 'description', 'type'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode([
        'success' => false,
        'message' => 'Отсутствуют обязательные поля: ' . implode(', ', $missingFields)
    ]);
    exit;
}

try {
    $pdo = getDbConnection();

    // Подготовка запроса на добавление профессии
    $stmt = $pdo->prepare("
        INSERT INTO professions (
            title, description, skills, type, salary_range, demand_level, image_path, created_by
        ) VALUES (
            :title, :description, :skills, :type, :salary_range, :demand_level, :image_path, :created_by
        ) RETURNING id
    ");

    // Привязка параметров
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    //$stmt->bindParam(':skills', $data['skills']);
    $stmt->bindParam(':type', $data['type']);

    // Необязательные поля
    $salaryRange = isset($data['salary_range']) ? $data['salary_range'] : null;
    $stmt->bindParam(':salary_range', $salaryRange);

    $demandLevel = isset($data['demand_level']) ? intval($data['demand_level']) : null;
    $stmt->bindParam(':demand_level', $demandLevel);

    $imagePath = isset($data['image_path']) ? $data['image_path'] : null;
    $stmt->bindParam(':image_path', $imagePath);

    $createdBy = $_SESSION['user_id'];
    $stmt->bindParam(':created_by', $createdBy);

    $skills = isset($data['skills']) ? $data['skills'] : null;
    $stmt->bindParam(':skills', $data['skills']);

    // Выполнение запроса
    $stmt->execute();

    // Получение ID созданной профессии
    $newProfessionId = $stmt->fetchColumn();

    header('Location: /admin/index.php?success=' . urlencode("Профессия успешно добавлена!"));
    exit;

    // echo json_encode([
    //     'success' => true,
    //     'message' => 'Профессия успешно добавлена',
    //     'profession_id' => $newProfessionId
    // ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при добавлении профессии: ' . $e->getMessage()
    ]);
}
?>