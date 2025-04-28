<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Получение ID профессии из URL
$professionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($professionId <= 0) {
    header('Location: /professions.php');
    exit;
}

try {
    $pdo = getDbConnection();

    //Получение данных о профессии
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(DISTINCT er.id) as ratings_count,
               COALESCE(AVG(er.rating), 0) as avg_rating
        FROM professions p
        LEFT JOIN expert_ratings er ON p.id = er.profession_id
        WHERE p.id = :id
        GROUP BY p.id
    ");
    $stmt->bindParam(':id', $professionId);
    $stmt->execute();

    $profession = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profession) {
        header('Location: /professions.php');
        exit;
    }

    // Получение профессионально важных качеств для данной профессии
    $stmt = $pdo->prepare("
    SELECT pq.name, cpqr.average_rating
    FROM combined_profession_quality_ratings cpqr
    JOIN professional_qualities pq ON cpqr.quality_id = pq.id
    WHERE cpqr.profession_id = ?
    ORDER BY cpqr.average_rating DESC
");
    $stmt->execute([$professionId]);
    $professionQualities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение отзывов экспертов
    // $stmt = $pdo->prepare("
    //     SELECT er.*, u.name as expert_name
    //     FROM expert_ratings er
    //     JOIN users u ON er.expert_id = u.id
    //     WHERE er.profession_id = :profession_id
    //     ORDER BY er.created_at DESC
    // ");
    // $stmt->bindParam(':profession_id', $professionId);
    // $stmt->execute();
    // $expertRatings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profession['title']); ?> - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <!-- Верхнее меню -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Главная</a></li>
                <li class="breadcrumb-item"><a href="/professions.php">Профессии</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($profession['title']); ?>
                </li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <h1 class="mb-3"><?php echo htmlspecialchars($profession['title']); ?></h1>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <span
                        class="badge bg-secondary"><?php echo htmlspecialchars($profession['type'] ?? 'ИТ-специалист'); ?></span>

                    <?php if (!empty($profession['salary_range'])): ?>
                        <span class="badge bg-info text-dark">
                            <i class="fas fa-money-bill-wave me-1"></i>
                            <?php echo htmlspecialchars('Зарплата ' . $profession['salary_range']); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($profession['demand_level']): ?>
                        <div class="d-flex align-items-center">
                            <span class="me-2">Востребованность:</span>
                            <div class="text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa<?php echo $i <= $profession['demand_level'] ? 's' : 'r'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($profession['ratings_count'] > 0): ?>
                        <div class="d-flex align-items-center">
                            <span class="me-2">Рейтинг экспертов:</span>
                            <div class="text-warning">
                                <?php
                                $avg = round($profession['avg_rating'] * 2) / 2;
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= $avg) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 == $avg) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                endfor;
                                ?>
                                <span class="ms-1">(<?php echo number_format($profession['avg_rating'], 1); ?>)</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Описание профессии</h2>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($profession['description'])); ?></p>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($professionQualities)): ?>
                        <hr>
                        <h3 class="h6 mt-4">Профессионально важные качества:</h3>
                        <ul class="list-unstyled">
                            <?php foreach ($professionQualities as $quality): ?>
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars($quality['name']); ?>
                                    <small class="text-muted ms-2">(средняя оценка: <?php echo number_format($quality['average_rating'], 1); ?>/10)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mt-4">Нет выбранных качеств для этой профессии.</p>
                    <?php endif; ?>
                </div>

                <!-- <?php if (count($expertRatings) > 0): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-0">Оценки экспертов (<?php echo count($expertRatings); ?>)</h2>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($expertRatings as $rating): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($rating['expert_name']); ?></strong>
                                                <span class="text-muted ms-2">
                                                    <?php echo date('d.m.Y', strtotime($rating['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fa<?php echo $i <= $rating['rating'] ? 's' : 'r'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($rating['comment'])): ?>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($rating['comment'])); ?></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?> -->
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($isLoggedIn): ?>
                            <?php if ($userRole === 'expert'): ?>
                                <a href="/expert/rate_profession_qualities.php?profession_id=<?php echo $profession['id']; ?>"
                                    class="btn btn-primary d-block mb-3">
                                    <i class="fas fa-star me-2"></i>Оценить профессию
                                </a>
                            <?php endif; ?>

                            <?php if ($userRole === 'admin'): ?>
                                <a href="/admin/edit_profession.php?id=<?php echo $profession['id']; ?>"
                                    class="btn btn-warning d-block mb-3">
                                    <i class="fas fa-edit me-2"></i>Редактировать профессию
                                </a>
                            <?php endif; ?>

                            <a href="/professions.php" class="btn btn-outline-primary d-block">
                                <i class="fas fa-arrow-left me-2"></i>Вернуться к списку
                            </a>
                        <?php else: ?>
                            <p class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Для оценки и взаимодействия с профессиями, пожалуйста,
                                <a href="/auth/login.php">войдите в систему</a>.
                            </p>

                            <a href="/professions.php" class="btn btn-outline-primary d-block">
                                <i class="fas fa-arrow-left me-2"></i>Вернуться к списку
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- <?php if (!empty($profession['image_path'])): ?>
                    <div class="card mb-4">
                        <div class="card-body p-0">
                            <img src="<?php echo htmlspecialchars($profession['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($profession['title']); ?>" class="img-fluid rounded">
                        </div>
                    </div>
                <?php endif; ?> -->
            </div>
        </div>
    </div>

    <!-- Нижний колонтитул -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>