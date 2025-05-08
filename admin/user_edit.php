<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Проверка наличия id пользователя в URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /admin/users.php?error=" . urlencode('Некорректный ID пользователя.'));
    exit;
}

$editUserId = intval($_GET['id']);

// Нельзя редактировать самого себя через этот интерфейс
if ($editUserId == $_SESSION['user_id']) {
    header("Location: /admin/users.php?error=" . urlencode('Нельзя редактировать свой аккаунт здесь.'));
    exit;
}

// Получение данных пользователя
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editUserId]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: /admin/users.php?error=" . urlencode('Пользователь не найден.'));
        exit;
    }
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Получение сообщений
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Редактирование пользователя</h1>

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
            <form action="/api/update_user.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        <div class="invalid-feedback">Пожалуйста, введите имя</div>
                    </div>

                    <div class="col-md-6">
                        <label for="login" class="form-label">Логин</label>
                        <input type="text" class="form-control" id="login" name="login" value="<?php echo htmlspecialchars($user['login']); ?>" required>
                        <div class="invalid-feedback">Пожалуйста, введите логин</div>
                    </div>

                    <div class="col-md-6">
                        <label for="age" class="form-label">Возраст</label>
                        <input type="number" class="form-control" id="age" name="age" value="<?php echo htmlspecialchars($user['age'] ?? ''); ?>" min="12" required>
                        <div class="invalid-feedback">Пожалуйста, введите возраст (от 12)</div>
                    </div>

                    <div class="col-md-6">
                        <label for="gender" class="form-label">Пол</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="мужской" <?php if (($user['gender'] ?? '') === 'мужской') echo 'selected'; ?>>мужской</option>
                            <option value="женский" <?php if (($user['gender'] ?? '') === 'женский') echo 'selected'; ?>>женский</option>
                        </select>
                        <div class="invalid-feedback">Пожалуйста, выберите пол</div>
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label">Роль</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Администратор</option>
                            <option value="expert" <?php if ($user['role'] === 'expert') echo 'selected'; ?>>Эксперт</option>
                            <option value="consultant" <?php if ($user['role'] === 'consultant') echo 'selected'; ?>>Консультант</option>
                            <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>Студент</option>
                        </select>
                        <div class="invalid-feedback">Пожалуйста, выберите роль</div>
                    </div>

                    <div class="col-md-12">
                        <label for="bio" class="form-label">О себе</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save me-1"></i>Сохранить изменения
                        </button>
                        <a href="/admin/users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Вернуться назад
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
