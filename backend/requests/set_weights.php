<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '/backend/help_funcs.php';
require_once ROOT . '/backend/db_managers.php';

$weights = json_decode($_POST['weights'], true);
//$weights = json_decode("[{\"piq_id\":244,\"test_id\":0,\"stat_name\":\"mistakes\",\"weight\":12},{\"piq_id\":249,\"test_id\":0,\"stat_name\":\"reaction_time\",\"weight\":12},{\"piq_id\":249,\"test_id\":0,\"stat_name\":\"accuracy\",\"weight\":12},{\"piq_id\":249,\"test_id\":0,\"stat_name\":\"Успешные попытки\",\"weight\":12},{\"piq_id\":215,\"test_id\":0,\"stat_name\":\"reaction_time\",\"weight\":0.5}]", true);

//print_r($weights);

$suc = setWeights($weights);

//updatePiqLevels();

echo json_encode(array("response" => $suc));