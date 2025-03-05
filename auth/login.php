<?php
session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit();
}

// Получение сообщения об ошибке или успеха, если они есть
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Портал ИТ-профессий</title>
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

        .error-message {
            padding: 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            color: #842029;
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .success-message {
            padding: 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            color: #0f5132;
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
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
                <h1>Вход на портал</h1>
                <p>Введите свои учетные данные для доступа к порталу ИТ-профессий</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="../api/auth.php" method="POST">
                <div class="form-group">
                    <label for="login" class="form-label">Логин</label>
                    <input type="text" class="form-control" id="login" name="login" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="pass" class="form-label">Пароль</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="pass" name="pass" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Запомнить меня
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Войти</button>
            </form>

            <div class="auth-links">
                <p><a href="recover.php">Забыли пароль?</a></p>
                <p>Еще нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                <p><a href="/">Вернуться на главную</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Показ/скрытие пароля
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('pass');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    </script>
</body>
</html>
