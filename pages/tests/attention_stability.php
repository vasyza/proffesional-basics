<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Тест на реакцию</title>
    <link rel="stylesheet" href="../../styles/header.css">
    <link rel="stylesheet" href="../../styles/general.css">
    <link rel="stylesheet" href="../../styles/tests.css">
    <style>
        /* body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
        } */

        .grid {
            display: grid;
            grid-template-columns: repeat(5, 100px);
            grid-template-rows: repeat(3, 100px);
            gap: 10px;
            justify-content: center;
            margin: 0 auto;
            position: relative;
            width: 540px;
            height: 340px;
        }

        .circle {
            width: 50px;
            height: 50px;
            background-color: red;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            transition: transform 0.5s linear;
        }

        #results {
            margin-top: 30px;
            position: absolute;
            top: 30%;
            left: 40%;
            
        }

        #start-button {
            padding: 10px 20px;
            font-size: 16px;
        }

        .progress-container {
            width: 100%;
            background-color: #f3f3f3;
            border: 1px solid #ccc;
            margin-top: 20px;
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
    </style>
</head>

<body>
<?php require_once ROOT . '/templates/header.php'; ?>
<main class="main">
    <h1>Тест на устойчивость внимания</h1>
    <div class="score-container" id="score-container" style="display: none">
        <h3>Счет: <span id="score">0</span></h3>
    </div>
    <div id="progress-container" class="progress-container" style="display: none">
        <div id="progress-bar" class="progress-bar"></div>
    </div>
    <div id="test-container">
        <p id="instructions">Нажмите "Начать тест", чтобы начать.</p>
        <div id="grid-container" class="grid"></div>
        <button id="start-button" onclick="startTest()">Начать тест</button>
    </div>
    <div id="results"></div>
</main>
    <script type='module' src="../../scripts/tests/attention_stability.js"></script>
</body>

</html>