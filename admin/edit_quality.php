<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Проверка ID качества
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /admin/manage_qualities.php?error=" . urlencode("Не указан ID качества"));
    exit;
}

$quality_id = (int)$_GET['id'];
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

try {
    $pdo = getDbConnection();
    
    // Получение данных качества
    $stmt = $pdo->prepare("SELECT * FROM professional_qualities WHERE id = :id");
    $stmt->bindParam(':id', $quality_id);
    $stmt->execute();
    $quality = $stmt->fetch();
    
    if (!$quality) {
        header("Location: /admin/manage_qualities.php?error=" . urlencode("Качество не найдено"));
        exit;
    }
    
    // Получение категорий для выбора
    $stmt = $pdo->query("SELECT DISTINCT category FROM professional_qualities ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Редактирование профессионально важного качества</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Редактирование качества: <?php echo htmlspecialchars($quality['name']); ?></h5>
        </div>
        <div class="card-body">
            <form action="/api/update_quality.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?php echo $quality_id; ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Название качества</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($quality['name']); ?>" required>
                        <div class="invalid-feedback">
                            Пожалуйста, введите название качества
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Категория</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               value="<?php echo htmlspecialchars($quality['category']); ?>" 
                               list="categories" required>
                        <datalist id="categories">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="invalid-feedback">
                            Пожалуйста, укажите категорию
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="5" required><?php echo htmlspecialchars($quality['description']); ?></textarea>
                        <div class="invalid-feedback">
                            Пожалуйста, добавьте описание качества
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save me-1"></i>Сохранить изменения
                        </button>
                        <a href="/admin/manage_qualities.php" class="btn btn-secondary">
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
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php include_once '../includes/admin_footer.php'; ?> 