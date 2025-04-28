<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сложные цифровые звуковые сигналы</title>
    <link rel="icon" href="../../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../styles/header.css">
    <link rel="stylesheet" href="../../styles/general.css">
    <link rel="stylesheet" href="../../styles/tests.css">
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>


    <div class="main">
        <div class="scale-container" id="progressBar">
            <div class="scale-fill" id="progress">
            </div>
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
        </div>
        <button class="task-button-1" id="button-even">Четное<br>[1]</button>
        <button class="task-button-2" id="button-odd">Нечетное<br>[2]</button>

        <div class="plus">+</div>
        <div class="zvook1">
            <img src="../../resources/zvook.png" alt="Извинись." width="100" height="100">
        </div>
        <div class="zvook2">
            <img src="../../resources/zvook.png" alt="Извинись." width="100" height="100">
        </div>

        <script type='module' src="../../scripts/tests/reaction_audio_task.js"></script>
</body>

</html>