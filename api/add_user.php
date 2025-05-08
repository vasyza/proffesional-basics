<?php
session_start();
require_once 'config.php';

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/login.php?error=' . urlencode("Неверный метод запроса"));
    exit;
}

// Получение данных из формы
$name = clean_input($_POST['name'] ?? '');
$login = clean_input($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$role = clean_input($_POST['role'] ?? '');
// Новые поля: возраст и пол
$age = filter_var($_POST['age'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 12]]);
$gender = clean_input($_POST['gender'] ?? '');

// Валидация входных данных
if (empty($name) || empty($login) || empty($password) || empty($password_confirm) || empty($role)) {
    header('Location: /auth/add_user.php?error=' . urlencode("Все поля обязательны для заполнения"));
    exit();
}

// Проверка длины логина
if (strlen($login) < 3) {
    header('Location: /auth/add_user.php?error=' . urlencode("Логин должен содержать не менее 3 символов"));
    exit;
}

// Проверка длины пароля
if (strlen($password) < 6) {
    header('Location: /auth/add_user.php?error=' . urlencode("Пароль должен содержать не менее 6 символов"));
    exit;
}

// Проверка совпадения паролей
if ($password !== $password_confirm) {
    header('Location: /auth/add_user.php?error=' . urlencode("Пароли не совпадают"));
    exit;
}

// Проверка возраста
if ($age === false) {
    header('Location: /auth/add_user.php?error=' . urlencode("Возраст должен быть от 12 лет"));
    exit;
}

// Проверка пола
if (!in_array($gender, ['мужской', 'женский'])) {
    header('Location: /auth/add_user.php?error=' . urlencode("Укажите корректный пол"));
    exit;
}

// Хеширование пароля
//$hashed_password = md5($password . "hiferhifurie");
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
    //$role = 'user';

    // Добавление пользователя в базу данных (обновленный запрос с age и gender)
    $stmt = $pdo->prepare("
        INSERT INTO users (name, login, pass, role, age, gender, created_at)
        VALUES (:name, :login, :pass, :role, :age, :gender, NOW())
    ");

    $stmt->execute([
        ':name' => $name,
        ':login' => $login,
        ':pass' => $hashed_password,
        ':role' => $role,
        ':age' => $age,
        ':gender' => $gender
    ]);

    // Получение ID нового пользователя
    //$userId = $pdo->lastInsertId();

    // Фиксируем транзакцию
    $pdo->commit();

    // Авторизуем пользователя
    // $_SESSION['user_id'] = $userId;
    // $_SESSION['user_name'] = $name;
    // $_SESSION['user_login'] = $login;
    // $_SESSION['user_role'] = $role;

    // Перенаправляем на страницу личного кабинета
    header('Location: /admin/index.php?success=' . urlencode("Пользователь успешно добавлен!"));
    exit;

} catch (PDOException $e) {
    echo $e->getMessage();
    // Отменяем транзакцию в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Логирование ошибки
    error_log("Ошибка при добавлении пользователя: " . $e->getMessage());

    // Перенаправляем пользователя с сообщением об ошибке
    header('Location: /auth/add_user.php?error=' . urlencode("Ошибка при регистрации: " . $e->getMessage()));
    exit;
}

// Insert into database
// try {
//     $stmt = $pdo->prepare("INSERT INTO users (name, login, password, role) VALUES (:name, :login, :password, :role)");
//     $stmt->execute([
//         ':name' => $name,
//         ':login' => $login,
//         ':password' => $hashed_password,
//         ':role' => $role
//     ]);

//     // Redirect to user list
//     header('Location: /admin/users.php');
//     exit();
// } catch (PDOException $e) {
//     // Check if duplicate login
//     if ($e->errorInfo[1] == 1062) {
//         http_response_code(400);
//         echo 'Логин уже используется.';
//     } else {
//         http_response_code(500);
//         echo 'Ошибка сервера: ' . $e->getMessage();
//     }
//     exit();
// }
?>