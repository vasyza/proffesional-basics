<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли эксперта
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'expert') {
    header("Location: /auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Подключение к базе данных
try {
    $pdo = getDbConnection();

    // Получаем данные о профессиях, которые можно оценить
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COALESCE(er.rating, 0) as user_rating,
               er.comment as user_comment,
               CASE WHEN er.id IS NOT NULL THEN 1 ELSE 0 END as is_rated
        FROM professions p
        LEFT JOIN expert_ratings er ON p.id = er.profession_id AND er.expert_id = ?
        ORDER BY p.title ASC
    ");
    $stmt->execute([$userId]);
    $professions = $stmt->fetchAll();

    // Получаем данные о ПВК, оцененных экспертом
    $stmt = $pdo->prepare("
        SELECT 
            p.id as profession_id,
            p.title as profession_title
        FROM 
            professions p
        JOIN 
            profession_quality_ratings pqr ON p.id = pqr.profession_id
        WHERE 
            pqr.expert_id = ?
        GROUP BY 
            p.id, p.title
    ");
    $stmt->execute([$userId]);
    $ratedPvkProfessions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Получаем статистику оценок эксперта
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT er.profession_id) as rated_professions,
            COUNT(DISTINCT pqr.profession_id) as pvk_rated_professions,
            COUNT(pqr.id) as total_pvk_ratings
        FROM 
            users u
        LEFT JOIN 
            expert_ratings er ON u.id = er.expert_id
        LEFT JOIN 
            profession_quality_ratings pqr ON u.id = pqr.expert_id
        WHERE 
            u.id = ?
    ");
    $stmt->execute([$userId]);
    $expertStats = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}
$recent_ratings = [];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель эксперта - Портал ИТ-профессий</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .rating-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .rating-stars {
            font-size: 24px;
            color: #ffc107;
            cursor: pointer;
        }

        .rating-stars .star {
            margin-right: 5px;
        }

        .expert-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 20px;
            margin-bottom: 20px;
        }

        .expert-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
        }

        .profession-list {
            margin-top: 30px;
        }

        .profession-card {
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s;
        }

        .profession-card:hover {
            transform: translateY(-5px);
        }

        .rated-profession {
            border-left: 4px solid #198754;
        }

        .rating-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
        }

        .task-card {
            border-left: 4px solid #007bff;
        }

        .task-card.completed {
            border-left-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/">Портал ИТ-профессий</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/#about">О портале</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/professions.php">Каталог
                        профессий</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/experts.php">Наши эксперты</a>
                </li>
            </ul>
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       id="navbarDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Личный кабинет
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item"
                               href="/cabinet.php">Профиль</a></li>
                        <li><a class="dropdown-item active"
                               href="/expert/index.php">Панель эксперта</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="/auth/logout.php">Выход</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Панель эксперта</h1>

    <div class="row">
        <div class="col-md-4">
            <!-- Карточка эксперта -->
            <div class="expert-card">
                <h2>Здравствуйте, <?php echo htmlspecialchars($userName); ?>
                    !</h2>

                <div class="d-grid gap-2 mt-3">
                    <a href="/expert/pvk_list.php"
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-list me-1"></i> Просмотреть ПВК
                    </a>
                </div>

                <!-- Тумблер -->
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox"
                           id="expertModeSwitch">
                    <label class="form-check-label" for="expertModeSwitch">Публичный
                        профиль</label>
                    <div class="form-text">Если включено, ваши данные будут
                        видны пользователям
                    </div>
                </div>

                <!-- <div class="expert-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo intval($expert_data['ratings_count'] ?? 0); ?></div>
                            <div class="stat-label">Оценок</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($expert_data['avg_rating'] ?? 0, 1); ?></div>
                            <div class="stat-label">Средняя оценка</div>
                        </div>
                    </div> -->
            </div>

            <!-- Последние оценки -->
            <!-- <div class="expert-card">
                    <h3>Ваши последние оценки</h3>
                    <?php if (count($recent_ratings) > 0): ?>
                        <ul class="list-group">
                            <?php foreach ($recent_ratings as $rating): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="/professions.php?id=<?php echo $rating['profession_id']; ?>">
                                            <?php echo htmlspecialchars($rating['profession_title']); ?>
                                        </a>
                                        <small class="d-block text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($rating['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $rating['rating']; ?>/5</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">У вас пока нет оценок.</p>
                    <?php endif; ?>
                </div> -->
        </div>

        <div class="col-md-8">
            <h2 class="mb-4">Профессии для оценки</h2>

            <!-- Список профессий -->
            <div class="profession-list">
                <?php foreach ($professions as $profession): ?>
                    <div class="card profession-card <?php echo $profession['is_rated'] ? 'rated-profession' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="card-title h5 mb-0">
                                    <a href="/professions.php?id=<?php echo $profession['id']; ?>">
                                        <?php echo htmlspecialchars($profession['title']); ?>
                                    </a>
                                </h3>
                                <?php if ($profession['is_rated']): ?>
                                    <div class="rating-badge">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $profession['user_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <p class="card-text text-truncate"><?php echo htmlspecialchars($profession['description']); ?></p>

                            <div class="d-flex justify-content-between mt-auto">
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($profession['type']); ?>
                                    </span>

                                <?php if (isset($ratedPvkProfessions[$profession['id']])): ?>
                                    <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            ПВК: <?php echo $ratedPvkProfessions[$profession['id']]; ?>
                                        </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">
                                            <i class="fas fa-times-circle me-1"></i>
                                            ПВК не оценены
                                        </span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3 d-grid gap-2">
                                <!-- <?php if (!$profession['is_rated']): ?>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rateModal<?php echo $profession['id']; ?>">
                                            <i class="fas fa-star me-1"></i>Оценить профессию
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rateModal<?php echo $profession['id']; ?>">
                                            <i class="fas fa-edit me-1"></i>Изменить оценку
                                        </button>
                                    <?php endif; ?> -->

                                <a href="/expert/rate_profession_qualities.php?profession_id=<?php echo $profession['id']; ?>"
                                   class="btn btn-outline-success">
                                    <i class="fas fa-clipboard-list me-1"></i>Оценить
                                    ПВК
                                </a>

                                <a href="/profession.php?id=<?php echo $profession['id']; ?>"
                                   class="btn btn-link">
                                    <i class="fas fa-external-link-alt me-1"></i>Просмотр
                                    профессии
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>Портал ИТ-профессий</h5>
                <p>Информационная платформа о профессиях в ИТ-сфере,
                    способствующая профессиональной ориентации, выбору
                    карьерного пути и профессиональному развитию.</p>
            </div>
            <div class="col-md-3">
                <h5>Навигация</h5>
                <ul class="list-unstyled">
                    <li><a href="/" class="text-white">Главная</a></li>
                    <li><a href="/professions.php" class="text-white">Каталог
                            профессий</a></li>
                    <li><a href="/groups.php" class="text-white">Рабочие
                            группы</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Контакты</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-envelope me-2"></i>
                        info@it-professions.ru
                    </li>
                    <li><i class="fas fa-phone me-2"></i> +7 (999) 123-45-67
                    </li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p class="mb-0">© 2025 Портал ИТ-профессий. Все права защищены.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Обработка выбора оценки звездами
    document.addEventListener('DOMContentLoaded', function () {
        // Для каждой профессии настраиваем рейтинг звездами
        <?php foreach ($professions as $profession): ?>
        const stars<?php echo $profession['id']; ?> = document.querySelectorAll('#ratingForm<?php echo $profession['id']; ?> .star');
        const ratingInput<?php echo $profession['id']; ?> = document.getElementById('rating<?php echo $profession['id']; ?>');

        stars<?php echo $profession['id']; ?>.forEach(star => {
            star.addEventListener('click', () => {
                const value = parseInt(star.getAttribute('data-value'));
                ratingInput<?php echo $profession['id']; ?>.value = value;

                // Обновляем отображение звезд
                stars<?php echo $profession['id']; ?>.forEach(s => {
                    const starValue = parseInt(s.getAttribute('data-value'));
                    const starIcon = s.querySelector('i');

                    if (starValue <= value) {
                        starIcon.classList.remove('text-muted');
                    } else {
                        starIcon.classList.add('text-muted');
                    }
                });
            });
        });
        <?php endforeach; ?>
    });
</script>

// Тумблер
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const expertModeSwitch = document.getElementById('expertModeSwitch');

        // Загрузка текущего состояния при старте страницы
        fetch('../api/getPublicStat.php')
            .then(response => response.json())
            .then(data => {
                expertModeSwitch.checked = data.isPublic;
            })
            .catch(error => console.error('Error:', error));

        // При изменении тумблера
        expertModeSwitch.addEventListener('change', function () {
            const isPublic = this.checked ? 1 : 0;

            fetch('../api/updatePublicStat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({isPublic: isPublic})
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Ошибка при обновлении статуса');
                        expertModeSwitch.checked = !isPublic; // Возвращаем предыдущее состояние
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    expertModeSwitch.checked = !isPublic; // Возвращаем предыдущее состояние
                });
        });
    });
</script>
</body>

</html>
