<?php
require_once dirname(__DIR__) . "/backend/config.php";

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корректировка весов</title>
    <link rel="icon" href="../../ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/weight_change.css">
    <style>
        body {
            /*font-family: Arial, sans-serif;*/
            margin: 20px;
        }

        h1 {
            text-align: center;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        button#save-button {
            margin-top: 20px;
        }

        input {
            padding: 10px 20px;
        }

        select {
            padding: 10px 20px;
        }

    </style>
</head>

<body>
<?php require_once ROOT . '/templates/header.php'; ?>

<div class="main">
    <h1>Настройка Весов для ПВК</h1>
    <div id="container">
        <div class="pvk-container" id="pvk-container">
            <!-- Здесь будут добавлены элементы для каждого ПВК -->
        </div>
        <button id="save-button">Сохранить</button>
    </div>
    <script type='module' src="../scripts/weight_change.js"></script>
</div>

</body>

</html>