<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Получение сообщений
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Добавление нового пользователя</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Информация о пользователе</h5>
        </div>
        <div class="card-body">
            <form action="/api/add_user.php" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">
                            Пожалуйста, введите имя пользователя
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="login" class="form-label">Логин</label>
                        <input type="text" class="form-control" id="login" name="login" required>
                        <div class="invalid-feedback">
                            Пожалуйста, введите логин
                        </div>
                        <small class="text-muted">Минимум 3 символа</small>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Пароль</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Пожалуйста, введите пароль
                        </div>
                        <small class="text-muted">Минимум 6 символов</small>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password_confirm')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Пожалуйста, подтвердите пароль
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="age" class="form-label">Возраст</label>
                        <input type="number" class="form-control" id="age" name="age" min="12" required>
                        <div class="invalid-feedback">
                            Пожалуйста, введите возраст (от 12 лет)
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="gender" class="form-label">Пол</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Выберите пол</option>
                            <option value="мужской">мужской</option>
                            <option value="женский">женский</option>
                        </select>
                        <div class="invalid-feedback">
                            Пожалуйста, выберите пол
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Роль пользователя</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Выберите роль</option>
                            <option value="admin">Администратор</option>
                            <option value="expert">Эксперт</option>
                            <option value="consultant">Консультант</option>
                            <option value="student" selected>Студент</option>
                        </select>
                        <div class="invalid-feedback">
                            Пожалуйста, выберите роль
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-user-plus me-1"></i>Создать пользователя
                        </button>
                        <a href="/admin/users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Вернуться к списку
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Валидация формы
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Проверка совпадения паролей
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirm').value;
                
                if (password !== passwordConfirm) {
                    event.preventDefault();
                    alert('Пароли не совпадают');
                    return false;
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    // Функция переключения видимости пароля
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>

<?php include_once '../includes/admin_footer.php'; ?> 