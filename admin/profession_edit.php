<?php
session_start();
require_once '../api/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

$professionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($professionId <= 0) {
    header("Location: /admin/professions.php?error=" . urlencode("Неверный ID профессии"));
    exit;
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM professions WHERE id = ?");
    $stmt->execute([$professionId]);
    $profession = $stmt->fetch();

    if (!$profession) {
        header("Location: /admin/professions.php?error=" . urlencode("Профессия не найдена"));
        exit;
    }
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <h1>Редактирование профессии</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Информация о профессии</h5>
        </div>
        <div class="card-body">
            <form action="/api/update_profession.php" method="post">
                <input type="hidden" name="id" value="<?php echo $profession['id']; ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Название профессии</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($profession['title']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Тип</label>
                    <input type="text" class="form-control" id="type" name="type" value="<?php echo htmlspecialchars($profession['type']); ?>">
                </div>

                <div class="mb-3">
                    <label for="salary_range" class="form-label">Диапазон зарплат</label>
                    <input type="text" class="form-control" id="salary_range" name="salary_range" value="<?php echo htmlspecialchars($profession['salary_range']); ?>">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Описание</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($profession['description']); ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="/admin/professions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Назад
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
