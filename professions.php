<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Получение категории из URL
$category = isset($_GET['category']) ? $_GET['category'] : '';

try {
    $pdo = getDbConnection();
    
    // Получение категорий для фильтрации
    $stmt = $pdo->query("SELECT DISTINCT type FROM professions ORDER BY type");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Формирование SQL-запроса с фильтрацией
    $sql = "SELECT p.*, 
                  COUNT(DISTINCT er.id) as ratings_count,
                  COALESCE(AVG(er.rating), 0) as avg_rating
           FROM professions p
           LEFT JOIN expert_ratings er ON p.id = er.profession_id";
    
    // Применение фильтров
    $whereConditions = [];
    
    if (!empty($category)) {
        $whereConditions[] = "p.type = :category";
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.title";
    
    $stmt = $pdo->prepare($sql);
    
    if (!empty($category)) {
        $stmt->bindParam(':category', $category);
    }
    
    $stmt->execute();
    $professions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог ИТ-профессий - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .profession-card {
            height: 100%;
            transition: transform 0.3s;
        }
        .profession-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-footer {
            background-color: transparent;
            border-top: none;
        }
    </style>
</head>
<body>
    <!-- Верхнее меню -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <h1 class="mb-4">Каталог ИТ-профессий</h1>
        
        <!-- Фильтр по категориям -->
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <h2 class="h5 mb-0 me-3">Фильтр по категориям:</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/professions.php" class="btn btn-sm <?php echo empty($category) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Все категории
                    </a>
                    <?php foreach($categories as $cat): ?>
                        <a href="/professions.php?category=<?php echo urlencode($cat); ?>" 
                           class="btn btn-sm <?php echo $category === $cat ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (!empty($category)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-filter me-2"></i>
                    Показаны профессии из категории: <strong><?php echo htmlspecialchars($category); ?></strong>
                    <a href="/professions.php" class="ms-2 alert-link">Сбросить фильтр</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Список профессий -->
        <?php if (empty($professions)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Профессии не найдены. Пожалуйста, выберите другую категорию или сбросьте фильтр.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($professions as $profession): ?>
                    <div class="col">
                        <div class="card profession-card">
                            <div class="card-body">
                                <h2 class="card-title h5 mb-2">
                                    <a href="profession.php?id=<?php echo $profession['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($profession['title']); ?>
                                    </a>
                                </h2>
                                
                                <p class="card-text text-muted small mb-3">
                                    <?php echo htmlspecialchars(mb_substr($profession['description'], 0, 150)); ?>...
                                </p>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($profession['type']); ?>
                                    </span>
                                    
                                    <?php if (!empty($profession['salary_range'])): ?>
                                        <span class="badge bg-info text-dark">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            <?php echo htmlspecialchars($profession['salary_range']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($profession['ratings_count'] > 0): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-star me-1"></i>
                                            Рейтинг: <?php echo number_format($profession['avg_rating'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="profession.php?id=<?php echo $profession['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-info-circle me-1"></i>Подробнее
                                    </a>
                                    
                                    <?php if ($profession['ratings_count'] > 0): ?>
                                        <a href="profession_ratings.php?profession_id=<?php echo $profession['id']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-star me-1"></i>Оценить
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($userRole === 'expert'): ?>
                                        <a href="/expert/rate_profession_qualities.php?profession_id=<?php echo $profession['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-star me-1"></i>Оценить
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Нижний колонтитул -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 