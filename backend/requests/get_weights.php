<?php

require_once dirname(__DIR__) . "/config.php";
require_once ROOT . '/backend/help_funcs.php';
require_once ROOT . '/backend/db_managers.php';

$weights = getWeights();

//updatePiqLevels();

echo json_encode($weights);