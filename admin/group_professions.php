<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Получение ID группы из URL
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($group_id <= 0) {
    header("Location: /admin/manage_groups.php?error=Неверный ID группы");
    exit;
}

// Обработка добавления профессии в группу
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_profession'])) {
    $profession_id = (int)$_POST['profession_id'];
    
    try {
        $pdo = getDbConnection();
        
        // Проверка существования такой связи
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_professions WHERE group_id = ? AND profession_id = ?");
        $stmt->execute([$group_id, $profession_id]);
        
        if ($stmt->fetchColumn() == 0) {
            // Добавление профессии в группу
            $stmt = $pdo->prepare("INSERT INTO group_professions (group_id, profession_id) VALUES (?, ?)");
            $stmt->execute([$group_id, $profession_id]);
            
            $success = "Профессия успешно добавлена в группу";
        } else {
            $error = "Эта профессия уже добавлена в группу";
        }
    } catch (PDOException $e) {
        $error = "Ошибка базы данных: " . $e->getMessage();
    }
}

// Обработка удаления профессии из группы
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    
    try {
        $pdo = getDbConnection();
        
        // Удаление профессии из группы
        $stmt = $pdo->prepare("DELETE FROM group_professions WHERE group_id = ? AND profession_id = ?");
        $stmt->execute([$group_id, $remove_id]);
        
        $success = "Профессия успешно удалена из группы";
    } catch (PDOException $e) {
        $error = "Ошибка базы данных: " . $e->getMessage();
    }
}

// Получение данных
try {
    $pdo = getDbConnection();
    
    // Получение информации о группе
    $stmt = $pdo->prepare("SELECT * FROM student_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();
    
    if (!$group) {
        header("Location: /admin/manage_groups.php?error=Группа не найдена");
        exit;
    }
    
    // Получение профессий, уже добавленных в группу
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM professions p
        JOIN group_professions gp ON p.id = gp.profession_id
        WHERE gp.group_id = ?
        ORDER BY p.title
    ");
    $stmt->execute([$group_id]);
    $group_professions = $stmt->fetchAll();
    
    // Получение всех доступных профессий для добавления
    $stmt = $pdo->query("SELECT id, title, type FROM professions ORDER BY title");
    $all_professions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Ошибка базы данных: " . $e->getMessage();
    $group = null;
    $group_professions = [];
    $all_professions = [];
}

// Получение сообщений
$error = isset($error) ? $error : (isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '');
$success = isset($success) ? $success : (isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '');

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Управление профессиями в группе "<?php echo htmlspecialchars($group['name']); ?>"</h1>
        <a href="/admin/manage_groups.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к группам
        </a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Добавление профессии в группу</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="profession_id" class="form-label">Выберите профессию</label>
                            <select name="profession_id" id="profession_id" class="form-select" required>
                                <option value="">-- Выберите профессию --</option>
                                <?php foreach ($all_professions as $profession): ?>
                                    <option value="<?php echo $profession['id']; ?>">
                                        <?php echo htmlspecialchars($profession['title']); ?> 
                                        (<?php echo htmlspecialchars($profession['type'] ?: 'Не указан'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_profession" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Добавить в группу
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Профессии в группе</h5>
                    <span class="badge bg-primary"><?php echo count($group_professions); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($group_professions)): ?>
                        <p class="text-muted">Профессии не добавлены в эту группу</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($group_professions as $prof): ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($prof['title']); ?></h6>
                                        <small class="text-muted">
                                            Тип: <?php echo htmlspecialchars($prof['type'] ?: 'Не указан'); ?>
                                        </small>
                                    </div>
                                    <a href="?id=<?php echo $group_id; ?>&remove=<?php echo $prof['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Вы уверены, что хотите удалить эту профессию из группы?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?> 