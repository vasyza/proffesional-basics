<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сложные цветные сигналы</title>
    <link rel="icon" href="../../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../styles/header.css">
    <link rel="stylesheet" href="../../styles/general.css">
    <link rel="stylesheet" href="../../styles/tests.css">
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>


    <div class="main">
        <div class="scale-container">
            <div class="scale-fill" id="progress"></div>
            <div class="scale-text" id="progressBarText">0/15</div>
        </div>
        <div class="button-container">
            <div class="timer-text-container">
                <div class="timer" id="timer">0 ms</div>
                <div class="text" id="timerText">Время реакции текущей попытки</div>
            </div>
            <button class="restart-button" id="restartButton">Перезапустить</button>
            <a href="../tests.php">
                <button class="back-button" id="backButton">Назад</button>
            </a>
            <div class="grey-button-container">
                <button class="grey-button" id="circle">
                    <i class="fas fa-volume-up"></i>
                </button>
            </div>

        </div>
        <button class="color-button-1" id="button1">[1]</button>
        <button class="color-button-2" id="button2">[2]</button>
        <button class="color-button-3" id="button3">[3]</button>
    </div>

    
    <script type='module' src="../../scripts/tests/reaction_colors.js"></script>
</body>

</html>