<?php
require_once dirname(__DIR__) . "/backend/config.php";
require_once ROOT . "/backend/db_managers.php";
$tests = getTests();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тесты</title>
    <link rel="icon" href="../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/boxes.css">
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>

    <main class="main">
        <br>
        <?php if (currentUser()) : ?>
            <a href="./testing.php">
                <button class="button">Пройти тестирование с измерением сердцебиения</button>
            </a>
        <?php endif; ?>
        <h1 class="heading">Тесты на сенсомоторные реакции</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 0, 5) as $test) : ?>

                <div class="box">
                    <div class="box_heading">
                        <h3><?php echo $test['name']; ?></h3>
                    </div>
                    <p><?php echo $test['description']; ?></p>
                    <a href="./tests/<?php echo $test['href']; ?>">
                        <button class="button" style="margin: 20px 0">Пройти</button>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на реакцию на движущийся объект</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 5, 2) as $test) : ?>
                <div class="box">
                    <div class="box_heading">
                        <h3><?php echo $test['name']; ?></h3>
                    </div>
                    <p><?php echo $test['description']; ?></p>
                    <a href="./tests/<?php echo $test['href']; ?>">
                        <button class="button">Пройти</button>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на аналоговое слежение/преследование</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 7, 2) as $test) : ?>
                <div class="box">
                    <div class="box_heading">
                        <h3><?php echo $test['name']; ?></h3>
                    </div>
                    <p><?php echo $test['description']; ?></p>
                    <a href="./tests/<?php echo $test['href']; ?>">
                        <button class="button">Пройти</button>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на внимание</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 9, 2) as $test) : ?>
                <div class="box">
                    <div class="box_heading">
                        <h3><?php echo $test['name']; ?></h3>
                    </div>
                    <p><?php echo $test['description']; ?></p>
                    <a href="./tests/<?php echo $test['href']; ?>">
                        <button class="button">Пройти</button>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на память</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 11, 2) as $test) : ?>
                <div class="box">
                    <div class="box_heading">
                        <h3><?php echo $test['name']; ?></h3>
                    </div>
                    <p><?php echo $test['description']; ?></p>
                    <a href="./tests/<?php echo $test['href']; ?>">
                        <button class="button">Пройти</button>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <h1 class="heading">Тесты на мышление</h1>
        <div class="boxes">
            <?php foreach (array_slice($tests, 13, 3) as $test) : ?>
                <div class="box">
                    <div class="box_heading">
                        <h3><?php echo $test['name']; ?></h3>
                    </div>
                    <p><?php echo $test['description']; ?></p>
                    <a href="./tests/<?php echo $test['href']; ?>">
                        <button class="button">Пройти</button>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

</body>

</html>