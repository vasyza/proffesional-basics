<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Тест на абстрактное мышление</title>
    <link rel="icon" href="../../resources/ico.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="../../styles/header.css"/>
    <link rel="stylesheet" href="../../styles/general.css"/>
    <link rel="stylesheet" href="../../styles/tests.css"/>
    <style>
         #test-container,
      #results,
      .progress-container,
      .score-container {
        display: none;
      }
      #question-container {
        font-size: 24px;
        margin-bottom: 20px;
      }
      #answer-input {
        padding: 10px;
        font-size: 16px;
        width: 300px;
        margin-bottom: 20px;
      }
      #submit-button {
        padding: 10px 20px;
        font-size: 16px;
        margin-bottom: 20px;
      }
      .progress-container {
        width: 100%;
        background-color: #f3f3f3;
        height: 30px;
      }

      .progress-bar {
        height: 100%;
        width: 0;
        background-color: #5d5d5d;
        text-align: center;
        line-height: 30px;
        color: white;
      }
      table {
        width: 80%;
        margin: 20px auto;
        border-collapse: collapse;
      }
      th,
      td {
        border: 1px solid #ddd;
        padding: 8px;
      }
      th {
        background-color: #5d5d5d;
        color: white;
      }
      #results {
            margin-top: 20px;
            position: absolute;
            top: 20%;
            left: 25%;
        }
    </style>
</head>
<body>
    <?php require_once ROOT . '/templates/header.php'; ?>

    <div class="main">
        <h1>Тест на абстрактное мышление</h1>
        <div id="question-container">Нажмите "Начать тест", чтобы начать.</div>
        <button id="start-button" onclick="startTest()" class="button" style="width: 20%;">Начать тест</button>
        <a href="../tests.php">
            <button style="position: absolute; top:20%" class="back-button" id="backButton">Назад</button>
        </a>

        <div id="test-container">
            <div id="question-container"></div>
            <input type="text" id="answer-input" placeholder="Ваш ответ..." />
            <button id="submit-button" onclick="submitAnswer()">Ответить</button>
            <div class="score-container" id="score-container">
                <h3>Счет: <span id="score">0</span></h3>
            </div>
            <div class="progress-container" id="progress-container">
                <div id="progress-bar" class="progress-bar"></div>
            </div>
        </div>

        <div id="results"></div>
    </div>

    <script type='module' src="../../scripts/tests/abstraction_thinking.js"></script>
</body>
</html>
