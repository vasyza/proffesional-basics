<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'expert'])) {
    header("Location: /auth/login.php");
    exit;
}

$criterionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($criterionId <= 0) {
    header("Location: lab7_criteria_management.php");
    exit;
}

$error = '';
$success = '';

try {
    $pdo = getDbConnection();
    
    // Получение данных критерия
    $stmt = $pdo->prepare("SELECT * FROM pvk_criteria WHERE id = ?");
    $stmt->execute([$criterionId]);
    $criterion = $stmt->fetch();
    
    if (!$criterion) {
        header("Location: lab7_criteria_management.php?error=Критерий не найден");
        exit;
    }
    
    // Получение связанных ПВК
    $stmt = $pdo->prepare("
        SELECT cp.*, pq.name as pvk_name, pq.category
        FROM criterion_to_pvk cp
        JOIN professional_qualities pq ON cp.pvk_id = pq.id
        WHERE cp.criterion_id = ?
        ORDER BY pq.category, pq.name
    ");
    $stmt->execute([$criterionId]);
    $linkedPvks = $stmt->fetchAll();
    
    // Получение доступных ПВК для связывания
    $stmt = $pdo->prepare("
        SELECT pq.* 
        FROM professional_qualities pq
        WHERE pq.id NOT IN (
            SELECT pvk_id FROM criterion_to_pvk WHERE criterion_id = ?
        )
        ORDER BY pq.category, pq.name
    ");
    $stmt->execute([$criterionId]);
    $availablePvks = $stmt->fetchAll();
    
    // Получение индикаторов тестов
    $stmt = $pdo->prepare("
        SELECT cti.*, tn.name as test_name
        FROM criterion_test_indicators cti
        LEFT JOIN test_names tn ON cti.test_type = tn.test_type
        WHERE cti.criterion_id = ?
        ORDER BY cti.test_type, cti.indicator_name
    ");
    $stmt->execute([$criterionId]);
    $testIndicators = $stmt->fetchAll();
    
    // Получение доступных типов тестов
    $stmt = $pdo->query("SELECT test_type, name FROM test_names ORDER BY name");
    $availableTests = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка при загрузке данных: ' . $e->getMessage();
}

// Обработка добавления ПВК к критерию
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'link_pvk') {
    $pvkId = (int)$_POST['pvk_id'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO criterion_to_pvk (criterion_id, pvk_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
        $stmt->execute([$criterionId, $pvkId]);
        $success = 'ПВК успешно привязано к критерию';
        header("Location: criterion_edit.php?id=$criterionId&success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = 'Ошибка при привязке ПВК: ' . $e->getMessage();
    }
}

// Обработка добавления индикатора теста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_indicator') {
    $testType = $_POST['test_type'];
    $indicatorName = $_POST['indicator_name'];
    $indicatorWeight = (float)$_POST['indicator_weight'];
    $assessmentDirection = $_POST['assessment_direction'];
    $cutoffValue = !empty($_POST['cutoff_value']) ? (float)$_POST['cutoff_value'] : null;
    $cutoffOperator = !empty($_POST['cutoff_operator']) ? $_POST['cutoff_operator'] : null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO criterion_test_indicators 
            (criterion_id, test_type, indicator_name, indicator_weight, assessment_direction, cutoff_value, cutoff_comparison_operator)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT (criterion_id, test_type, indicator_name) 
            DO UPDATE SET 
                indicator_weight = EXCLUDED.indicator_weight,
                assessment_direction = EXCLUDED.assessment_direction,
                cutoff_value = EXCLUDED.cutoff_value,
                cutoff_comparison_operator = EXCLUDED.cutoff_comparison_operator
        ");
        $stmt->execute([$criterionId, $testType, $indicatorName, $indicatorWeight, $assessmentDirection, $cutoffValue, $cutoffOperator]);
        $success = 'Индикатор успешно добавлен';
        header("Location: criterion_edit.php?id=$criterionId&success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = 'Ошибка при добавлении индикатора: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

$pageTitle = "Редактирование критерия: " . htmlspecialchars($criterion['name']);
include '../includes/admin_header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin/index.php">Админ-панель</a></li>
                    <li class="breadcrumb-item"><a href="lab7_criteria_management.php">Критерии ПВК</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($criterion['name']); ?></li>
                </ol>
            </nav>
            
            <h1 class="mb-4">Редактирование критерия: <?php echo htmlspecialchars($criterion['name']); ?></h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Информация о критерии -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title mb-0">Информация о критерии</h3>
                </div>
                <div class="card-body">
                    <p><strong>Название:</strong> <?php echo htmlspecialchars($criterion['name']); ?></p>
                    <p><strong>Описание:</strong> <?php echo htmlspecialchars($criterion['description']); ?></p>
                    <p><strong>Создан:</strong> <?php echo date('d.m.Y H:i', strtotime($criterion['created_at'])); ?></p>
                </div>
            </div>
            
            <!-- Связанные ПВК -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Связанные ПВК</h3>
                </div>
                <div class="card-body">
                    <?php if (count($linkedPvks) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Категория</th>
                                        <th>Название ПВК</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($linkedPvks as $pvk): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pvk['category']); ?></td>
                                            <td><?php echo htmlspecialchars($pvk['pvk_name']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="unlinkPvk(<?php echo $pvk['pvk_id']; ?>)">
                                                    <i class="fas fa-unlink"></i> Отвязать
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Нет связанных ПВК</p>
                    <?php endif; ?>
                    
                    <!-- Форма добавления ПВК -->
                    <?php if (count($availablePvks) > 0): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="link_pvk">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="pvk_id" class="form-label">Добавить ПВК</label>
                                    <select name="pvk_id" id="pvk_id" class="form-select" required>
                                        <option value="">Выберите ПВК...</option>
                                        <?php 
                                        $currentCategory = '';
                                        foreach ($availablePvks as $pvk): 
                                            if ($currentCategory != $pvk['category']):
                                                if ($currentCategory != '') echo '</optgroup>';
                                                $currentCategory = $pvk['category'];
                                                echo '<optgroup label="' . htmlspecialchars($currentCategory) . '">';
                                            endif;
                                        ?>
                                            <option value="<?php echo $pvk['id']; ?>"><?php echo htmlspecialchars($pvk['name']); ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($currentCategory != '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Добавить
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Индикаторы тестов -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h3 class="card-title mb-0">Индикаторы тестов</h3>
                </div>
                <div class="card-body">
                    <?php if (count($testIndicators) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Тест</th>
                                        <th>Индикатор</th>
                                        <th>Вес</th>
                                        <th>Направление</th>
                                        <th>Отсечка</th>
                                        <th>Оператор</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($testIndicators as $indicator): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($indicator['test_name'] ?? $indicator['test_type']); ?></td>
                                            <td><?php echo htmlspecialchars($indicator['indicator_name']); ?></td>
                                            <td><?php echo $indicator['indicator_weight']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $indicator['assessment_direction'] === 'higher_is_better' ? 'bg-success' : 'bg-primary'; ?>">
                                                    <?php echo $indicator['assessment_direction'] === 'higher_is_better' ? 'Выше - лучше' : 'Ниже - лучше'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $indicator['cutoff_value'] ?? '-'; ?></td>
                                            <td><?php echo $indicator['cutoff_comparison_operator'] ?? '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="removeIndicator(<?php echo $indicator['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Нет настроенных индикаторов</p>
                    <?php endif; ?>
                    
                    <!-- Форма добавления индикатора -->
                    <form method="POST" class="mt-3 border-top pt-3">
                        <input type="hidden" name="action" value="add_indicator">
                        <h5>Добавить новый индикатор</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="test_type" class="form-label">Тип теста</label>
                                <select name="test_type" id="test_type" class="form-select" required>
                                    <option value="">Выберите тест...</option>
                                    <?php foreach ($availableTests as $test): ?>
                                        <option value="<?php echo htmlspecialchars($test['test_type']); ?>">
                                            <?php echo htmlspecialchars($test['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="indicator_name" class="form-label">Индикатор</label>
                                <select name="indicator_name" id="indicator_name" class="form-select" required>
                                    <option value="">Выберите индикатор...</option>
                                    <option value="average_time">Среднее время</option>
                                    <option value="accuracy">Точность</option>
                                    <option value="normalized_result">Нормализованный результат</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="indicator_weight" class="form-label">Вес</label>
                                <input type="number" step="0.1" name="indicator_weight" id="indicator_weight" 
                                       class="form-control" value="1.0" required>
                            </div>
                            <div class="col-md-2">
                                <label for="assessment_direction" class="form-label">Направление</label>
                                <select name="assessment_direction" id="assessment_direction" class="form-select" required>
                                    <option value="">Выберите...</option>
                                    <option value="higher_is_better">Выше - лучше</option>
                                    <option value="lower_is_better">Ниже - лучше</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="cutoff_value" class="form-label">Отсечка</label>
                                <input type="number" step="0.1" name="cutoff_value" id="cutoff_value" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <label for="cutoff_operator" class="form-label">Оператор сравнения</label>
                                <select name="cutoff_operator" id="cutoff_operator" class="form-select">
                                    <option value="">Не использовать</option>
                                    <option value=">=">&gt;=</option>
                                    <option value="<=">&lt;=</option>
                                    <option value=">">&gt;</option>
                                    <option value="<">&lt;</option>
                                    <option value="==">=</option>
                                    <option value="!=">!=</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Добавить индикатор
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="lab7_criteria_management.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Вернуться к списку критериев
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function unlinkPvk(pvkId) {
    if (confirm('Вы уверены, что хотите отвязать это ПВК от критерия?')) {
        fetch('/api/lab7/criterion_unlink_pvk.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                criterion_id: <?php echo $criterionId; ?>,
                pvk_id: pvkId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
            }
        });
    }
}

function removeIndicator(indicatorId) {
    if (confirm('Вы уверены, что хотите удалить этот индикатор?')) {
        fetch('/api/lab7/criterion_remove_indicator.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                indicator_id: indicatorId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
            }
        });
    }
}
</script>

<?php include '../includes/admin_footer.php'; ?>
