<?php
require_once dirname(dirname(__DIR__)) . "/backend/config.php";

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Название</title>
    <link rel="icon" href="../../resources/ico.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="../../styles/header.css"/>
    <link rel="stylesheet" href="../../styles/general.css"/>
    <!-- <link rel="stylesheet" href="../../styles/tests.css" /> -->
    <style>
        #test-container {
            display: none;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 400px;
            border: 1px solid #ccc;
            background-color: white;
        }

        #chase-square {
            left: 50%;
            position: absolute;
            bottom: 60%;
            width: 50px;
            height: 50px;
            background-color: blue;
        }

        #marker {
            position: absolute;
            margin-top: 10px;
            width: 20px;
            height: 20px;
            background-color: red;
            border-radius: 50%;
        }

        #start-button,
        #submit-time-button {
            padding: 10px 20px;
            font-size: 16px;
            margin-top: 50px;
            cursor: pointer;
        }

        #results {
            /* position: absolute;
            top: 15%;
            left: 20%; */
        }

        #menu {
            margin-top: 20px;
        }

        .scale-text {
            position: relative;
            margin-left: 48%;
            font-size: 24px;
            line-height: 1.5;
            top: 25px;
        }

        #results {
            /* position: absolute;
            bottom: -30%; */
        }

        .scale-container {
            position: absolute;
            left: 15%;
            width: 70%;
            height: 30px;
            background-color: #ccc;
            /* padding: 75px 0; */
        }

        .scale-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background-color: #f00;
            width: 50%;
        }

        .big-red-button {
            display: block;
            position: absolute;
            bottom: -5%;
            justify-content: center;
            align-items: center;
            width: 150px;
            height: 150px;
            background-color: green;
            color: white;
            border: none;
            cursor: pointer;
        }


        .button-container {
            position: relative;
            height: 50vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .timer-text-container {
            position: absolute;
            margin-top: 400px;
            right: 10%;
            transform: translateX(-50%);
        }

        .timer {
            right: 10%;
            width: 100px;
            height: 50px;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 400px;
        }

        .text {
            text-align: center;
        }

        .bottom-button {
            position: absolute;
            bottom: -65.5%;
            display: block;
            width: 150px;
            height: 50px;
            right: 2%;
            border: none;
            border-radius: 5px;
            background-color: blue;
            color: white;
            cursor: pointer;
        }

        .restart-button {
            position: absolute;
            margin-top: 800px;
            display: block;
            width: 150px;
            height: 50px;
            border: none;
            border-radius: 5px;
            left: 2%;
            background-color: rgb(0, 118, 39);
            color: white;
            cursor: pointer;
        }

        .back-button {
            position: absolute;
            bottom: 84%;
            display: block;
            width: 150px;
            height: 50px;
            border: none;
            border-radius: 5px;
            left: 2%;
            background-color: blue;
            color: white;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        input[type="number"] {
            width: 200px;
            height: 50px;
            border-radius: 10px;
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }

        button {
            width: 200px;
            height: 50px;
            border-radius: 10px;
            background-color: #5d5d5d;
            color: white;
            margin-top: 10px;
        }

        #myProgress {
            position: absolute;
            width: 99%;
            height: 30px;
            bottom: 10%;
            background-color: #ddd;
        }

        #myBar {
            position: absolute;
            width: 0%;
            height: 100%;
            background-color: #5d5d5d;
        }

        #label {
            text-align: center;
            line-height: 30px;
            color: white;
        }

        .timer-text-container2 {
            position: absolute;
            margin-top: 410px;
            left: 20%;
            transform: translateX(-50%);
        }

        .timer2 {
            left: 20%;
            width: 100px;
            height: 50px;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 410px;
        }

        .modal {
            display: none;
            /* Скрываем всплывающее окно по умолчанию */
            position: fixed;
            /* Фиксируем позицию окна относительно окна браузера */
            z-index: 1;
            /* Устанавливаем z-index, чтобы окно было поверх других элементов */
            left: 0;
            top: 0;
            width: 100%;
            /* Ширина окна равна ширине окна браузера */
            height: 100%;
            /* Высота окна равна высоте окна браузера */
            background-color: rgb(0, 0, 0);
            /* Цвет фона окна */
            background-color: rgba(0,
            0,
            0,
            0.4);
            /* Цвет фона окна с прозрачностью */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            /* Центрируем окно по вертикали и горизонтали */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            /* Ширина окна */
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        #ball {
            position: absolute;
            top: 200px;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        #center-zone {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }


        #button {
            position: absolute;
            bottom: -50%;
            display: block;
            width: 150px;
            height: 50px;
            right: 2%;
            border: none;
            border-radius: 5px;
            background-color: blue;
            color: white;
            cursor: pointer;
        }

        #verticalLine {
            position: absolute;
            height: 200%;
            width: 7px;
            margin-top: 25%;
            background-color: red;
            left: 50%;
            transform: translateX(-50%);
        }
    </style>
</head>

<body>
<?php require_once ROOT . '/templates/header.php'; ?>
<div class="main">
    <div id="menu">
        <h2>Введите время выполнения теста (минуты):</h2>
        <input type="number" id="test-time-input" min="2" max="45" value="2"/>
        <button id="start-button" onclick="startTest()">Начать тест</button>
    </div>
    <div id="test-container">
        <div id="chase-square"></div>
        <div id="marker"></div>
    </div>
    <div id="results" style="width:100%; align-content:center;bottom:50%; z-index: 100000;"></div>
    <div id="test-time-counter" style="position: absolute; top: 10px; right: 10px; font-size: 18px"></div>
    <div id="test-container">
        <div id="chase-square" style="margin-top: 200px; left: 50%;"></div>
        <div id="marker" style="margin-top: 200px"></div>
    </div>
    <div id="myProgress">
        <div id="myBar"></div>
    </div>
</div>
<script type='module' src="../../scripts/tests/analog_chase.js"></script>
<script>
    document.getElementById('chase-square').style.left = "50%";
</script>
</body>

</html>