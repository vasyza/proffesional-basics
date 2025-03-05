<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Подключение заголовка
include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Блок приветствия -->
    <div class="row mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 mb-4">Добро пожаловать на Портал ИТ-профессий!</h1>
            <p class="lead mb-4">Ваш надежный проводник в мире информационных технологий и профессионального развития.</p>
            <p class="mb-4">Мы помогаем ориентироваться в многообразии IT-специальностей, выбирать направление развития и находить профессиональную поддержку.</p>
            <div class="d-grid gap-2 d-md-flex">
                <a href="/professions.php" class="btn btn-primary btn-lg">Каталог профессий</a>
                <?php if (!$isLoggedIn): ?>
                    <a href="/auth/register.php" class="btn btn-outline-primary btn-lg">Регистрация</a>
                <?php else: ?>
                    <a href="/cabinet.php" class="btn btn-outline-primary btn-lg">Личный кабинет</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 d-flex align-items-center justify-content-center">
            <img src="https://placehold.co/600x400?text=IT+Professions+Portal" alt="IT Профессии" class="img-fluid rounded shadow">
        </div>
    </div>
    
    <!-- О портале -->
    <div class="row mb-5" id="about">
        <div class="col-12">
            <h2 class="text-center mb-4">О нашем портале</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-laptop-code fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Каталог профессий</h3>
                            <p class="mb-0">Подробная информация о востребованных IT-специальностях, необходимых навыках и компетенциях.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Рабочие группы</h3>
                            <p class="mb-0">Возможность объединяться в команды по интересам для совместной работы над проектами и обмена опытом.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                            <h3 class="h4 mb-3">Профессиональные консультации</h3>
                            <p class="mb-0">Помощь экспертов и консультантов в выборе направления развития и решении профессиональных вопросов.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Типы пользователей -->
    <div class="row mb-5" id="user-types">
        <div class="col-12">
            <h2 class="text-center mb-4">Кто может пользоваться порталом</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                            <h3 class="h5 mb-3">Студенты</h3>
                            <p class="mb-0">Изучайте информацию о профессиях, вступайте в рабочие группы и получайте консультации.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-star fa-3x text-primary mb-3"></i>
                            <h3 class="h5 mb-3">Эксперты</h3>
                            <p class="mb-0">Оценивайте профессии, делитесь опытом и знаниями в своей области.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                            <h3 class="h5 mb-3">Консультанты</h3>
                            <p class="mb-0">Проводите консультации, помогайте студентам в выборе профессионального пути.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                            <h3 class="h5 mb-3">Администраторы</h3>
                            <p class="mb-0">Управляйте контентом портала, пользователями и обеспечивайте корректную работу системы.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Призыв к действию -->
    <div class="row">
        <div class="col-12">
            <div class="bg-primary text-white p-5 rounded">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2 class="mb-3">Готовы начать свой путь в IT?</h2>
                        <p class="lead mb-0">Присоединяйтесь к нашему сообществу прямо сейчас и откройте для себя мир возможностей!</p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                        <?php if (!$isLoggedIn): ?>
                            <a href="/auth/register.php" class="btn btn-light btn-lg">Зарегистрироваться</a>
                        <?php else: ?>
                            <a href="/cabinet.php" class="btn btn-light btn-lg">Перейти в личный кабинет</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Подключение подвала
include 'includes/footer.php';
?>