<?php 

require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '/backend/help_funcs.php';
require_once ROOT . '/backend/db_managers.php';

$gender_id = $_POST['gender_id'];
$age_interval = $_POST['age_interval'];

echo json_encode(array('response' => getNormalizedUserLogins($gender_id, $age_interval)));