<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли эксперта
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'expert') {
    header("Location: /auth/login.php");
    exit;
}

// Получение ID профессии из URL
$professionId = isset($_GET['profession_id']) ? intval($_GET['profession_id']) : 0;

if ($professionId <= 0) {
    header('Location: /expert/index.php?error=' . urlencode('Неверный ID профессии'));
    exit;
}

$userId = $_SESSION['user_id'];

// Обработка сообщений
$errorMsg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$successMsg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

try {
    $pdo = getDbConnection();
    
    // Получение информации о профессии
    $stmt = $pdo->prepare("SELECT * FROM professions WHERE id = ?");
    $stmt->execute([$professionId]);
    $profession = $stmt->fetch();
    
    if (!$profession) {
        header('Location: /expert/index.php?error=' . urlencode('Профессия не найдена'));
        exit;
    }
    
    // Получение всех ПВК, сгруппированных по категориям
    $stmt = $pdo->query("
        SELECT * FROM professional_qualities 
        ORDER BY category, name
    ");
    $allQualities = $stmt->fetchAll();
    
    // Группировка качеств по категориям
    $qualitiesByCategory = [];
    foreach ($allQualities as $quality) {
        $category = !empty($quality['category']) ? $quality['category'] : 'Без категории';
        if (!isset($qualitiesByCategory[$category])) {
            $qualitiesByCategory[$category] = [];
        }
        $qualitiesByCategory[$category][] = $quality;
    }
    
    // Получение уже выбранных и оцененных качеств данным экспертом для данной профессии
    $stmt = $pdo->prepare("
        SELECT pqr.*, pq.name as quality_name
        FROM profession_quality_ratings pqr
        JOIN professional_qualities pq ON pqr.quality_id = pq.id
        WHERE pqr.profession_id = ? AND pqr.expert_id = ?
    ");
    $stmt->execute([$professionId, $userId]);
    $expertRatings = $stmt->fetchAll();
    
    // Создание ассоциативного массива для быстрого доступа к оценкам
    $ratingsByQualityId = [];
    foreach ($expertRatings as $rating) {
        $ratingsByQualityId[$rating['quality_id']] = $rating;
    }
    
} catch (PDOException $e) {
    error_log("Ошибка при получении данных о профессии и ПВК: " . $e->getMessage());
    header('Location: /expert/index.php?error=' . urlencode('Ошибка при получении данных: ' . $e->getMessage()));
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оценка ПВК для профессии - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .quality-item {
            position: relative;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            transition: all 0.3s;
        }
        .quality-item:hover {
            background-color: #f8f9fa;
        }
        .quality-item.selected {
            background-color: #e6f7ff;
            border-color: #79b8ff;
        }
        .rating-controls {
            display: none;
        }
        .quality-item.selected .rating-controls {
            display: block;
            margin-top: 0.5rem;
        }
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            margin-right: 1rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            font-size: 1.25rem;
            padding: 0 0.1rem;
            cursor: pointer;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffd700;
        }
        .selected-qualities {
            min-height: 5rem;
            border: 1px dashed #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .selected-quality-tag {
            display: inline-block;
            background-color: #e6f7ff;
            border: 1px solid #79b8ff;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem;
            font-size: 0.875rem;
        }
        .selected-quality-tag .remove-quality {
            margin-left: 0.5rem;
            cursor: pointer;
            color: #dc3545;
        }
        .quality-count {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background-color: #6c757d;
            color: white;
            border-radius: 50%;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/expert/index.php">Панель эксперта</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Выход
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Оценка ПВК для профессии "<?php echo htmlspecialchars($profession['title']); ?>"</h1>
            <a href="/expert/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Вернуться к профессиям
            </a>
        </div>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0">Описание профессии</h2>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($profession['description'])); ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Выбор и оценка ПВК</h2>
                        <span class="badge bg-warning text-dark" id="selectedCount">
                            Выбрано: <span id="countValue">0</span>/10
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="card-text mb-4">
                            Выберите из списка и оцените важность профессионально значимых качеств для профессии "<?php echo htmlspecialchars($profession['title']); ?>".
                            Рекомендуется выбрать от 5 до 10 наиболее важных качеств.
                        </p>
                        
                        <!-- Выбранные качества -->
                        <form id="ratingsForm" action="/api/rate_profession_qualities.php" method="post">
                            <input type="hidden" name="profession_id" value="<?php echo $professionId; ?>">
                            
                            <h3 class="h6 mb-2">Выбранные качества:</h3>
                            <div class="selected-qualities mb-3" id="selectedQualities">
                                <?php if (count($expertRatings) === 0): ?>
                                    <p class="text-muted small" id="noQualitiesMsg">Выберите качества из списка ниже</p>
                                <?php else: ?>
                                    <?php foreach ($expertRatings as $rating): ?>
                                        <div class="selected-quality-tag" data-id="<?php echo $rating['quality_id']; ?>">
                                            <?php echo htmlspecialchars($rating['quality_name']); ?> 
                                            (<?php echo $rating['rating']; ?>/10)
                                            <span class="remove-quality" onclick="removeQuality(<?php echo $rating['quality_id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </span>
                                            <input type="hidden" name="qualities[<?php echo $rating['quality_id']; ?>][rating]" 
                                                   value="<?php echo $rating['rating']; ?>">
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success mb-4" id="saveButton">
                                    <i class="fas fa-save me-2"></i>Сохранить оценки
                                </button>
                            </div>
                        </form>
                        
                        <!-- Список категорий ПВК -->
                        <ul class="nav nav-tabs" id="qualitiesTabs" role="tablist">
                            <?php $firstCategory = true; ?>
                            <?php foreach ($qualitiesByCategory as $category => $qualities): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $firstCategory ? 'active' : ''; ?>" 
                                            id="<?php echo 'tab-' . md5($category); ?>"
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo 'content-' . md5($category); ?>" 
                                            type="button" role="tab" aria-selected="<?php echo $firstCategory ? 'true' : 'false'; ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </button>
                                </li>
                                <?php $firstCategory = false; ?>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Содержимое вкладок с качествами -->
                        <div class="tab-content p-3 border border-top-0 rounded-bottom" id="qualitiesTabContent">
                            <?php $firstCategory = true; ?>
                            <?php foreach ($qualitiesByCategory as $category => $qualities): ?>
                                <div class="tab-pane fade <?php echo $firstCategory ? 'show active' : ''; ?>" 
                                     id="<?php echo 'content-' . md5($category); ?>" role="tabpanel" 
                                     aria-labelledby="<?php echo 'tab-' . md5($category); ?>">
                                    
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control category-search" 
                                               placeholder="Поиск по качествам в этой категории" 
                                               data-category="<?php echo htmlspecialchars($category); ?>">
                                    </div>
                                    
                                    <div class="qualities-list">
                                        <?php foreach ($qualities as $quality): ?>
                                            <?php 
                                                $isSelected = isset($ratingsByQualityId[$quality['id']]);
                                                $rating = $isSelected ? $ratingsByQualityId[$quality['id']]['rating'] : 0;
                                            ?>
                                            <div class="quality-item <?php echo $isSelected ? 'selected' : ''; ?>" 
                                                 id="quality-<?php echo $quality['id']; ?>" 
                                                 data-id="<?php echo $quality['id']; ?>"
                                                 data-name="<?php echo htmlspecialchars($quality['name']); ?>">
                                                
                                                <div class="d-flex align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input quality-checkbox" type="checkbox" 
                                                               id="check-<?php echo $quality['id']; ?>"
                                                               data-id="<?php echo $quality['id']; ?>"
                                                               <?php echo $isSelected ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="check-<?php echo $quality['id']; ?>">
                                                            <strong><?php echo htmlspecialchars($quality['name']); ?></strong>
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($quality['description'])): ?>
                                                    <p class="mb-1 small text-muted">
                                                        <?php echo htmlspecialchars($quality['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="rating-controls">
                                                    <div class="d-flex align-items-center">
                                                        <label class="form-label me-2 mb-0">Важность:</label>
                                                        <input type="range" class="form-range quality-rating" 
                                                               min="1" max="10" step="1" 
                                                               data-id="<?php echo $quality['id']; ?>"
                                                               value="<?php echo $rating ?: 5; ?>" style="width: 150px;">
                                                        <span class="ms-2 rating-value">
                                                            <?php echo $rating ?: 5; ?>/10
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php $firstCategory = false; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 80px">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0">Рекомендации по оценке</h2>
                    </div>
                    <div class="card-body">
                        <h3 class="h6">Как выбирать и оценивать ПВК:</h3>
                        <ul class="small">
                            <li>Выберите от 5 до 10 наиболее значимых качеств для этой профессии.</li>
                            <li>Для каждого выбранного качества укажите степень важности от 1 до 10, где:
                                <ul>
                                    <li>1-3 — малозначимое, но желательное качество</li>
                                    <li>4-6 — значимое качество</li>
                                    <li>7-8 — важное качество</li>
                                    <li>9-10 — критически важное, определяющее качество</li>
                                </ul>
                            </li>
                            <li>Учитывайте специфику профессии и современные требования к ней.</li>
                            <li>Не выбирайте слишком много качеств — это размывает профиль профессии.</li>
                            <li>Оценивайте объективно, основываясь на своем опыте и знаниях о профессии.</li>
                        </ul>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Внимание!</strong> После оценки вы сможете увидеть согласованность ваших оценок с другими экспертами в общей статистике.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qualityCheckboxes = document.querySelectorAll('.quality-checkbox');
            const ratingsForm = document.getElementById('ratingsForm');
            const selectedQualities = document.getElementById('selectedQualities');
            const noQualitiesMsg = document.getElementById('noQualitiesMsg');
            const countValue = document.getElementById('countValue');
            const saveButton = document.getElementById('saveButton');
            const MAX_QUALITIES = 10;
            
            // Инициализация счетчика выбранных качеств
            updateSelectedCount();
            
            // Обработчики событий для чекбоксов качеств
            qualityCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const qualityId = this.dataset.id;
                    const qualityItem = document.getElementById('quality-' + qualityId);
                    
                    if (this.checked) {
                        // Проверка на превышение лимита
                        const selectedCount = document.querySelectorAll('.selected-quality-tag').length;
                        if (selectedCount >= MAX_QUALITIES) {
                            this.checked = false;
                            alert('Вы можете выбрать максимум 10 качеств. Пожалуйста, удалите одно из ранее выбранных, чтобы добавить новое.');
                            return;
                        }
                        
                        // Добавление качества в выбранные
                        qualityItem.classList.add('selected');
                        addQualityToSelected(qualityId, qualityItem.dataset.name);
                        
                        // Инициализация слайдера рейтинга
                        const ratingInput = qualityItem.querySelector('.quality-rating');
                        updateRatingValue(ratingInput);
                        ratingInput.addEventListener('input', function() {
                            updateRatingValue(this);
                            updateSelectedQualityTag(this.dataset.id, this.value);
                        });
                    } else {
                        // Удаление качества из выбранных
                        qualityItem.classList.remove('selected');
                        removeQuality(qualityId);
                    }
                    
                    updateSelectedCount();
                });
            });
            
            // Поиск по качествам в категории
            document.querySelectorAll('.category-search').forEach(input => {
                input.addEventListener('input', function() {
                    const searchText = this.value.toLowerCase();
                    const category = this.dataset.category;
                    const categoryId = 'content-' + md5(category);
                    const qualityItems = document.querySelectorAll('#' + categoryId + ' .quality-item');
                    
                    qualityItems.forEach(item => {
                        const qualityName = item.dataset.name.toLowerCase();
                        const qualityDesc = item.querySelector('p') ? item.querySelector('p').textContent.toLowerCase() : '';
                        
                        if (qualityName.includes(searchText) || qualityDesc.includes(searchText)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
            
            // Проверка формы перед отправкой
            ratingsForm.addEventListener('submit', function(event) {
                const selectedCount = document.querySelectorAll('.selected-quality-tag').length;
                
                if (selectedCount < 5) {
                    event.preventDefault();
                    alert('Пожалуйста, выберите как минимум 5 качеств для оценки профессии.');
                }
            });
            
            // Функция добавления качества в блок выбранных
            function addQualityToSelected(qualityId, qualityName) {
                // Удаление сообщения "нет выбранных качеств"
                if (noQualitiesMsg) {
                    noQualitiesMsg.style.display = 'none';
                }
                
                // Получение значения рейтинга
                const ratingInput = document.querySelector('.quality-rating[data-id="' + qualityId + '"]');
                const ratingValue = ratingInput ? ratingInput.value : 5;
                
                // Создание тега выбранного качества
                const qualityTag = document.createElement('div');
                qualityTag.className = 'selected-quality-tag';
                qualityTag.dataset.id = qualityId;
                qualityTag.innerHTML = `
                    ${qualityName} (${ratingValue}/10)
                    <span class="remove-quality" onclick="removeQuality(${qualityId})">
                        <i class="fas fa-times"></i>
                    </span>
                    <input type="hidden" name="qualities[${qualityId}][rating]" value="${ratingValue}">
                `;
                
                selectedQualities.appendChild(qualityTag);
            }
            
            // Функция обновления значения рейтинга
            function updateRatingValue(input) {
                const value = input.value;
                const valueDisplay = input.parentNode.querySelector('.rating-value');
                if (valueDisplay) {
                    valueDisplay.textContent = value + '/10';
                }
            }
            
            // Функция обновления тега выбранного качества
            function updateSelectedQualityTag(qualityId, ratingValue) {
                const qualityTag = selectedQualities.querySelector(`.selected-quality-tag[data-id="${qualityId}"]`);
                if (qualityTag) {
                    const qualityName = document.querySelector(`#quality-${qualityId}`).dataset.name;
                    qualityTag.childNodes[0].nodeValue = `${qualityName} (${ratingValue}/10)`;
                    qualityTag.querySelector('input').value = ratingValue;
                }
            }
            
            // Функция обновления счетчика выбранных качеств
            function updateSelectedCount() {
                const selectedCount = document.querySelectorAll('.selected-quality-tag').length;
                countValue.textContent = selectedCount;
                
                // Обновление стиля счетчика в зависимости от количества
                const selectedCountBadge = document.getElementById('selectedCount');
                if (selectedCount < 5) {
                    selectedCountBadge.className = 'badge bg-danger';
                } else if (selectedCount <= MAX_QUALITIES) {
                    selectedCountBadge.className = 'badge bg-success';
                } else {
                    selectedCountBadge.className = 'badge bg-warning text-dark';
                }
                
                // Активация/деактивация кнопки сохранения
                saveButton.disabled = selectedCount < 5;
            }
            
            // Хеширование строки (для генерации ID вкладок)
            function md5(string) {
                // Простая замена для демонстрации
                // В реальном приложении использовать настоящую MD5 функцию
                return string.replace(/[^a-z0-9]/gi, '').toLowerCase();
            }
        });
        
        // Глобальная функция удаления качества
        function removeQuality(qualityId) {
            // Снятие чекбокса
            const checkbox = document.querySelector(`.quality-checkbox[data-id="${qualityId}"]`);
            if (checkbox) {
                checkbox.checked = false;
                const qualityItem = document.getElementById('quality-' + qualityId);
                if (qualityItem) {
                    qualityItem.classList.remove('selected');
                }
            }
            
            // Удаление тега
            const qualityTag = document.querySelector(`.selected-quality-tag[data-id="${qualityId}"]`);
            if (qualityTag) {
                qualityTag.remove();
            }
            
            // Обновление счетчика
            const countValue = document.getElementById('countValue');
            const selectedCount = document.querySelectorAll('.selected-quality-tag').length;
            countValue.textContent = selectedCount;
            
            // Показ сообщения если нет выбранных качеств
            if (selectedCount === 0) {
                const noQualitiesMsg = document.getElementById('noQualitiesMsg');
                if (noQualitiesMsg) {
                    noQualitiesMsg.style.display = 'block';
                } else {
                    const newMsg = document.createElement('p');
                    newMsg.id = 'noQualitiesMsg';
                    newMsg.className = 'text-muted small';
                    newMsg.textContent = 'Выберите качества из списка ниже';
                    document.getElementById('selectedQualities').appendChild(newMsg);
                }
            }
            
            // Обновление стиля счетчика
            const selectedCountBadge = document.getElementById('selectedCount');
            if (selectedCount < 5) {
                selectedCountBadge.className = 'badge bg-danger';
            } else if (selectedCount <= 10) {
                selectedCountBadge.className = 'badge bg-success';
            } else {
                selectedCountBadge.className = 'badge bg-warning text-dark';
            }
            
            // Активация/деактивация кнопки сохранения
            document.getElementById('saveButton').disabled = selectedCount < 5;
        }
    </script>
</body>
</html> 