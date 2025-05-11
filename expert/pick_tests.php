<?php
session_start();
require_once '../api/config.php';

// Авторизация
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'expert') {
    header("Location: /auth/login.php");
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId === 0) {
    header("Location: select_user.php");
    exit;
}

// Тесты без дубликатов
$testTypes = [
    'light_reaction' => 'Реакция на свет',
    'sound_reaction' => 'Реакция на звук',
    'color_reaction' => 'Реакция на разные цвета',
    'sound_arithmetic' => 'Звуковой сигнал и арифметика',
    'visual_arithmetic' => 'Визуальная арифметика'
];

$pageTitle = "Выбор тестов для пользователя";
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Выбор тестов для пользователя</h1>

    <form method="post" action="">
        <div id="test-container" class="mb-3">
            <div class="input-group mb-2 test-dropdown">
                <select name="tests[]" class="form-select">
                    <option value="" disabled selected>Выберите тест</option>
                    <?php foreach ($testTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="button" id="add-test" class="btn btn-outline-secondary mb-3">Добавить тест</button>
        <button type="button" id="remove-test" class="btn btn-outline-danger mb-3">Удалить последний тест</button>
        <button type="submit" name="submit" class="btn btn-success">Выбрать</button>
        <div id="copy-link-container" class="mt-3" style="display: none;">
            <button type="button" id="copy-link" class="btn btn-primary">Скопировать ссылку-приглашение на прохождение тестов</button>
        </div>
    </form>
</div>

<script>
    const testContainer = document.getElementById('test-container');
    const addTestButton = document.getElementById('add-test');
    const removeTestButton = document.getElementById('remove-test');
    const testTypes = <?php echo json_encode($testTypes); ?>;

    addTestButton.addEventListener('click', () => {
        const selectedTests = Array.from(document.querySelectorAll('select[name="tests[]"]')).map(select => select.value);
        const availableTests = Object.keys(testTypes).filter(test => !selectedTests.includes(test));

        if (availableTests.length === 0) return;

        const newSelect = document.createElement('div');
        newSelect.classList.add('input-group', 'mb-2', 'test-dropdown');
        newSelect.innerHTML = '<select name="tests[]" class="form-select">' +
            '<option value="" disabled selected>Выберите тест</option>' +
            availableTests.map(test => `<option value="${test}">${testTypes[test]}</option>`).join('') +
            '</select>';

        testContainer.appendChild(newSelect);
    });

    removeTestButton.addEventListener('click', () => {
        const dropdowns = document.querySelectorAll('.test-dropdown');
        if (dropdowns.length > 1) {
            dropdowns[dropdowns.length - 1].remove();
        }
    });
</script>

<?php
if (isset($_POST['submit'])) {
    $selectedTests = array_filter($_POST['tests']);

    if (!empty($selectedTests)) {
        try {
            $pdo = getDbConnection();
            $pdo->beginTransaction();

            // Создание новой партии тестов
            $stmt = $pdo->prepare("INSERT INTO test_batches (user_id, expert_id, created_at) VALUES (:user_id, :expert_id, NOW()) RETURNING id");
            $stmt->execute([':user_id' => $userId, ':expert_id' => $_SESSION['user_id']]);
            $batchId = $stmt->fetchColumn();

            // Вставка выбранных тестов в партию
            $stmt = $pdo->prepare("INSERT INTO tests_in_batches (batch_id, test_type, created_at) VALUES (:batch_id, :test_type, NOW())");
            foreach ($selectedTests as $test) {
                $stmt->execute([':batch_id' => $batchId, ':test_type' => $test]);
            }

            $pdo->commit();

            echo "<div class='alert alert-success'>Тесты успешно назначены пользователю.</div>";
            echo "<script>
                document.getElementById('copy-link-container').style.display = 'block';
                const copyLinkButton = document.getElementById('copy-link');
                copyLinkButton.addEventListener('click', function() {
                    const link = 'http://localhost:3000/tests/test_batch.php?batch_id=' + $batchId;
                    navigator.clipboard.writeText(link);
                    alert('Ссылка скопирована: ' + link);
                });
            </script>";

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<div class='alert alert-danger'>Ошибка базы данных: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

include '../includes/footer.php';
?>
