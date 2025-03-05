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

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Добавление новой профессии</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Информация о профессии</h5>
        </div>
        <div class="card-body">
            <form action="/api/add_profession.php" method="post">
                <div class="mb-3">
                    <label for="title" class="form-label">Наименование профессии <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="category" class="form-label">Категория</label>
                    <input type="text" class="form-control" id="category" name="category">
                    <div class="form-text">Например: Разработка, Аналитика, Тестирование, Менеджмент и т.д.</div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Описание профессии <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="requirements" class="form-label">Требования</label>
                    <textarea class="form-control" id="requirements" name="requirements" rows="5"></textarea>
                    <div class="form-text">Опишите необходимые навыки, знания и компетенции для данной профессии.</div>
                </div>
                
                <div class="mb-3">
                    <label for="duties" class="form-label">Обязанности</label>
                    <textarea class="form-control" id="duties" name="duties" rows="5"></textarea>
                    <div class="form-text">Опишите основные задачи и обязанности специалиста данной профессии.</div>
                </div>
                
                <div class="mb-3">
                    <label for="salary_range" class="form-label">Диапазон зарплат</label>
                    <input type="text" class="form-control" id="salary_range" name="salary_range">
                    <div class="form-text">Например: 80 000 - 150 000 руб.</div>
                </div>
                
                <div class="mb-3">
                    <label for="career_path" class="form-label">Карьерный путь</label>
                    <textarea class="form-control" id="career_path" name="career_path" rows="3"></textarea>
                    <div class="form-text">Опишите возможные варианты развития карьеры.</div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="/admin/professions.php" class="btn btn-secondary">Отмена</a>
                    <button type="submit" class="btn btn-primary">Добавить профессию</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?> 