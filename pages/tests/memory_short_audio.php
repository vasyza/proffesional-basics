<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест на музыкальные ноты</title>
    <link rel="stylesheet" href="../../styles/header.css">
    <link rel="stylesheet" href="../../styles/general.css">
    <link rel="stylesheet" href="../../styles/tests.css">
    <style>
        .button-container {
            margin-top: 20px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            margin: 5px;
        }

        #results {
            margin-top: 30px;
            position: absolute;
            top: 30%;
            left: 30%;
        }

        #progress-container {
            width: 50%;
            margin: 0 auto;
            height: 30px;
            background-color: #f3f3f3;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            display: none;
        }

        #progress-bar {
            height: 100%;
            width: 0;
            background-color: #5d5d5d;
            transition: width 0.5s;
        }
    </style>
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>
    <main class="main">
        <h1>Тест на музыкальные ноты</h1>
        <div id="test-container">
            <p id="instructions">Нажмите "Начать тест", чтобы начать.</p>
            <div id="progress-container">
                <div id="progress-bar"></div>
            </div>
            <div id="buttons-container" class="button-container"></div>
            <button id="start-button" style="position: absolute; top: 40%; left:45%;">Начать тест</button>
        </div>
        </div>
        <a href="../tests.php">
            <button style="position: absolute; top:20%" class="back-button" id="backButton">Назад</button>
        </a>
        <div id="results"></div>
    </main>
    <script type='module' src="../../scripts/tests/memory_short_audio.js"></script>
</body>

</html>