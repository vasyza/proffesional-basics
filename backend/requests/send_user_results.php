<?php

require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '/backend/help_funcs.php';
require_once ROOT . '/backend/db_managers.php';

if (currentUser()) {
    $user_id = $_SESSION['user']['id'];
    $test_id = $_POST['test_id'];
    $statistics = $_POST['statistics'];
    
    addTestResults($user_id, $test_id, $statistics);

    // отправить с помощью echo что то по типу ваши результаты сохранены
    echo json_encode(array('response' => 'done', 'stats' => $statistics, 'post' => $_POST));

    if (passedAll($user_id)){
        updateUserPiqs($user_id);
    }
} else {
    // что то придумать для незареганных пользователей
    // отправить с помощью echo что то по типу тест пройден, зарегайтесь, чтобы просматривать динамику и историю ваших прохождений
    echo json_encode(array('response' => 'done', 'stats' => null));
}
