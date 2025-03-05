<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}

// Получение сообщений
$errorMsg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$successMsg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

try {
    $pdo = getDbConnection();
    
    // Получение списка всех ПВК
    $stmt = $pdo->query("SELECT * FROM professional_qualities ORDER BY category, name");
    $qualities = $stmt->fetchAll();
    
    // Получение категорий ПВК для фильтрации
    $stmtCategories = $pdo->query("SELECT DISTINCT category FROM professional_qualities ORDER BY category");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $errorMsg = "Ошибка базы данных: " . $e->getMessage();
    $qualities = [];
    $categories = [];
}

// Подключение заголовка
include_once '../includes/admin_header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Управление профессионально важными качествами (ПВК)</h1>
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
    <?php endif; ?>
    
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    
    <!-- Форма добавления ПВК -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Добавление нового качества</h2>
        </div>
        <div class="card-body">
            <form action="/api/add_quality.php" method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Название качества</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">
                            Пожалуйста, введите название качества
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Категория</label>
                        <input type="text" class="form-control" id="category" name="category" list="categories" required>
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
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        <div class="invalid-feedback">
                            Пожалуйста, добавьте описание качества
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Добавить качество
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Список ПВК -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Список профессионально важных качеств</h2>
            
            <!-- Фильтр по категории -->
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="categoryFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-filter me-1"></i>Все категории
                </button>
                <ul class="dropdown-menu" aria-labelledby="categoryFilterDropdown">
                    <li><a class="dropdown-item" href="#" data-category="all">Все категории</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($categories as $category): ?>
                        <li><a class="dropdown-item" href="#" data-category="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="qualitiesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Описание</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($qualities as $quality): ?>
                            <tr data-category="<?php echo htmlspecialchars($quality['category']); ?>">
                                <td><?php echo $quality['id']; ?></td>
                                <td><?php echo htmlspecialchars($quality['name']); ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?php echo htmlspecialchars($quality['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($quality['description'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editQualityModal" 
                                                data-id="<?php echo $quality['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($quality['name']); ?>"
                                                data-category="<?php echo htmlspecialchars($quality['category']); ?>"
                                                data-description="<?php echo htmlspecialchars($quality['description']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $quality['id']; ?>, '<?php echo htmlspecialchars($quality['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования качества -->
<div class="modal fade" id="editQualityModal" tabindex="-1" aria-labelledby="editQualityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editQualityModalLabel">Редактирование качества</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/api/update_quality.php" method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Название качества</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Категория</label>
                        <input type="text" class="form-control" id="edit_category" name="category" list="edit_categories" required>
                        <datalist id="edit_categories">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Описание</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Вы действительно хотите удалить качество <span id="deleteName"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Удалить</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Обработка формы с валидацией
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

    // Фильтрация по категориям
    document.querySelectorAll('[data-category]').forEach(function(categoryLink) {
        categoryLink.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            const dropdownToggle = document.getElementById('categoryFilterDropdown');
            
            if (category === 'all') {
                document.querySelectorAll('#qualitiesTable tbody tr').forEach(function(row) {
                    row.style.display = '';
                });
                dropdownToggle.innerHTML = '<i class="fas fa-filter me-1"></i>Все категории';
            } else {
                document.querySelectorAll('#qualitiesTable tbody tr').forEach(function(row) {
                    if (row.getAttribute('data-category') === category) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                dropdownToggle.innerHTML = '<i class="fas fa-filter me-1"></i>' + category;
            }
        });
    });

    // Обработка модального окна редактирования
    var editQualityModal = document.getElementById('editQualityModal');
    if (editQualityModal) {
        editQualityModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var category = button.getAttribute('data-category');
            var description = button.getAttribute('data-description');
            
            this.querySelector('#edit_id').value = id;
            this.querySelector('#edit_name').value = name;
            this.querySelector('#edit_category').value = category;
            this.querySelector('#edit_description').value = description;
        });
    }

    // Подтверждение удаления
    function confirmDelete(id, name) {
        document.getElementById('deleteName').textContent = name;
        document.getElementById('deleteLink').href = '/api/delete_quality.php?id=' + id;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>

<?php include_once '../includes/admin_footer.php'; ?> 