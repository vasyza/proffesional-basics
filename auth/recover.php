<?php
session_start();
require_once '../api/config.php';

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Получение сообщений
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Заголовок
$pageTitle = "Восстановление пароля";
include_once '../includes/auth_header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h1 class="h3">Восстановление пароля</h1>
                        <p class="text-muted">Введите ваш логин для получения инструкций по восстановлению пароля</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form action="/api/recover.php" method="post">
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login" required>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">Восстановить пароль</button>
                        </div>
                        
                        <div class="text-center">
                            <p>Вспомнили пароль? <a href="/auth/login.php">Войти</a></p>
                            <p>Нет аккаунта? <a href="/auth/register.php">Зарегистрироваться</a></p>
                            <p><a href="/">Вернуться на главную</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 