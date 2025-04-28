<?php

require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '\backend\help_funcs.php';
require_once ROOT . '\backend\db_managers.php';

if (currentUser()) {

    echo json_encode(array('response' => $_SESSION['testing']));
} else {
    echo json_encode(array('response' => null));
}