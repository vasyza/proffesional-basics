<?php
// Включение конфигурационного файла
require_once 'config.php';

// Запуск сессии
session_start();

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/register.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

// Получение и валидация данных
$name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
$login = filter_var(trim($_POST['login']), FILTER_SANITIZE_STRING);
$password = trim($_POST['password']);
$password_confirm = trim($_POST['password_confirm'] ?? $_POST['confirm_password']);
$terms = isset($_POST['terms']) ? true : false;

// Проверка наличия всех обязательных полей
if (empty($name) || empty($login) || empty($password) || empty($password_confirm)) {
    header('Location: /auth/register.php?error=' . urlencode("Все поля обязательны для заполнения"));
    exit;
}

// Проверка согласия с правилами
if (!$terms) {
    header('Location: /auth/register.php?error=' . urlencode("Необходимо согласиться с правилами использования"));
    exit;
}

// Проверка длины логина
if (strlen($login) < 3) {
    header('Location: /auth/register.php?error=' . urlencode("Логин должен содержать не менее 3 символов"));
    exit;
}

// Проверка длины пароля
if (strlen($password) < 6) {
    header('Location: /auth/register.php?error=' . urlencode("Пароль должен содержать не менее 6 символов"));
    exit;
}

// Проверка совпадения паролей
if ($password !== $password_confirm) {
    header('Location: /auth/register.php?error=' . urlencode("Пароли не совпадают"));
    exit;
}

try {
    $pdo = getDbConnection();

    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Проверка уникальности логина
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE login = :login");
    $stmt->execute([':login' => $login]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        header('Location: /auth/register.php?error=' . urlencode("Пользователь с таким логином уже существует"));
        exit;
    }

    $hashed_password = md5($password . "hiferhifurie");

    // Определение роли пользователя (по умолчанию - обычный пользователь)
    $role = 'user';

    // Добавление пользователя в базу данных
    $stmt = $pdo->prepare("
        INSERT INTO users (name, login, pass, role, created_at)
        VALUES (:name, :login, :pass, :role, NOW())
    ");

    $stmt->execute([
        ':name' => $name,
        ':login' => $login,
        ':pass' => $hashed_password,
        ':role' => $role
    ]);

    // Получение ID нового пользователя
    $userId = $pdo->lastInsertId();

    // Фиксируем транзакцию
    $pdo->commit();

    // Авторизуем пользователя
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_login'] = $login;
    $_SESSION['user_role'] = $role;

    // Перенаправляем на страницу личного кабинета
    header('Location: /cabinet.php?success=' . urlencode("Регистрация успешно завершена!"));
    exit;

} catch (PDOException $e) {
    // Отменяем транзакцию в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Логирование ошибки
    error_log("Ошибка при регистрации пользователя: " . $e->getMessage());

    // Перенаправляем пользователя с сообщением об ошибке
    header('Location: /auth/register.php?error=' . urlencode("Ошибка при регистрации: " . $e->getMessage()));
    exit;
}
?>