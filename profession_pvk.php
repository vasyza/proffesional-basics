<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Получение ID профессии из URL
$profession_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($profession_id <= 0) {
    header("Location: /professions.php");
    exit;
}

// Подключение стилей для страницы
$extraStyles = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .quality-card {
        margin-bottom: 1rem;
        transition: transform 0.2s;
    }
    .quality-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .importance-indicator {
        display: flex;
        align-items: center;
        margin-top: 0.5rem;
    }
    .importance-bar {
        height: 8px;
        border-radius: 4px;
        flex-grow: 1;
        background: linear-gradient(to right, #ffc107, #28a745, #007bff);
    }
    .importance-value {
        font-weight: bold;
        margin-left: 0.5rem;
        width: 45px;
        text-align: right;
    }
    .expert-indicator {
        font-size: 0.75rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        margin-top: 0.25rem;
    }
    .expert-indicator i {
        margin-right: 0.25rem;
    }
    .rating-heatmap {
        overflow-x: auto;
    }
    .heatmap-cell {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        border-radius: 4px;
    }
    .heatmap-headers th {
        vertical-align: bottom;
        text-align: center;
        padding: 0.5rem;
        min-width: 40px;
    }
    .heatmap-headers th span {
        writing-mode: vertical-lr;
        transform: rotate(180deg);
        max-height: 150px;
        white-space: nowrap;
        display: inline-block;
    }
</style>
';

try {
    $pdo = getDbConnection();
    
    // Получение данных о профессии
    $stmt = $pdo->prepare("SELECT * FROM professions WHERE id = ?");
    $stmt->execute([$profession_id]);
    $profession = $stmt->fetch();
    
    if (!$profession) {
        header("Location: /professions.php");
        exit;
    }
    
    // Получение ПВК для профессии
    $stmt = $pdo->prepare("
        SELECT pq.*, 
               COALESCE(AVG(pqr.rating), 0) as avg_importance,
               COUNT(DISTINCT pqr.expert_id) as experts_count,
               COALESCE(
                   (SELECT MIN(kendall_w) 
                    FROM profession_quality_concordance 
                    WHERE profession_id = ? AND quality_id = pq.id), 
                   0
               ) as concordance,
               COALESCE(
                   (SELECT COUNT(DISTINCT expert_id) 
                    FROM profession_quality_ratings 
                    WHERE profession_id = ? AND quality_id = pq.id), 
                   0
               ) as rated_by
        FROM professional_qualities pq
        LEFT JOIN profession_quality_ratings pqr ON pq.id = pqr.quality_id AND pqr.profession_id = ?
        GROUP BY pq.id
        ORDER BY avg_importance DESC, pq.category, pq.name
    ");
    
    $stmt->execute([$profession_id, $profession_id, $profession_id]);
    $qualities = $stmt->fetchAll();
    
    // Получение количества экспертов, оценивших профессию
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT expert_id) 
        FROM profession_quality_ratings 
        WHERE profession_id = ?
    ");
    $stmt->execute([$profession_id]);
    $expertCount = $stmt->fetchColumn();
    
    // Получение общей согласованности оценок экспертов (коэффициент конкордации Кендалла)
    $stmt = $pdo->prepare("
        SELECT kendall_w, experts_count
        FROM profession_quality_concordance
        WHERE profession_id = ? AND quality_id IS NULL
    ");
    
    $stmt->execute([$profession_id]);
    $concordanceData = $stmt->fetch();
    
    // Если нужно, получаем данные для тепловой карты
    $heatmapData = [];
    if ($expertCount > 0) {
        $stmt = $pdo->prepare("
            SELECT pqr.quality_id, pqr.expert_id, pqr.rating, u.name as expert_name,
                   pq.name as quality_name, pq.category as quality_category
            FROM profession_quality_ratings pqr
            JOIN users u ON pqr.expert_id = u.id
            JOIN professional_qualities pq ON pqr.quality_id = pq.id
            WHERE pqr.profession_id = ?
            ORDER BY pq.category, pq.name, u.name
        ");
        
        $stmt->execute([$profession_id]);
        $ratingData = $stmt->fetchAll();
        
        // Формируем данные для тепловой карты
        foreach ($ratingData as $rating) {
            if (!isset($heatmapData['experts'][$rating['expert_id']])) {
                $heatmapData['experts'][$rating['expert_id']] = [
                    'id' => $rating['expert_id'],
                    'name' => $rating['expert_name']
                ];
            }
            
            if (!isset($heatmapData['qualities'][$rating['quality_id']])) {
                $heatmapData['qualities'][$rating['quality_id']] = [
                    'id' => $rating['quality_id'],
                    'name' => $rating['quality_name'],
                    'category' => $rating['quality_category']
                ];
            }
            
            $heatmapData['ratings'][$rating['quality_id']][$rating['expert_id']] = $rating['rating'];
        }
    }
    
} catch (PDOException $e) {
    die("Ошибка при подключении к базе данных: " . $e->getMessage());
}

// Подключение заголовка
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-3">Профессионально важные качества для профессии</h1>
            <h2 class="text-primary mb-4"><?php echo htmlspecialchars($profession['title']); ?></h2>
            
            <div class="card mb-4">
                <div class="card-body">
                    <p class="lead"><?php echo htmlspecialchars($profession['description']); ?></p>
                    
                    <?php if ($expertCount > 0): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Данные о ПВК основаны на оценках <strong><?php echo $expertCount; ?> экспертов</strong>.
                            <?php if (!empty($concordanceData)): ?>
                                Согласованность экспертных мнений: 
                                <strong><?php echo round($concordanceData['kendall_w'] * 100, 1); ?>%</strong>
                                (коэффициент конкордации Кендалла: <?php echo round($concordanceData['kendall_w'], 3); ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($qualities) || $expertCount == 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Для данной профессии еще не определены профессионально важные качества.
                    <?php if ($userRole === 'expert'): ?>
                        <a href="/expert/rate_profession_qualities.php?profession_id=<?php echo $profession_id; ?>" class="alert-link">
                            Оценить как эксперт
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Основные ПВК профессии -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Основные профессионально важные качества</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $lastCategory = '';
                            foreach ($qualities as $index => $quality): 
                                if ($quality['avg_importance'] < 3.5) continue; // Показываем только важные ПВК
                                
                                // Добавляем заголовок категории, если она изменилась
                                if ($lastCategory != $quality['category']):
                                    $lastCategory = $quality['category'];
                            ?>
                                <div class="col-12 mt-3 mb-2">
                                    <h4 class="border-bottom pb-2"><?php echo htmlspecialchars($quality['category']); ?></h4>
                                </div>
                            <?php endif; ?>
                                <div class="col-md-4">
                                    <div class="card quality-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($quality['name']); ?></h5>
                                            <p class="card-text small"><?php echo htmlspecialchars($quality['description']); ?></p>
                                            <div class="importance-indicator">
                                                <div class="importance-bar" style="background: linear-gradient(to right, #ffffff, 
                                                <?php 
                                                    // Вычисляем цвет в зависимости от важности
                                                    $importance = $quality['avg_importance'];
                                                    if ($importance >= 4.5) echo '#28a745'; // Очень важно
                                                    elseif ($importance >= 3.5) echo '#007bff'; // Важно
                                                    elseif ($importance >= 2.5) echo '#ffc107'; // Средне важно
                                                    else echo '#dc3545'; // Менее важно
                                                ?>); width: <?php echo round($importance / 5 * 100); ?>%"></div>
                                                <span class="importance-value">
                                                    <?php echo number_format($importance, 1); ?>
                                                </span>
                                            </div>
                                            <div class="expert-indicator">
                                                <i class="fas fa-users"></i>
                                                Оценено <?php echo $quality['rated_by']; ?> экспертами
                                                <?php if ($quality['concordance'] > 0): ?>
                                                    • Согласованность: <?php echo round($quality['concordance'] * 100); ?>%
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Диаграмма -->
                <!-- <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Диаграмма значимости ПВК</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="qualitiesChart" height="400"></canvas>
                    </div>
                </div> -->
                
                <!-- Тепловая карта оценок экспертов -->
                <?php if (!empty($heatmapData)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Тепловая карта оценок экспертов</h3>
                    </div>
                    <div class="card-body">
                        <div class="rating-heatmap">
                            <table class="table table-sm table-bordered">
                                <thead class="heatmap-headers">
                                    <tr>
                                        <th>ПВК \ Эксперты</th>
                                        <?php foreach ($heatmapData['experts'] as $expert): ?>
                                            <th><span><?php echo htmlspecialchars($expert['name']); ?></span></th>
                                        <?php endforeach; ?>
                                        <th>Средняя</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $currentCategory = '';
                                    foreach ($heatmapData['qualities'] as $qualityId => $quality): 
                                        // Добавляем строку-заголовок категории
                                        if ($currentCategory != $quality['category']):
                                            $currentCategory = $quality['category'];
                                    ?>
                                        <tr class="table-secondary">
                                            <th colspan="<?php echo count($heatmapData['experts']) + 2; ?>" class="text-center">
                                                <?php echo htmlspecialchars($quality['category']); ?>
                                            </th>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th><?php echo htmlspecialchars($quality['name']); ?></th>
                                        <?php 
                                        $sum = 0;
                                        $count = 0;
                                        foreach ($heatmapData['experts'] as $expertId => $expert): 
                                            $rating = isset($heatmapData['ratings'][$qualityId][$expertId]) 
                                                ? $heatmapData['ratings'][$qualityId][$expertId] 
                                                : null;
                                            if ($rating !== null) {
                                                $sum += $rating;
                                                $count++;
                                            }
                                            // Цвет ячейки в зависимости от оценки
                                            $bgColor = '';
                                            if ($rating !== null) {
                                                if ($rating >= 4.5) $bgColor = '#28a745'; // Очень важно (зеленый)
                                                elseif ($rating >= 3.5) $bgColor = '#007bff'; // Важно (синий)
                                                elseif ($rating >= 2.5) $bgColor = '#ffc107'; // Средне важно (желтый)
                                                else $bgColor = '#dc3545'; // Менее важно (красный)
                                            }
                                        ?>
                                            <td class="text-center">
                                                <?php if ($rating !== null): ?>
                                                    <div class="heatmap-cell" style="background-color: <?php echo $bgColor; ?>">
                                                        <?php echo $rating; ?>
                                                    </div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-center fw-bold">
                                            <?php if ($count > 0): ?>
                                                <?php echo number_format($sum / $count, 1); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($userRole === 'expert'): ?>
                    <div class="d-grid gap-2 col-md-6 mx-auto mt-4">
                        <a href="/expert/rate_profession_qualities.php?profession_id=<?php echo $profession_id; ?>" class="btn btn-primary">
                            <i class="fas fa-star me-2"></i>Оценить как эксперт
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Скрипт для диаграммы ПВК -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($qualities)): ?>
    // Данные для диаграммы
    var ctx = document.getElementById('qualitiesChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($qualities as $quality) {
                    if ($quality['avg_importance'] >= 2) { // Показываем только достаточно важные
                        echo "'" . addslashes($quality['name']) . "', ";
                    }
                }
                ?>
            ],
            datasets: [{
                label: 'Значимость ПВК (от 1 до 5)',
                data: [
                    <?php 
                    foreach ($qualities as $quality) {
                        if ($quality['avg_importance'] >= 2) { // Показываем только достаточно важные
                            echo round($quality['avg_importance'], 1) . ", ";
                        }
                    }
                    ?>
                ],
                backgroundColor: [
                    <?php 
                    foreach ($qualities as $quality) {
                        if ($quality['avg_importance'] >= 2) {
                            $importance = $quality['avg_importance'];
                            if ($importance >= 4.5) echo "'rgba(40, 167, 69, 0.7)', "; // Очень важно
                            elseif ($importance >= 3.5) echo "'rgba(0, 123, 255, 0.7)', "; // Важно
                            elseif ($importance >= 2.5) echo "'rgba(255, 193, 7, 0.7)', "; // Средне важно
                            else echo "'rgba(220, 53, 69, 0.7)', "; // Менее важно
                        }
                    }
                    ?>
                ],
                borderColor: [
                    <?php 
                    foreach ($qualities as $quality) {
                        if ($quality['avg_importance'] >= 2) {
                            $importance = $quality['avg_importance'];
                            if ($importance >= 4.5) echo "'rgba(40, 167, 69, 1)', "; // Очень важно
                            elseif ($importance >= 3.5) echo "'rgba(0, 123, 255, 1)', "; // Важно
                            elseif ($importance >= 2.5) echo "'rgba(255, 193, 7, 1)', "; // Средне важно
                            else echo "'rgba(220, 53, 69, 1)', "; // Менее важно
                        }
                    }
                    ?>
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 5
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
// Подключение подвала
include 'includes/footer.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ПВК для профессии <?php echo htmlspecialchars($profession['title']); ?> - Портал ИТ-профессий</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container py-5">
        <!-- Содержимое страницы -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="mb-3">Профессионально важные качества для профессии</h1>
                <h2 class="text-primary mb-4"><?php echo htmlspecialchars($profession['title']); ?></h2>
                
                <!-- Остальной HTML-код страницы -->
            </div>
        </div>
    </div>
</body>
</html> 