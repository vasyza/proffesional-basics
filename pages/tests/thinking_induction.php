<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мышление: Индукция</title>
    <link rel="stylesheet" href="../../styles/header.css">
    <link rel="stylesheet" href="../../styles/general.css">
    <link rel="stylesheet" href="../../styles/tests.css">
    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            /* default grid template, will be updated dynamically */
            gap: 0;
            justify-content: center;
            max-width: 100%;
            /* Adjust this based on the longest word length to ensure columns are close together */
            margin: 0 auto;
        }

        .cell {
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #3a3a3c;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            /* Remove gaps between cells */
            box-sizing: border-box;
            /* Ensure padding and border are included in the width and height */
        }

        .correct {
            background-color: green;
            color: white;
        }

        .present {
            background-color: orange;
            color: white;
        }

        .absent {
            background-color: #3a3a3c;
            color: white;
        }

        .button-container {
            margin-top: 20px;
        }

        .word-input {
            margin-top: 10px;
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
            left: 35%;
        }

        #wordle-container {
            display: none;
        }
    </style>
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>
    <main class="main">
        <div id="test-container">
            <h1>Мышление: Индукция</h1>
            <p id="instructions">Нажмите "Начать тест", чтобы начать.</p>
            <div id="wordle-container">
                <div id="grid" class="grid"></div>
                <div id="input-container" class="word-input">
                    <input type="text" id="word-input">
                    <button id="guess">Угадать</button>
                </div>
            </div>
            <button id="start-button">Начать тест</button>
        </div>
            <a href="../tests.php">
                <button style="position: absolute; top:20%" class="back-button" id="backButton">Назад</button>
            </a>
        <div id="results"></div>
    </main>
<script type='module' src="../../scripts/tests/thinking_induction.js"></script>
</body>

</html>