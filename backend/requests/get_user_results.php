<?php 

require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '\backend\help_funcs.php';
require_once ROOT . '\backend\db_managers.php';

if (currentUser()) {
    $user_id = $_SESSION['user']['id'];
    $test_id = $_POST['test_id'];

    $userResults = getUserResults($user_id, $test_id);

    if ($userResults) {
        echo json_encode(array('response' => $userResults));
    } else {
        echo json_encode(array('response' => null, 'user_id' => $user_id, 'test_id' => $test_id, 'post' => $_POST));
    }
} else {
    echo json_encode(array('response' => null));
}