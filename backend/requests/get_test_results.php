<?php 

require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '\backend\help_funcs.php';
require_once ROOT . '\backend\db_managers.php';

$test_id = $_POST['test_id'];

$testResults = getTestResults($test_id);

if ($testResults) {
    echo json_encode(array('response' => $testResults));
} else {
    echo json_encode(array('response' => null));
}