<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Простые звуковые сигналы</title>
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
            <button class="big-red-button" id="button">Включи звук и нажми, чтобы начать тест</button>
            <div class="timer-text-container">
                <div class="timer" id="timer">0 ms</div>
                <div class="text">Время реакции текущей попытки</div>
            </div>
            <div id="results"></div>
            <div id="attempts"></div>
            <a href="../tests.php">
                <button class="back-button" id="backButton">Назад</button>
            </a>
            <button class="restart-button" id="restartButton">Перезапустить</button>
            <!-- <div class="zvook-container">
                <div class="zvook">
                    <img src="../../resources/zvook0.png" alt="Извинись." width="150" height="150">
                </div>
            </div> -->
        </div>
    </div>

    
    <script type='module' src="../../scripts/tests/reaction_audio.js"></script>

</body>

</html>