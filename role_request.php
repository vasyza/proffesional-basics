<?php
session_start();
require_once 'api/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$pageTitle = "Запросить роль";
include 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Запросить роль</h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <form action="api/add_request.php" method="POST">
        <div class="mb-3">
            <label for="requested_role" class="form-label">Роль:</label>
            <select name="requested_role" id="requested_role" class="form-select" required>
                <option value="">-- Выберите роль --</option>
                <option value="expert">Эксперт</option>
                <option value="consultant">Консультант</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Информация о вас</label>
            <textarea name="description" id="description" class="form-control" rows="5" placeholder="Описание вас, ссылки и т. д."></textarea>
        </div>

        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Отправить запрос</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
