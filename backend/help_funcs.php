<?php

require_once dirname(__DIR__) . "/backend/config.php";

// обычные функции

// перейти на конкретную страницу
// можно использовать .. и .
function redirect($url, $statusCode = 303)
{
    header('Location: ' . $url, true, $statusCode);
    die();
}

// Вернуться на предыдущую страницу
function redirectToPrevious()
{
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        redirect('/ЛР2-4/pages/main.php');
    }
    die();
}

function setValidationError(string $fieldName, string $message): void
{
    $_SESSION['validation'][$fieldName] = $message;
}

function hasValidationError(string $fieldName): bool
{
    return isset($_SESSION['validation'][$fieldName]);
}

function hasValidationErrors(): bool
{
    // print_r($_SESSION['validation']);
    if (!empty($_SESSION['validation']) || (hasMessage('error'))) {
        return true;
    } else {
        return false;
    }
}

function validationErrorMessage(string $fieldName): string
{
    $message = $_SESSION['validation'][$fieldName] ?? '';
    unset($_SESSION['validation'][$fieldName]);
    return $message;
}

function clearValidationErrors(): void
{
    unset($_SESSION['validation']);
    unset($_SESSION['message']);
}

function clearSession(): void
{
    clearValidationErrors();
    unset($_SESSION['old']);
}

function setOldValue(string $key, mixed $value): void
{
    $_SESSION['old'][$key] = $value;
}

function old(string $key)
{
    $value = $_SESSION['old'][$key] ?? '';
    unset($_SESSION['old'][$key]);
    return $value;
}

function setMessage(string $key, string $message): void
{
    $_SESSION['message'][$key] = $message;
}

function hasMessage(string $key): bool
{
    return isset($_SESSION['message'][$key]);
}

function getMessage(string $key): string
{
    $message = $_SESSION['message'][$key] ?? '';
    unset($_SESSION['message'][$key]);
    return $message;
}

function logout(): void
{
    unset($_SESSION['user']);
    // redirect('pages/main.php');
    redirectToPrevious();
}

function checkAuth(): void
{
    if (!isset($_SESSION['user']['id'])) {
        redirect('pages/main.php');
    }
}

function checkGuest(): void
{
    if (isset($_SESSION['user']['id'])) {
        redirect('pages/main.php');
    }
}

function getNormalizedUserLogins($gender_id, $ageInterval): array
{
    $logins = [];
    foreach (getUsers() as $user) {
        if ($user['gender_id'] == $gender_id) {

            $age = getAge($user['birth_date']);

            if ($age > $ageInterval * 10 && $age <= ($ageInterval + 1) * 10 || ($ageInterval == 0 && $age <= 0) || ($ageInterval == 6 && $age >= 60)) {
                $logins[] = $user['login'];
            }
        }
    }
    return $logins;
}

function getAge($birthDate)
{
    $birthDate = $birthDate;
    //explode the date to get month, day and year
    $birthDate = explode("-", $birthDate);
    //get age from date or birthdate
    $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[2], $birthDate[0], $birthDate[1]))) > date("md")
        ? ((date("Y") - $birthDate[0]) - 1)
        : (date("Y") - $birthDate[0]));

    return $age;
}


// ПОСЧИТАТЬ ИТОГОВЫЙ отсортированный рейтинг у профессии
// типа подсчитать список ПВК и их процент важности у каждого для ОДНОЙ профессии
function countProfResultRating(int $prof_id): void
{
    global $professionPiqs;
    // возвращается список ссылочных массивов
    // resultPiq - как вариант инстанса одного такого массива (пвк + его важность от 0 до 1 вкл.)
    // resultPiq = getProfResultRating[piq_id]
    // resultPiq['piq']
    // resultPiq['importance']
    $ratings = getProfRatings($prof_id);
    $result = []; // result = [piq_id => ['piq' => piq, 'importance' => float]]
    if ($ratings) {
        foreach ($ratings as $rating) {
            $currPiq = $rating['piq'];
            $currPriority = $rating['priority'];
            $piq = $result[$currPiq['id']] ?? null;
            if (is_null($piq)) {
                $result[$currPiq['id']]['piq'] = $currPiq;
                $result[$currPiq['id']]['importance'] = $currPriority / 10;
            } else {
                $result[$currPiq['id']]['importance'] = ($result[$currPiq['id']]['importance'] + ($currPriority / 10)) / 2;
            }
        }
    }

    usort($result, 'importanceSort');
    $result = array_reverse($result);
    $professionPiqs[$prof_id] = $result;
}

function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

// Получить подсчёт всех ПВК для ОДНОЙ профессии
function getProfResultRating(int $prof_id, int $n = 0): array
{
    global $professionPiqs;
    if (is_null($professionPiqs[$prof_id] ?? null)) {
        countProfResultRating($prof_id);
    }
    debug_to_console(json_encode($professionPiqs));
    if ($n == 0) {
        return $professionPiqs[$prof_id];
    } else {
        return array_slice($professionPiqs[$prof_id], 0, $n);
    }
}

function importanceSort($x, $y)
{
    return $x['importance'] <=> $y['importance'];
}

// 7 лаба

// массив, в котором хранятся наборы пвк профессии
// id тестов из базы данных
$PROFESSIONS_TO_PIQ = [
    1 => [253, 301, 245, 246, 282],
    2 => [251, 240, 244, 241, 282],
    3 => [249, 215, 282, 254, 260]
];

const REQUIERED_PIQS = [253, 301, 245, 246, 282, 251, 240, 244, 241, 249, 215, 254, 260];

// массив, в котором пвк ставятся в соответсвие тесты
// пвк из бд
// $PIQ_TO_TESTS = [
//     253 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 100],
//     301 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 40, 0, 10, 50, 0, 0, 0],
//     245 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0, 75, 20, 0],
//     246 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26, 67, 7],
//     282 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 14, 14, 14, 14, 15, 14, 14],
//     251 => [0, 0, 0, 0, 0, 2, 2, 5, 5, 24, 0, 24, 28, 0, 0, 10],
//     240 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 45, 0, 0, 50],
//     244 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 10, 10, 40, 35],
//     241 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 7, 6, 20, 60],
//     249 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 20, 75],
//     215 => [0, 0, 0, 0, 0, 15, 15, 20, 15, 0, 0, 0, 0, 30, 5, 0],
//     254 => [0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 8, 25, 15, 25, 7, 13],
//     260 => [0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 85, 0, 0, 5]
//     // 215 | Способность аргументировано отстаивать свое мнение
//     // 240 | Способность к пространственному воображению
//     // 241 | Способность к образному представлению предметов, процессов и явлений
//     // 244 | Способность к воссозданию образа по словесному описанию
//     // 245 | Аналитичность (способность выделять отдельные элементы действительности, способность к классификации)
//     // 246 | Синтетичность (способность к обобщениям, установлению связей, закономерностей, формирование понятий)
//     // 249 | Креативность (способность порождать необычные идеи, отклоняться от традиционных схем мышления)
//     // 251 | Предметность (объекты реального мира и их признаки)
//     // 253 | Абстрактность (абстрактные образы, понятия)
//     // 254 | Вербальность (устная и письменная речь)
//     // 260 | Зрительная долговременная память на слова и фразы
//     // 282 | Умственная работоспособность
//     // 301 | Объем внимания (количество объектов, на которые может быть направлено внимание при их одновременном восприятии)
// ];

// считает оценку прохождения теста по данным результатам и названию оцениваемой характеристики
function testingMark(int $testId, $stat_name, $test_result): float
{
    global $DEFAULT_TESTING_RESULTS;
    $default = $DEFAULT_TESTING_RESULTS[$testId];

    // если результат лучше идеального -> 1
    if ($test_result >= $default[$stat_name][0] and $default[$stat_name][0] > $default[$stat_name][1] or $test_result <= $default[$stat_name][0] and $default[$stat_name][0] < $default[$stat_name][1]){
        return 1;
    }
    if ($test_result >= $default[$stat_name][1] and $default[$stat_name][1] > $default[$stat_name][0] or $test_result <= $default[$stat_name][1] and $default[$stat_name][1] < $default[$stat_name][0]){
        return 0;
    }
    return 1 - abs($default[$stat_name][0] - $test_result) / max(abs($default[$stat_name][0] - $default[$stat_name][1]), 1);
}

$DEFAULT_TESTING_RESULTS = [
    6 => ['reaction_time' => [10, 200], 'reaction_time_module' => [30, 300], 
        'standart_deviation' => [10, 200], 'standart_deviation_module' => [10, 300]], 
    7 => ['reaction_time' => [20, 300], 'reaction_time_module' => [50, 500], 
    'standart_deviation' => [10, 600], 'standart_deviation_module' => [10, 650]],
    1 => ['accuracy' => [100, 0], 'reaction_time' => [300, 1000]],
    2 => ['accuracy' => [100, 0], 'reaction_time' => [500, 1000]],
    3 => ['accuracy' => [100, 0], 'reaction_time' => [550, 1000], 'mistakes' => [0, 15]],
    4 => ['accuracy' => [100, 0], 'reaction_time' => [1000, 3000]],
    5 => ['accuracy' => [100, 0], 'reaction_time' => [2000, 4000]],
    8 => ['reaction_time' => [100, 500]],
    9 => ['average_reaction_time' => [100, 500], 'max_intersection_time' => [120000, 0]],
    10 => ['attention_time' => [1000, 10000], 'standard_deviation' => [500, 10000], 'mistakes' => [0, 9]],
    11 => ['accuracy' => [24, 0], 'reaction_time' => [1, 10], 'standart_deviation' => [1, 10]],
    12 => ['accuracy' => [100, 0], 'reaction_time' => [1, 10], 'standart_deviation' => [1, 10]],
    13 => ['accuracy' => [100, 0], 'reaction_time' => [0.5, 1]],
    14 => ['accuracy' => [100, 0], 'reaction_time' => [2, 10], 'standart_deviation' => [2, 10]],
    15 => ['attempts' => [1, 7], 'standard_devision_attempts' => [1, 10], 'reaction_time' => [6, 20]],
    16 => ['accuracy' => [18, 0], 'reaction_time' => [5, 10], 'standart_deviation' => [1, 10]]
];

// считает и возвращает процент соответствия пользователя конкретному пвк
function getPiqLevel(int $userId, int $piqId): int
{
    $result = 1;
    $userResults = [];
    foreach(getPiqWeights($piqId) as $weight){
        $testId = $weight['test_id'];
        if (!array_key_exists($testId, $userResults)) {
            $userResults[$testId] = getMidUserStats($testId, $userId);
        }
        $res = $userResults[$testId][$weight['stat_name']];

        $result = $result * testingMark($testId, $weight['stat_name'], $res) * $weight['weight'];
    }

    return min(1, $result) * 100;
}

// обновление в базе данных информации о соответствии пользователя пвк
function updateUserPiqs(int $userId)
{
    foreach (REQUIERED_PIQS as $i) {
        insertUsersPiq($userId, $i, getPiqLevel($userId, $i));
    }
}

// возвращает процент соответствия пользователя конкретной профессии
// вычисляется среднее из всех пвк
function getProfessionMatch(int $userId, int $profId)
{
    global $PROFESSIONS_TO_PIQ;
    $profs_piq = $PROFESSIONS_TO_PIQ[$profId];
    $result = 0;
    $piqs_level = getPiqsLevelFromDB($userId);

    foreach ($piqs_level as $i) {
        if (in_array($i['piq_id'], $profs_piq)) {
            $result += $i['level'];
        }
    }

    return $result / count($profs_piq);
}

// чекать если чел прошел тест
function passed($userId, $testId): bool
{
    if (!getUserResults($userId, $testId)) {
        return false;
    }
    return true;
}

function passedAll($userId): bool
{
    foreach (getTests() as $test) {
        if (!passed($userId, $test['id'])) return false;
    }
    return true;
}
