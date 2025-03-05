<?php
// Включение конфигурационного файла
require_once '../api/config.php';

// Запуск сессии
session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit();
}

// Получение токена из URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Проверка наличия токена
if (empty($token)) {
    header('Location: /auth/login.php?error=' . urlencode("Недействительная ссылка для сброса пароля"));
    exit();
}

// Проверка валидности токена
try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT prt.*, u.login, u.name
        FROM password_reset_tokens prt
        JOIN users u ON prt.user_id = u.id
        WHERE prt.token = :token
        AND prt.used = 0
        AND prt.expires_at > NOW()
    ");

    $stmt->execute([':token' => $token]);
    $tokenData = $stmt->fetch();

    if (!$tokenData) {
        header('Location: /auth/login.php?error=' . urlencode("Ссылка для сброса пароля недействительна или истекла"));
        exit();
    }

    // Токен действителен, отображаем форму для сброса пароля
    $userId = $tokenData['user_id'];
    $userName = $tokenData['name'];
    $userLogin = $tokenData['login'];

} catch (PDOException $e) {
    header('Location: /auth/login.php?error=' . urlencode("Ошибка при проверке ссылки сброса пароля"));
    exit();
}

// Получение сообщения об ошибке, если оно есть
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            background-color: #f8f9fa;
        }

        .auth-form-container {
            background-color: #fff;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .auth-header {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .auth-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .password-requirements {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .progress-container {
            width: 100%;
            height: 0.25rem;
            background-color: #e9ecef;
            margin-top: 0.5rem;
            border-radius: 0.125rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: #0d6efd;
            width: 0;
            transition: width 0.3s;
        }

        .error-message {
            padding: 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            color: #842029;
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .auth-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .auth-links p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-form-container">
            <div class="auth-header">
                <h1>Сброс пароля</h1>
                <p>Здравствуйте, <?php echo htmlspecialchars($userName); ?>! Создайте новый пароль для вашей учетной
                    записи.</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h1 class="h3">Сброс пароля</h1>
                        <p class="text-muted">Создайте новый пароль для вашей учетной записи</p>
                    </div>
                    
                    <div class="user-info mb-3">
                        <div class="text-center">
                            <h5><?php echo htmlspecialchars($userName); ?></h5>
                            <p class="mb-0">Логин: <?php echo htmlspecialchars($userLogin); ?></p>
                        </div>
                    </div>
                    
                    <form action="/api/reset_password.php" method="post">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Новый пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Минимум 6 символов</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">Сбросить пароль</button>
                        </div>
                        
                        <div class="text-center">
                            <p><a href="/auth/login.php">Вернуться на страницу входа</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Показ/скрытие пароля
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });

        // Проверка прочности пароля
        document.getElementById('password').addEventListener('input', function () {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');

            // Простая оценка прочности пароля
            let strength = 0;

            if (password.length >= 8) strength += 20;
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 20;

            strengthBar.style.width = strength + '%';

            // Изменение цвета индикатора в зависимости от прочности
            if (strength < 40) {
                strengthBar.style.backgroundColor = '#dc3545'; // красный
            } else if (strength < 80) {
                strengthBar.style.backgroundColor = '#ffc107'; // желтый
            } else {
                strengthBar.style.backgroundColor = '#28a745'; // зеленый
            }
        });

        // Валидация совпадения паролей
        document.getElementById('resetForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Пароли не совпадают!');
            }
        });
    </script>
</body>

</html>