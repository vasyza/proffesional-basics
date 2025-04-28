<?php
require_once __DIR__ . 'db_managers.php';

$nextpage = 'pages/my_stats.php';


$test_id = $_POST['test_id'];
$reaction_time = $_POST['reaction_time'];
$accuracy = $_POST['accuracy'];
$misses = $_POST['misses'];
$mistakes = $_POST['mistakes'];

$user_id = currentUser()['id'];

addTestResults($user_id, $test_id, $reaction_time, $accuracy, $misses, $mistakes);

redirect($nextpage);