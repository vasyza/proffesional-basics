<?php
session_start();
require_once '../api/config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'expert'])) {
    header("Location: /auth/login.php");
    exit;
}

$error = '';
$stats = [];

try {
    $pdo = getDbConnection();
    
    // Общие статистики
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_pvk_assessments");
    $stats['total_assessments'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM user_pvk_assessments");
    $stats['users_with_assessments'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pvk_criteria");
    $stats['total_criteria'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM profession_to_criteria");
    $stats['profession_criteria_links'] = $stmt->fetchColumn();    // Статистика по профессиям
    $stmt = $pdo->query("
        SELECT 
            p.title as profession_name,
            COUNT(DISTINCT upa.user_id) as users_assessed,
            AVG(upa.assessment_score) as avg_assessment_score,
            COUNT(upa.id) as total_pvk_assessments
        FROM user_pvk_assessments upa
        JOIN professions p ON upa.profession_id = p.id
        GROUP BY p.id, p.title
        ORDER BY users_assessed DESC
    ");
    $profession_stats = $stmt->fetchAll();
      // Топ ПВК по уровню развития
    $stmt = $pdo->query("
        SELECT 
            pq.name as pvk_name,
            COUNT(*) as assessment_count,
            AVG(upa.assessment_score) as avg_level,
            MIN(upa.assessment_score) as min_level,
            MAX(upa.assessment_score) as max_level
        FROM user_pvk_assessments upa
        JOIN professional_qualities pq ON upa.pvk_id = pq.id
        GROUP BY pq.id, pq.name
        HAVING COUNT(*) >= 5
        ORDER BY avg_level DESC
        LIMIT 10
    ");
    $top_pvk = $stmt->fetchAll();
      // Динамика оценок по времени (последние 30 дней)
    $stmt = $pdo->query("
        SELECT 
            DATE(last_calculated) as date,
            COUNT(*) as assessments_count,
            AVG(assessment_score) as avg_level
        FROM user_pvk_assessments 
        WHERE last_calculated >= NOW() - INTERVAL '30 days'
        GROUP BY DATE(last_calculated)
        ORDER BY date
    ");
    $daily_stats = $stmt->fetchAll();
      // Статистика физиологических записей
    $physiological_stats = [];
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM physiological_recordings");
        $physiological_stats['total_recordings'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("
            SELECT 
                device_type,
                COUNT(*) as count,
                AVG(EXTRACT(EPOCH FROM (recording_datetime_end - recording_datetime_start))) as avg_duration
            FROM physiological_recordings 
            WHERE recording_datetime_end IS NOT NULL
            GROUP BY device_type
        ");
        $physiological_stats['by_type'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        $physiological_stats['error'] = 'Таблицы физиологических данных не найдены';
    }
    
} catch (PDOException $e) {
    $error = 'Ошибка при загрузке данных: ' . $e->getMessage();
}

$pageTitle = "Анализ и отчеты Lab 7";
include '../includes/admin_header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">
        <i class="fas fa-chart-line"></i> Анализ и отчеты Lab 7
    </h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Общие статистики -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h2 mb-0"><?php echo $stats['total_assessments']; ?></div>
                            <div>Всего оценок ПВК</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h2 mb-0"><?php echo $stats['users_with_assessments']; ?></div>
                            <div>Пользователей с оценками</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h2 mb-0"><?php echo $stats['total_criteria']; ?></div>
                            <div>Критериев оценки</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h2 mb-0"><?php echo $stats['profession_criteria_links']; ?></div>
                            <div>Связей профессия-критерий</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-link fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Статистика по профессиям -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Статистика по профессиям</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($profession_stats)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Профессия</th>
                                        <th>Пользователей</th>
                                        <th>Средний уровень</th>
                                        <th>Оценок ПВК</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($profession_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['profession_name']); ?></td>
                                            <td><?php echo $stat['users_assessed']; ?></td>
                                            <td>                                <span class="badge <?php echo $stat['avg_assessment_score'] >= 7 ? 'bg-success' : ($stat['avg_assessment_score'] >= 5 ? 'bg-warning' : 'bg-secondary'); ?>">
                                                    <?php echo round($stat['avg_assessment_score'], 1); ?>/10
                                                </span>
                                            </td>
                                            <td><?php echo $stat['total_pvk_assessments']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Данные не найдены</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Топ ПВК -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Топ ПВК по развитию</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_pvk)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ПВК</th>
                                        <th>Оценок</th>
                                        <th>Средний уровень</th>
                                        <th>Диапазон</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_pvk as $pvk): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pvk['pvk_name']); ?></td>
                                            <td><?php echo $pvk['assessment_count']; ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo round($pvk['avg_level'] * 100); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo round($pvk['min_level'] * 100); ?>% - <?php echo round($pvk['max_level'] * 100); ?>%
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Недостаточно данных для анализа</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- График динамики -->
    <?php if (!empty($daily_stats)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Динамика оценок (последние 30 дней)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyStatsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Статистика физиологических данных -->
    <?php if (!isset($physiological_stats['error'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Физиологические данные</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h3 text-primary"><?php echo $physiological_stats['total_recordings']; ?></div>
                                    <div class="text-muted">Всего записей</div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <?php if (!empty($physiological_stats['by_type'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Тип записи</th>
                                                    <th>Количество</th>
                                                    <th>Средняя продолжительность</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($physiological_stats['by_type'] as $type_stat): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($type_stat['recording_type']); ?></td>
                                                        <td><?php echo $type_stat['count']; ?></td>
                                                        <td><?php echo round($type_stat['avg_duration']); ?> сек</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="/admin/index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Вернуться в админ-панель
        </a>
        <a href="lab7_criteria_management.php" class="btn btn-primary ms-2">
            <i class="fas fa-cogs"></i> Управление критериями
        </a>
    </div>
</div>

<?php if (!empty($daily_stats)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dailyData = <?php echo json_encode($daily_stats); ?>;
const labels = dailyData.map(item => new Date(item.date).toLocaleDateString('ru-RU'));
const counts = dailyData.map(item => parseInt(item.assessments_count));
const levels = dailyData.map(item => parseFloat(item.avg_level) * 100);

const ctx = document.getElementById('dailyStatsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Количество оценок',
            data: counts,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            yAxisID: 'y'
        }, {
            label: 'Средний уровень развития (%)',
            data: levels,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Дата'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Количество оценок'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Средний уровень (%)'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include '../includes/admin_footer.php'; ?>
