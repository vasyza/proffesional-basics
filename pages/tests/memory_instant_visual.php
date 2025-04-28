<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Память: Мгновенная Визуальная</title>
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
        <div class="scale-text" id="progressBarText">0/9</div>
    </div>
    <div class="button-container">
        <div class="timer-text-container">
            <div class="timer" id="timer">0ms</div>
            <div class="text">Время текущей попытки</div>
        </div>
        <a href="../tests.php">
            <button class="back-button" id="backButton">Назад</button>
        </a>
        <button class="restart-button" id="restartButton">Перезапустить</button>
        <p class="instructions" id="memory-instructions">Как только увидите картинку, появившуюся второй раз, нажмите на неё</p>
        <button class="start-button" id="memory-start-button">Нажмите, чтобы начать тест</button>

        <div class="memory-image-panel" id="memoryPanel"></div>

    </div>
</div>

<script type='module' src="../../scripts/tests/memory_instant_visual.js"></script>
</body>

</html>