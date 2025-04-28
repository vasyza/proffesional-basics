<?php

require_once dirname(__DIR__) . "/backend/config.php";
require_once ROOT . "/backend/db_managers.php";
require_once ROOT . "/backend/help_funcs.php";

if (currentUser()) {
    $userId = currentUser()['id'];
} else {
    $userId = -1;
}

updatePiqLevels($userId);

var_dump(passedAll($userId));
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профессии</title>
    <link rel="icon" href="../../ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/professions.css">
    <link rel="stylesheet" href="../styles/windows.css">

</head>

<body>
<?php require_once ROOT . '/templates/header.php'; ?>

<main class="main">
    <h1 class="heading">Профессии</h1>
    <div class="profession-boxes">
        <?php $professions = getProfessions();
        if (empty ($professions)): ?>
            <p style="font-family: Times new roman; font-size: 24px;">Сюда ещё не добавлены профессии</p>
        <?php else: ?>
            <?php foreach ($professions as $profession): ?>
                <div class="profession-box">
                    <h2>
                        <?php echo $profession['name']; ?>
                    </h2>
                    <p>
                        <?php echo $profession['description']; ?>
                    </p>

                    <br>
                    <?php if ($userId != -1 && passedAll($userId)): ?>
                        <?php $level = getProfessionMatch($userId, $profession['id']); ?>
                        Ваша совместимость с профессией:
                        <div class="rating-bar"
                             style="width: 90%; background: linear-gradient(to right, green <?php echo $level; ?>%, white 0%);">
                        </div>
                        <?php echo $level . '%'; ?>
                        <br>
                        <button class="my-piqs" profession_id="<?php echo $profession['id']; ?>">Мои ПВК</button>
                    <?php endif; ?>
                    <button class="pvc-button">ПВК для профессии</button>
                    <dl class="pvc-list">
                        <?php $results = getProfResultRating($profession['id'], 5);
                        if (empty ($results)): ?>
                            <p>Для данной профессии ещё нет оценок</p>
                        <?php else: ?>
                            <?php $piqs = []; ?>
                            <?php foreach ($results as $piq_id => $result): ?>
                                <?php $importance = round($result['importance'] * 100);
                                $piq = $result['piq']; ?>
                                <?php echo $piq['name']; ?>
                                <br>
                                <div class="rating-bar"
                                     style="width: 90%; background: linear-gradient(to right, red <?php echo $importance; ?>%, white 0%);">
                                </div>
                                <div class="rating-progress">
                                    <?php echo $importance . '%'; ?>
                                    <?php $piqs[$piq_id] = $importance; ?>
                                </div>
                                <br>
                                <?php $piqId = $piq['id']; ?>
                                <?php $level = getOnePiqLevelFromDB($userId, $piqId)['level']; ?>
                                Уровень развития ПВК:
                                <div class="rating-bar"
                                     style="width: 90%; background: linear-gradient(to right, green <?php echo $level; ?>%, white 0%);">
                                </div>
                                <?php echo $level . '%'; ?>
                                <br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </dl>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<div class="window" id="my-piqs-window" style="display: none;">
    <div class="window-content">
        <div class="window-header">
            <div class="window-title">Мои ПВК</div>
            <div class="close-window" id="close_my_piqs_window">&times;</div>
        </div>
        <div class="window-body">
            <div class="window-text" id="window_message">Текст</div>
        </div>
    </div>
</div>

<script src="../scripts/professions.js"></script>
</body>

</html>