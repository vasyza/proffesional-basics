<?php
session_start();
require_once 'api/config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Получение записей пользователя
try {
    $pdo = getDbConnection();
      // Получение физиологических записей пользователя
    $stmt = $pdo->prepare("
        SELECT 
            pr.*,
            ts.test_type,
            ts.created_at as test_date,
            COUNT(pdp.id) as data_points_count
        FROM physiological_recordings pr
        LEFT JOIN test_sessions ts ON pr.test_session_id = ts.id
        LEFT JOIN physiological_data_points pdp ON pr.id = pdp.recording_id
        WHERE pr.user_id = ?
        GROUP BY pr.id, ts.test_type, ts.created_at
        ORDER BY pr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $recordings = $stmt->fetchAll();
    
    // Получение последних тестовых сессий для связывания
    $stmt = $pdo->prepare("
        SELECT id, test_type, created_at, average_time, accuracy
        FROM test_sessions 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $recent_tests = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Ошибка при загрузке данных: ' . $e->getMessage();
    $recordings = [];
    $recent_tests = [];
}

// Массив для человеко-читаемых названий тестов
$typeLabels = [
    'light_reaction' => 'Реакция на свет',
    'sound_reaction' => 'Реакция на звук',
    'color_reaction' => 'Реакция на разные цвета',
    'sound_arithmetic' => 'Звуковой сигнал и арифметика',
    'visual_arithmetic' => 'Визуальная арифметика',
    'moving_object_simple' => 'Простая реакция на движущийся объект',
    'moving_object_complex' => 'Сложная реакция на движущийся объект',
    'analog_tracking' => 'Аналоговое слежение',
    'pursuit_tracking' => 'Слежение с преследованием',
    'schulte_table' => 'Тест внимания: Таблицы Шульте',
    'number_memorization' => 'Тест памяти: Запоминание чисел',
    'analogies_test' => 'Тест мышления: Аналогии'
];

$pageTitle = "Нейроинтерфейс и физиологические данные";
include 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">
        <i class="fas fa-brain"></i> Нейроинтерфейс и физиологические данные
    </h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Информационный блок -->
    <div class="alert alert-info">
        <h5><i class="fas fa-info-circle"></i> О нейроинтерфейсе</h5>
        <p class="mb-0">
            Этот раздел позволяет загружать и анализировать физиологические данные, полученные от нейроинтерфейсов.
            Поддерживаются различные типы данных: ЭЭГ, ЭКГ, ЭМГ, GSR и другие биометрические показатели.
            Данные могут быть связаны с результатами тестов для комплексного анализа.
        </p>
    </div>
    
    <!-- Форма загрузки данных -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-upload"></i> Загрузка физиологических данных
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <form id="physiologicalForm">
                        <div class="mb-3">
                            <label for="session_name" class="form-label">Название сессии записи</label>
                            <input type="text" class="form-control" id="session_name" name="session_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="recording_type" class="form-label">Тип записи</label>
                            <select class="form-select" id="recording_type" name="recording_type" required>
                                <option value="">Выберите тип</option>
                                <option value="EEG">ЭЭГ (электроэнцефалография)</option>
                                <option value="ECG">ЭКГ (электрокардиография)</option>
                                <option value="EMG">ЭМГ (электромиография)</option>
                                <option value="EOG">ЭОГ (электроокулография)</option>
                                <option value="GSR">GSR (кожно-гальваническая реакция)</option>
                                <option value="PPG">PPG (фотоплетизмография)</option>
                                <option value="MIXED">Смешанный тип</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration_seconds" class="form-label">Продолжительность (секунды)</label>
                            <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="test_session_id" class="form-label">Связать с тестом (опционально)</label>                            <select class="form-select" id="test_session_id" name="test_session_id">
                                <option value="">Не связывать с тестом</option>
                                <?php foreach ($recent_tests as $test): ?>
                                    <option value="<?php echo $test['id']; ?>">
                                        <?php echo htmlspecialchars($typeLabels[$test['test_type']] ?? $test['test_type']); ?> - 
                                        <?php echo date('d.m.Y H:i', strtotime($test['created_at'])); ?>
                                        <?php if ($test['accuracy']): ?>
                                            (точность: <?php echo round($test['accuracy'], 1); ?>%)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Заметки</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Дополнительная информация о записи..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Создать запись
                        </button>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded">
                        <h5>Инструкции по загрузке</h5>
                        <ol>
                            <li>Укажите название сессии записи</li>
                            <li>Выберите тип физиологических данных</li>
                            <li>Укажите продолжительность записи</li>
                            <li>При желании свяжите запись с результатом теста</li>
                            <li>Добавьте заметки для контекста</li>
                            <li>После создания записи можно будет загрузить данные через API</li>
                        </ol>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>API для загрузки данных:</strong><br>
                                POST /api/lab7/upload_physiological_data.php<br>
                                Content-Type: application/json<br>
                                Body: {"recording_id": ID, "data_points": [...]}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Список существующих записей -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-list"></i> Мои физиологические записи
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($recordings)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название сессии</th>
                                <th>Тип записи</th>
                                <th>Продолжительность</th>
                                <th>Связанный тест</th>
                                <th>Точек данных</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recordings as $recording): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($recording['session_name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($recording['recording_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $recording['duration_seconds']; ?> сек</td>                                    <td>
                                        <?php if ($recording['test_type']): ?>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($typeLabels[$recording['test_type']] ?? $recording['test_type']); ?><br>
                                                <?php echo date('d.m.Y', strtotime($recording['test_date'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $recording['data_points_count'] > 0 ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $recording['data_points_count']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($recording['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewRecordingDetails(<?php echo $recording['id']; ?>)">
                                            <i class="fas fa-eye"></i> Просмотр
                                        </button>
                                        <?php if ($recording['data_points_count'] == 0): ?>
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="showUploadInstructions(<?php echo $recording['id']; ?>)">
                                                <i class="fas fa-upload"></i> Загрузить данные
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <p class="text-muted mb-0">У вас пока нет физиологических записей. Создайте первую запись выше.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="/tests/my_results.php" class="btn btn-outline-primary">
            <i class="fas fa-chart-line"></i> Мои результаты тестов
        </a>
        <a href="/cabinet.php" class="btn btn-outline-secondary">
            <i class="fas fa-user"></i> Личный кабинет
        </a>
    </div>
</div>

<!-- Modal для деталей записи -->
<div class="modal fade" id="recordingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали записи</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recordingDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
// Type labels for human-readable test names
const typeLabels = <?php echo json_encode($typeLabels); ?>;

// Handle physiological form submission
document.getElementById('physiologicalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        user_id: <?php echo $user_id; ?>,
        session_name: formData.get('session_name'),
        recording_type: formData.get('recording_type'),
        duration_seconds: parseInt(formData.get('duration_seconds')),
        test_session_id: formData.get('test_session_id') || null,
        notes: formData.get('notes')
    };
    
    fetch('/api/lab7/create_physiological_recording.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Refresh to show new recording
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при создании записи');
    });
});

function viewRecordingDetails(recordingId) {
    // Load recording details
    fetch(`/api/lab7/get_physiological_recordings.php?recording_id=${recordingId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.recordings.length > 0) {
            const recording = data.recordings[0];
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Основная информация</h6>
                        <p><strong>Название:</strong> ${recording.session_name}</p>
                        <p><strong>Тип записи:</strong> ${recording.recording_type}</p>
                        <p><strong>Продолжительность:</strong> ${recording.duration_seconds} сек</p>
                        <p><strong>Точек данных:</strong> ${recording.data_points_count}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Дополнительно</h6>                        <p><strong>Дата создания:</strong> ${new Date(recording.created_at).toLocaleString('ru-RU')}</p>
                        ${recording.test_type ? `<p><strong>Связанный тест:</strong> ${typeLabels[recording.test_type] || recording.test_type}</p>` : ''}
                        ${recording.notes ? `<p><strong>Заметки:</strong> ${recording.notes}</p>` : ''}
                    </div>
                </div>
            `;
            document.getElementById('recordingDetailsContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('recordingDetailsModal')).show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при загрузке деталей записи');
    });
}

function showUploadInstructions(recordingId) {
    const instructions = `
        <h6>Инструкции по загрузке данных</h6>
        <p>Для загрузки данных в запись ID: ${recordingId}, используйте API:</p>
        <pre><code>POST /api/lab7/upload_physiological_data.php
Content-Type: application/json

{
  "recording_id": ${recordingId},
  "data_points": [
    {
      "timestamp_ms": 1000,
      "channel": "Channel1",
      "value": 0.123,
      "unit": "V",
      "quality_indicator": 1.0
    },
    ...
  ]
}</code></pre>
        <p class="text-muted mt-3">
            Каждая точка данных должна содержать timestamp_ms и value. 
            Поля channel, unit и quality_indicator опциональны.
        </p>
    `;
    document.getElementById('recordingDetailsContent').innerHTML = instructions;
    new bootstrap.Modal(document.getElementById('recordingDetailsModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
