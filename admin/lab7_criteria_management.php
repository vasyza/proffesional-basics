<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'expert'])) {
    header("Location: /auth/login.php");
    exit;
}

$error = '';
$success = '';

// Обработка создания нового критерия
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_criterion') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name) || empty($description)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("INSERT INTO pvk_criteria (name, description, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $_SESSION['user_id']]);
            $success = 'Критерий успешно создан';
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = 'Критерий с таким названием уже существует';
            } else {
                $error = 'Ошибка базы данных: ' . $e->getMessage();
            }
        }
    }
}

// Получение списка критериев
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("
        SELECT c.*, u.name as created_by_name 
        FROM pvk_criteria c
        LEFT JOIN users u ON c.created_by = u.id
        ORDER BY c.name
    ");
    $criteria = $stmt->fetchAll();
      // Получение списка профессий для связывания
    $stmt = $pdo->query("SELECT id, title FROM professions ORDER BY title");
    $professions = $stmt->fetchAll();    // Получение существующих связей профессии-критерии
    $stmt = $pdo->query("
        SELECT 
            ptc.profession_id,
            ptc.criterion_id,
            ptc.criterion_weight,
            p.title as profession_name,
            c.name as criterion_name
        FROM profession_to_criteria ptc
        JOIN professions p ON ptc.profession_id = p.id
        JOIN pvk_criteria c ON ptc.criterion_id = c.id
        ORDER BY p.title, c.name
    ");
    $profession_criteria_links = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка при загрузке данных: ' . $e->getMessage();
    $criteria = [];
    $professions = [];
    $profession_criteria_links = [];
}

$pageTitle = "Управление критериями ПВК";
include '../includes/admin_header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Управление критериями оценки ПВК</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Форма создания нового критерия -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Создать новый критерий</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="create_criterion">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Название критерия</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">
                                    Пожалуйста, введите название критерия
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="description" class="form-label">Описание</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback">
                                    Пожалуйста, добавьте описание критерия
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Создать критерий
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Список существующих критериев -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title mb-0">Существующие критерии</h3>
                </div>
                <div class="card-body p-0">
                    <?php if (count($criteria) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Название</th>
                                        <th>Описание</th>
                                        <th>Создан</th>
                                        <th>Автор</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($criteria as $index => $criterion): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($criterion['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars(mb_substr($criterion['description'], 0, 100)); ?>...</td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($criterion['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($criterion['created_by_name'] ?? 'Неизвестно'); ?></td>
                                            <td>
                                                <a href="criterion_edit.php?id=<?php echo $criterion['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Редактировать
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">Критерии не найдены. Создайте первый критерий выше.</p>
                        </div>
                    <?php endif; ?>
                </div>            </div>
            
            <!-- Секция связывания критериев с профессиями -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title mb-0">Связывание критериев с профессиями</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Создать новую связь</h5>
                            <form id="linkCriterionForm" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="profession_select" class="form-label">Профессия</label>
                                    <select class="form-select" id="profession_select" name="profession_id" required>
                                        <option value="">Выберите профессию</option>                                        <?php foreach ($professions as $profession): ?>
                                            <option value="<?php echo $profession['id']; ?>">
                                                <?php echo htmlspecialchars($profession['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="criterion_select" class="form-label">Критерий</label>
                                    <select class="form-select" id="criterion_select" name="criterion_id" required>
                                        <option value="">Выберите критерий</option>
                                        <?php foreach ($criteria as $criterion): ?>
                                            <option value="<?php echo $criterion['id']; ?>">
                                                <?php echo htmlspecialchars($criterion['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Вес критерия (0.1 - 1.0)</label>
                                    <input type="number" class="form-control" id="weight" name="weight" 
                                           min="0.1" max="1.0" step="0.1" value="1.0" required>
                                </div>
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-link"></i> Связать
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h5>Существующие связи</h5>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <?php if (!empty($profession_criteria_links)): ?>
                                    <table class="table table-sm">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Профессия</th>
                                                <th>Критерий</th>
                                                <th>Вес</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($profession_criteria_links as $link): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($link['profession_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($link['criterion_name']); ?></td>
                                                    <td><?php echo $link['criterion_weight']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="unlinkCriterion(<?php echo $link['profession_id']; ?>, <?php echo $link['criterion_id']; ?>)">
                                                            <i class="fas fa-unlink"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">Связи не найдены</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="/admin/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Вернуться в админ-панель
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Handle criterion-profession linking
document.getElementById('linkCriterionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        profession_id: formData.get('profession_id'),
        criterion_id: formData.get('criterion_id'),
        criterion_weight: formData.get('weight')
    };
    
    fetch('/api/lab7/profession_link_criterion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Refresh to show new link
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при связывании');
    });
});

// Function to unlink criterion from profession
function unlinkCriterion(professionId, criterionId) {
    if (confirm('Вы уверены, что хотите разорвать эту связь?')) {
        fetch('/api/lab7/profession_unlink_criterion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                profession_id: professionId,
                criterion_id: criterionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh to remove the link
            } else {
                alert('Ошибка: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при разрыве связи');
        });
    }
}
</script>

<?php include '../includes/admin_footer.php'; ?>
