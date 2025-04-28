<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__) . "/backend/config.php";
require_once(ROOT . "/backend/db_managers.php");
require_once(ROOT . "/backend/help_funcs.php");
$tests = getTests();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестирование</title>
    <link rel="icon" href="../../ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/boxes.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<?php require_once ROOT . '/templates/header.php'; ?>

<div class="main">
    <h1 class="heading">Тестирование</h1>


    <?php

    if (!isset($_SESSION['testing']['stage'])) {
        $_SESSION['testing']['stage'] = 1;
    }

    if ($_SESSION['testing']['stage'] == 1 && $_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["testing_before"])) {
        $testing_before = $_POST["testing_before"];
        $_SESSION['testing'][0] = $testing_before;
        $_SESSION['testing']['stage'] = 2;
    }

    if ($_SESSION['testing']['stage'] == 2 && passedAll(currentUser()['id'])) {
        $_SESSION['testing']['stage'] = 3;
    }

    if ($_SESSION['testing']['stage'] == 4 && $_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['testing']['stage'] = 1;
        unset($_SESSION['testing'][0]);
        unset($_SESSION['testing'][1]);
        unset($_SESSION['testing'][2]);
    }

    if ($_SESSION['testing']['stage'] == 3 && $_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["testing_during"]) && !empty($_POST["testing_after"])) {
        $testing_during = $_POST["testing_during"];
        $_SESSION['testing'][1] = $testing_during;
        $testing_after = $_POST["testing_after"];
        $_SESSION['testing'][2] = $testing_after;

        $_SESSION['testing']['stage'] = 4;
        //echo "Показатель сердцебиения во время тестирования: $testing_during";
    }


    var_dump($_SESSION['testing']['stage']);
    ?>

    <!--    1-->

    <?php if ($_SESSION['testing']['stage'] == 1): ?>

        <h2>Измерьте показатель сердцебиения и введите его:</h2>
        <form method="post">
            <input type="number" id="testing_before" name="testing_before" placeholder="Показатель сердцебиения"
                   style="width: 20%; font-size: 24px; height: 30px;">
            <br>
            <button class="button" type="submit" style="width: 20%">Пройти тестирование</button>
        </form>
        <p>Во время тестирования необходимо время от времени измерять показатели сердцебиения,<br>затем вы введёте
            средние
            показатели во время тестирования, и показатели после тестирования.</p>

    <?php endif; ?>
    <?php if ($_SESSION['testing']['stage'] == 2): ?>
        <!--    2-->

        <h2 class="heading">Тесты на сенсомоторные реакции</h2>
        <div class="boxes">
            <?php foreach (array_slice($tests, 0, 5) as $test) : ?>
                <?php if (empty(getUserResults(currentUser()['id'], $test['id']))): ?>
                    <div class="box">
                        <div class="box_heading">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <p><?php echo $test['description']; ?></p>
                        <a href="./tests/<?php echo $test['href']; ?>">
                            <button class="button">Пройти</button>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на реакцию на движущийся объект</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 5, 2) as $test) : ?>
                <?php if (empty(getUserResults(currentUser()['id'], $test['id']))): ?>
                    <div class="box">
                        <div class="box_heading">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <p><?php echo $test['description']; ?></p>
                        <a href="./tests/<?php echo $test['href']; ?>">
                            <button class="button">Пройти</button>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на аналоговое слежение/преследование</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 7, 2) as $test) : ?>
                <?php if (empty(getUserResults(currentUser()['id'], $test['id']))): ?>
                    <div class="box">
                        <div class="box_heading">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <p><?php echo $test['description']; ?></p>
                        <a href="./tests/<?php echo $test['href']; ?>">
                            <button class="button">Пройти</button>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на внимание</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 9, 2) as $test) : ?>
                <?php if (empty(getUserResults(currentUser()['id'], $test['id']))): ?>
                    <div class="box">
                        <div class="box_heading">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <p><?php echo $test['description']; ?></p>
                        <a href="./tests/<?php echo $test['href']; ?>">
                            <button class="button">Пройти</button>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на память</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 11, 2) as $test) : ?>
                <?php if (empty(getUserResults(currentUser()['id'], $test['id']))): ?>
                    <div class="box">
                        <div class="box_heading">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <p><?php echo $test['description']; ?></p>
                        <a href="./tests/<?php echo $test['href']; ?>">
                            <button class="button">Пройти</button>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на мышление</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 13, 3) as $test) : ?>
                <?php if (empty(getUserResults(currentUser()['id'], $test['id']))): ?>
                    <div class="box">
                        <div class="box_heading">
                            <h3><?php echo $test['name']; ?></h3>
                        </div>
                        <p><?php echo $test['description']; ?></p>
                        <a href="./tests/<?php echo $test['href']; ?>">
                            <button class="button">Пройти</button>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
    <?php if ($_SESSION['testing']['stage'] == 3): ?>

        <!--    3-->

        <h2>Тесты пройдены! </h2>
        <p>Введите показатели сердцебиения:</p>
        <p>Во время тестирования:</p>
        <form method="post">
            <input type="number" id="testing_during" name="testing_during"
                   placeholder="Показатель сердцебиения во время тестирования"
                   style="width: 20%; font-size: 24px; height: 30px;">

            <p>После тестирования:</p>
            <input type="number" id="testing_after" name="testing_after"
                   placeholder="Показатель сердцебиения во время тестирования"
                   style="width: 20%; font-size: 24px; height: 30px;">

            <br>
            <button type="submit" class="button" style="width: 20%">Завершить тестирование</button>
        </form>

        <!--    4-->
    <?php endif; ?>
    <?php if ($_SESSION['testing']['stage'] == 4): ?>

        <h2>Результаты тестирования</h2>

    <?php //$_SESSION['testing'][0] = $testing_before;

    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["testing_during"])) {
        $testing_during = $_POST["testing_during"];
        $_SESSION['testing'][1] = $testing_during;
        //echo "Показатель сердцебиения во время тестирования: $testing_during";
    };
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["testing_after"])) {
        $testing_after = $_POST["testing_after"];
        $_SESSION['testing'][2] = $testing_after;
        //echo "Показатель сердцебиения во время тестирования: $testing_after";
    }; ?>

        <!--    график меняется сам в зависимости от переменных-->
        <div style="margin: 0 20%;">
            <canvas id="testing_chart"></canvas>
        </div>
        <script src="../scripts/testing.js" type="module"></script>

    <?php
    $max_change = (abs($_SESSION['testing'][2] - $_SESSION['testing'][1]) + abs($_SESSION['testing'][1] - $_SESSION['testing'][0])) / 2;
    ?>
        <p>Ваши показатели в среднем менялись на <?php echo $max_change; ?></p>
<!--    --><?php //if (passedAll(currentUser()['id'])) {
//        updateUserPiqs(currentUser()['id']);
//    } ?>
        <a href="./tests.php">
            <button class="button" style="width: 20%">Посмотреть уровень развития ПВК</button>
        </a>
        <form method="post">
            <button class="button" type="submit" style="width: 15%; height: 5%">Сбросить тестирование и начать заново
            </button>
        </form>

    <?php endif; ?>
</div>

</body>

</html>