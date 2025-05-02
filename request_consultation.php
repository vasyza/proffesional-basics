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

    // Получение всех доступных консультантов
    $stmt = $pdo->prepare("
        SELECT u.id, u.name
        FROM users u
        WHERE u.role = 'consultant'
        ORDER BY u.name ASC
    ");
    $stmt->execute();
    $consultants = $stmt->fetchAll();

    // Получение всех профессий
    $stmt = $pdo->prepare("
        SELECT id, title
        FROM professions
        ORDER BY title ASC
    ");
    $stmt->execute();
    $professions = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
}

$pageTitle = "Запрос консультации";
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <h1>Запрос консультации</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="/api/send_consultation_request.php" method="post">
                <div class="mb-3">
                    <label for="consultant_id" class="form-label">Выберите консультанта <span class="text-danger">*</span></label>
                    <select class="form-select" id="consultant_id" name="consultant_id" required>
                        <option value="">-- Выберите консультанта --</option>
                        <?php foreach ($consultants as $consultant): ?>
                            <option value="<?php echo $consultant['id']; ?>">
                                <?php echo htmlspecialchars($consultant['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="profession_id" class="form-label">Профессия (по теме консультации) <span class="text-danger">*</span></label>
                    <select class="form-select" id="profession_id" name="profession_id" required>
                        <option value="">-- Выберите профессию --</option>
                        <?php foreach ($professions as $profession): ?>
                            <option value="<?php echo $profession['id']; ?>">
                                <?php echo htmlspecialchars($profession['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="topic" class="form-label">Тема консультации <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="topic" name="topic" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Описание / Вопрос</label>
                    <textarea class="form-control" id="message" name="message" rows="4"></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/cabinet.php" class="btn btn-secondary">Отмена</a>
                    <button type="submit" class="btn btn-primary">Отправить запрос</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>