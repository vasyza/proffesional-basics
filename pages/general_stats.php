<?php
require_once dirname(__DIR__) . "/backend/config.php";
require_once ROOT . "/backend/help_funcs.php";
require_once ROOT . "/backend/db_managers.php";
$tests = getTests();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Общая статистика</title>
    <link rel="icon" href="../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/tables.css">
    <link rel="stylesheet" href="../styles/stats.css">
    <link rel="stylesheet" href="../styles/boxes.css">
    <link rel="stylesheet" href="../styles/windows.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<?php require_once ROOT . '/templates/header.php'; ?>

<main class="main">
    <h1 class="heading">Общие результаты</h1>

    <div class="norm-panel">
        <div class="norm-element">
            <label for="male">Муж</label>
            <input type="radio" name="gender" id="male" value="М" checked>
            <label for="female">Жен</label>
            <input type="radio" name="gender" id="female" value="Ж">
        </div>
        <div class="norm-element">
            Возраст:
            <select name="age" id="norm-age">
                <option value="0">0-10</option>
                <option value="1">11-20</option>
                <option value="2">21-30</option>
                <option value="3">31-40</option>
                <option value="4">41-50</option>
                <option value="5">51-60</option>
                <option value="6">60+</option>
            </select>
        </div>
        <button class="norm-button" id="normalize">Показать пользователей</button>
        <button class="norm-button" id="showAll">Показать всех</button>
    </div>

    <div class="table_container">
        <table class="table">

            <thead>
            <tr>
                <th>Логин пользователя</th>
                <?php foreach ($tests as $test): ?>
                    <th testId="<?php echo $test['id']; ?>"><?php echo $test['name']; ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <tr class="table_row0" id="mid_results">
                <td>Средние результаты всех пользователей</td>
                <?php foreach ($tests

                as $test) : ?>
                <?php
                $testId = $test['id'];
                $mids = getMidUserStats($testId);
                ?>
                <td>
                    <?php if (!$mids) : ?>
                        <div class="stat_container">
                            <p>Никто не проходил этот тест</p>
                        </div>
                    <?php else : ?>
                        <?php foreach ($mids as $stat => $midValue): ?>
                            <div class="stat_container">
                                <p><?php echo $stat; ?>:</p>
                                <?php echo $midValue; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="stat_container">
                            <p>Количество прохождений:</p>
                            <?php echo countUserResults($testId); ?>
                        </div>
                        <button class="stat_button" name="show_test_dynamic" test_id="<?php echo $testId; ?>">
                            Динамика
                        </button>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php foreach (getUsers() as $user) : ?>
                <?php $userId = $user['id'];
                $userLogin = $user['login'];
                ?>

                <tr class="table_row" id="<?php echo $userLogin; ?>">
                    <td>
                        <?php echo $user['login']; ?>
                    </td>
                    <?php foreach ($tests

                    as $test) : ?>
                    <?php
                    $testId = $test['id'];
                    $mids = getMidUserStats($testId, $userId);
                    ?>
                    <td>
                        <?php if (!$mids) : ?>
                            <div class="stat_container">
                                <p>Пользователь ещё не проходил этот тест</p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($mids as $stat => $midValue): ?>
                                <div class="stat_container">
                                    <p><?php echo $stat; ?>:</p>
                                    <?php echo $midValue; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="stat_container">
                                <p>Количество прохождений:</p>
                                <?php echo countUserResults($testId, $userId); ?>
                            </div>
                            <button class="stat_button" name="show_spec_user_dynamic" test_id="<?php echo $testId; ?>" user_id="<?php echo $userId; ?>">
                                Динамика
                            </button>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                </tr>

            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include dirname(__DIR__) . '/templates/chart.php'; ?>

<script type='module' src='../scripts/general_stats.js'></script>

</body>

</html>