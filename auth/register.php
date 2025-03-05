<?php
session_start();
require_once '../api/config.php';

// Если пользователь уже авторизован, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Получение сообщений об ошибках
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Заголовок
$pageTitle = "Регистрация";
include_once '../includes/auth_header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h1 class="h3">Регистрация на портале</h1>
                        <p class="text-muted">Создайте учетную запись, чтобы получить доступ к порталу ИТ-профессий</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="/api/register.php" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Ваше имя</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Я согласен с <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">правилами использования</a> портала
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Уже есть аккаунт? <a href="/auth/login.php">Войти</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно с правилами использования -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Правила использования портала</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4>1. Общие положения</h4>
                <p>Настоящие правила регулируют отношения между администрацией портала и пользователями. Регистрация на портале означает полное и безоговорочное согласие с настоящими правилами.</p>
                
                <h4>2. Права и обязанности пользователя</h4>
                <p>2.1. Пользователь обязуется предоставлять достоверную информацию о себе.</p>
                <p>2.2. Пользователь обязуется не использовать портал для распространения информации, противоречащей законодательству РФ.</p>
                <p>2.3. Пользователь имеет право использовать портал для получения информации о профессиях в ИТ-сфере, участия в консультациях и обсуждениях.</p>
                
                <h4>3. Ответственность</h4>
                <p>3.1. Администрация портала не несет ответственности за действия пользователей.</p>
                <p>3.2. Пользователь несет полную ответственность за все действия, совершенные с использованием его учетной записи.</p>
                
                <h4>4. Конфиденциальность</h4>
                <p>4.1. Администрация портала обязуется не разглашать персональные данные пользователей третьим лицам.</p>
                <p>4.2. Администрация портала имеет право использовать обезличенные данные для анализа и улучшения работы портала.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Я согласен</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>