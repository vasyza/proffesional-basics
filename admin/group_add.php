<!-- <?php
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

try {
    $pdo = getDbConnection();
    
    // Получение списка пользователей для руководителя группы
    $stmt = $pdo->query("SELECT id, name, login FROM users ORDER BY name");
    $users = $stmt->fetchAll();
    
    // Получение списка профессий
    $stmt = $pdo->query("SELECT id, title FROM professions ORDER BY title");
    $professions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
    $users = [];
    $professions = [];
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Добавление новой рабочей группы</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Информация о группе</h5>
        </div>
        <div class="card-body">
            <form action="/api/add_group.php" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Название группы</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">
                            Пожалуйста, введите название группы
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="leader_id" class="form-label">Руководитель группы</label>
                        <select class="form-select" id="leader_id" name="leader_id">
                            <option value="">Выберите руководителя</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['login']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Описание группы</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        <div class="invalid-feedback">
                            Пожалуйста, добавьте описание группы
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Связанные профессии</label>
                        <div class="card">
                            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                                <?php if (count($professions) > 0): ?>
                                    <?php foreach ($professions as $profession): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="professions[]" 
                                                   value="<?php echo $profession['id']; ?>" 
                                                   id="profession_<?php echo $profession['id']; ?>">
                                            <label class="form-check-label" for="profession_<?php echo $profession['id']; ?>">
                                                <?php echo htmlspecialchars($profession['title']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Нет доступных профессий</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-plus me-1"></i>Создать группу
                        </button>
                        <a href="/admin/manage_groups.php" class="btn btn-secondary">
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

<?php include_once '../includes/admin_footer.php'; ?>  -->