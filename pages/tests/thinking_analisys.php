<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Тест на дни недели</title>
    <link rel="icon" href="../../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../styles/header.css">
    <link rel="stylesheet" href="../../styles/general.css">
    <link rel="stylesheet" href="../../styles/tests.css">
    <style>
        #question-container {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .day-button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #5d5d5d;
            color: white;
            margin:10px
        }

        .progress-container {
            width: 80%;
            background-color: #f3f3f3;
            border: 1px solid #ccc;
            margin-top: 20px;
            position: relative;
            height: 30px;
            margin: 0 auto;
        }

        .progress-bar {
            width: 0;
            height: 30px;
            background-color: #5d5d5d;
            text-align: center;
            line-height: 30px;
            color: white;
        }

        .score-container {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        #results {
            margin-top: 30px;
            width: 100%;
            align-content: center;
            bottom: 50%;
        }
    </style>
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>
    <div class="main">
        <h1>Тест на дни недели</h1>
        <div class="score-container" id="score-container" style="display: none">
            <h3>Счет: <span id="score">0</span></h3>
        </div>
        <div id="progress-container" class="progress-container" style="display: none">
            <div id="progress-bar" class="progress-bar"></div>
        </div>
        <div id="test-container">
            <div id="question-container">Как можно быстрее отвечайте на вопросы.<br>Нажмите "Начать тест", чтобы начать.</div>
            <div class="button-container" id="button-container" style="display: none">
                <button class="day-button" id="day_0">Понедельник</button>
                <button class="day-button" id="day_1">Вторник</button>
                <button class="day-button" id="day_2">Среда</button>
                <button class="day-button" id="day_3">Четверг</button>
                <button class="day-button" id="day_4">Пятница</button>
                <button class="day-button" id="day_5">Суббота</button>
                <button class="day-button" id="day_6">Воскресенье</button>
            </div>
            <button id="start-button" class="button">Начать тест</button>
        </div>
        <div id="results"></div>
    </div>

    <script type='module' src="../../scripts/tests/thinking_analisys.js"></script>
</body>

</html>