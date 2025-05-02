<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Получение сообщений
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

try {
    $pdo = getDbConnection();
    
    // Получение данных пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: /index.php?error=" . urlencode("Пользователь не найден"));
        exit;
    }
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

// Заголовок
$pageTitle = "Редактирование профиля";
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <h1>Редактирование профиля</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Личные данные</h5>
                </div>
                <div class="card-body">
                    <form action="/api/update_profile.php" method="post">
                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login" value="<?php echo htmlspecialchars($user['login']); ?>" readonly>
                            <div class="form-text">Логин изменить нельзя</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Имя <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Текущий пароль</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Необходимо указать для изменения пароля</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Новый пароль</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Оставьте поле пустым, если не хотите менять пароль</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">О себе</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/cabinet.php" class="btn btn-secondary">Отмена</a>
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Информация о профиле</h5>
                </div>
                <div class="card-body">
                    <p><strong>Роль:</strong> 
                        <span class="badge <?php 
                            echo $user['role'] === 'admin' ? 'bg-danger' : 
                                ($user['role'] === 'expert' ? 'bg-primary' : 
                                    ($user['role'] === 'consultant' ? 'bg-success' : 'bg-secondary')); 
                        ?>">
                            <?php 
                            switch ($user['role']) {
                                case 'admin': echo 'Администратор'; break;
                                case 'expert': echo 'Эксперт'; break;
                                case 'consultant': echo 'Консультант'; break;
                                default: echo 'Студент';
                            }
                            ?>
                        </span>
                    </p>
                    <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                    
                    <?php if ($user['role'] === 'expert'): ?>
                        <?php
                        // Получение статистики для эксперта
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as ratings_count 
                            FROM expert_ratings 
                            WHERE expert_id = ?
                        ");
                        $stmt->execute([$userId]);
                        $expertStats = $stmt->fetch();
                        ?>
                        <hr>
                        <h6>Статистика эксперта</h6>
                        <p>Оценено профессий: <?php echo $expertStats['ratings_count']; ?></p>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'consultant'): ?>
                        <?php
                        // Получение статистики для консультанта
                        $stmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as total,
                                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                            FROM consultations 
                            WHERE consultant_id = ?
                        ");
                        $stmt->execute([$userId]);
                        $consultantStats = $stmt->fetch();
                        ?>
                        <hr>
                        <h6>Статистика консультанта</h6>
                        <p>Всего консультаций: <?php echo $consultantStats['total']; ?></p>
                        <p>Завершено: <?php echo $consultantStats['completed']; ?></p>
                        <p>Запланировано: <?php echo $consultantStats['scheduled']; ?></p>
                        <p>Ожидают: <?php echo $consultantStats['pending']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?> 