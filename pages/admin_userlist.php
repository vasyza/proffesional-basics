<?php
require_once dirname(__DIR__) . "/backend/config.php";
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список пользователей</title>
    <link rel="icon" href="../resources/ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/tables.css">
</head>

<body>
    <?php require_once ROOT . '/templates/header.php'; ?>

    <div class="main">
        <h1 class="heading">Список пользователей</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Пол</th>
                    <th>Дата рождения</th>
                    <th>Логин</th>
                    <th>Дата создания</th>
                    <th>Роль</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (getUsers() as $user) : ?>
                    <?php
                    $genderId = $user['gender_id'];
                    $roleId = $user['role_id'];
                    ?>
                    <tr>
                        <td>
                            <?php echo $user['id']; ?>
                        </td>
                        <td>
                            <?php echo $user['name']; ?>
                        </td>
                        <td>
                            <!-- <?php var_dump($genderId); ?> -->
                            <?php echo $genderId == 1 ? 'Мужской' : ($genderId == 2 ? 'Женский' : 'null'); ?>
                        </td>
                        <td>
                            <?php echo $user['birth_date']; ?>
                        </td>
                        <td>
                            <?php echo $user['login']; ?>
                        </td>
                        <td>
                            <?php echo $user['creation_date']; ?>
                        </td>
                        <td>
                            <!-- <?php var_dump($roleId); ?> -->
                            <?php echo $roleId == 0 ? 'Пользователь' : ($roleId == 1 ? 'Эксперт' : ($roleId == 2 ? 'Админ' : 'null')); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>