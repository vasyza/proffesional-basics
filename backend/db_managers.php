<?php
set_time_limit(300);
// session_start();

require_once __DIR__ . '/config.php';


// функции для работы с бд

function getPDO(): PDO
{
    //    try {
    //        return new \PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8;dbname=' . 'testlabs', DB_USERNAME, DB_PASSWORD);
    return new \PDO('pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';', DB_USERNAME, DB_PASSWORD);
    //    } catch (\PDOException $e) {
    //        die("Ошбика подключения в бд: {$e->getMessage()}");
    //    }
}


function getUsers(): array
{
    // user['id']
    // user['name']
    // user['email']
    // user['password']
    // user['role_id']
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_USERS);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

function getUserByLogin(string $login): array|bool
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_USERS . " WHERE login = :login");
    $stmt->execute(['login' => $login]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

function getUserById(int $user_id): array|bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_USERS . " WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

function currentUser(): array|false
{
    $pdo = getPDO();

    if (!isset($_SESSION['user'])) {
        return false;
    }

    $userId = $_SESSION['user']['id'] ?? null;

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_USERS . " WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

function addUserData(string $name, string $login, int $role_id, string $password, string $birth_date, int $gender_id)
{
    $pdo = getPDO();
    $query = 'INSERT INTO ' . DB_TABLE_USERS . ' (name, login, role_id, password, birth_date, gender_id) 
        VALUES (:name, :login, :role_id, :password, :birth_date, :gender_id) 
        ON CONFLICT (login) DO UPDATE SET 
            name = EXCLUDED.name,
            role_id = EXCLUDED.role_id,
            password = EXCLUDED.password,
            birth_date = EXCLUDED.birth_date,
            gender_id = EXCLUDED.gender_id;
        ';
    $params = [
        'name' => $name,
        'login' => $login,
        'role_id' => $role_id,
        'password' => $password,
        'birth_date' => $birth_date,
        'gender_id' => $gender_id
    ];
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    // try {
    //     $stmt->execute($params);
    // } catch (\Exception $e) {
    //     die($e->getMessage());
    // }
}

function getTestById(int $test_id): array|bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_TESTS . " WHERE id = :id");
    $stmt->execute(['id' => $test_id]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

function getTests(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_TESTS . " ORDER by id ASC");
    $stmt->execute();
    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // добавить ссылки на файлы для норм перенаправления
    foreach ($results as $i => $result) {
        switch ($result['id']) {
            case 1:
            default:
                $result['href'] = 'reaction_visual.php';
                break;
            case 2:
                $result['href'] = 'reaction_audio.php';
                break;
            case 3:
                $result['href'] = 'reaction_colors.php';
                break;
            case 4:
                $result['href'] = 'reaction_visual_task.php';
                break;
            case 5:
                $result['href'] = 'reaction_audio_task.php';
                break;
            case 6:
                $result['href'] = 'reaction_move_simple.php';
                break;
            case 7:
                $result['href'] = 'reaction_move_hard.php';
                break;
            case 8:
                $result['href'] = 'analog_follow.php';
                break;
            case 9:
                $result['href'] = 'analog_chase.php';
                break;
            case 10:
                $result['href'] = 'attention_distribution.php';
                break;
            case 11:
                $result['href'] = 'attention_stability.php';
                break;
            case 12:
                $result['href'] = 'memory_short_audio.php';
                break;
            case 13:
                $result['href'] = 'memory_instant_visual.php';
                break;
            case 14:
                $result['href'] = 'thinking_analisys.php';
                break;
            case 15:
                $result['href'] = 'thinking_induction.php';
                break;
            case 16:
                $result['href'] = 'abstraction_thinking.php';
                break;
        }
        $results[$i] = $result;
    }

    return $results;
}

function getUserResults($userId, $testId): array|false
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_TESTINGS . " WHERE test_id = :testId AND user_id = :userId;");
    $stmt->bindParam(':testId', $testId, \PDO::PARAM_INT);
    $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();
    if ($results) {
        foreach ($results as $key => $result) {
            $result['statistics'] = json_decode($result['statistics'], true);
            $results[$key] = $result;
        }
        return $results;
    } else {
        return false;
    }
    //return $stmt->fetch(\PDO::FETCH_ASSOC);
}

function countUserResults($testId, $userId = null): int
{
    $pdo = getPDO();
    if ($userId == null) {
        $stmt = $pdo->prepare("SELECT count(id) FROM " . DB_TABLE_TESTINGS . " WHERE test_id = :testId;");
    } else {
        $stmt = $pdo->prepare("SELECT count(id) FROM " . DB_TABLE_TESTINGS . " WHERE test_id = :testId AND user_id = :userId;");
        $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    }
    $stmt->bindParam(':testId', $testId, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch()[0];
}

function getTestResults($testId): array|false
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_TESTINGS . " WHERE test_id = :testId");
    $stmt->bindParam(':testId', $testId, \PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();
    if ($results) {
        foreach ($results as $key => $result) {
            $result['statistics'] = json_decode($result['statistics'], true);
            $results[$key] = $result;
        }
        return $results;
    } else {
        return false;
    }
    //return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

function addTestResults($userId, $testId, $statistics, $isJson = true): void
{
    $pdo = getPDO();
    if (!$isJson) {
        // если не json, то закодировать в json
        $statisticsJson = json_encode($statistics);
    } else {
        $statisticsJson = $statistics;
    }

    $stmt = $pdo->prepare("INSERT INTO " . DB_TABLE_TESTINGS . " (user_id, test_id, testing_date, statistics) VALUES (:userId, :testId, current_timestamp, :statistics);");
    $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    $stmt->bindParam(':testId', $testId, \PDO::PARAM_INT);
    $stmt->bindParam(':statistics', $statisticsJson, \PDO::PARAM_STR);
    $stmt->execute();
}

function getMidUserStats($testId, $userId = null)
{
    $pdo = getPDO();
    $query = "SELECT statistics FROM " . DB_TABLE_TESTINGS . " WHERE test_id = :testId";
    if ($userId !== null) {
        $query .= " AND user_id = :userId";
    }

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':testId', $testId, \PDO::PARAM_INT);

    if ($userId !== null) {
        $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $totalStats = [];
    $totalCount = count($results);

    foreach ($results as $result) {
        $statistics = json_decode($result['statistics'], true);
        foreach ($statistics as $key => $value) {
            if (!isset($totalStats[$key])) {
                $totalStats[$key] = 0;
            }
            // var_dump($value);
            if ($value != "NaN") {
                $totalStats[$key] += $value;
            }
        }
    }

    $averageStats = [];
    foreach ($totalStats as $key => $value) {
        $averageStats[$key] = round($value / $totalCount, 2);
    }

    return $averageStats;
}


function getProfessions(): array|bool
{
    // professions['id']
    // professions['name']
    // professions['description']
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_PROFESSIONS . ";");
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

function getProfessionById(int $prof_id): array|bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_PROFESSIONS . " WHERE id = :id;");
    $stmt->execute(['id' => $prof_id]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

function getPiqs(): array|bool
{
    // piq['id']
    // piq['name']
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_PIQS . ";");
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

function getPiqById(int $piq_id): array|bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_PIQS . " WHERE id = :id;");
    $stmt->execute(['id' => $piq_id]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}

// Найти все ОЦЕНКИ у профессии
// (каждый эксперт делает столько оценок, сколько пвк)
function getProfRatings(int $prof_id): array|bool
{
    // rating - как вариант для инстанса ОДНОГО рейтинга у профессии, опциональная строка
    // rating['id']
    // rating['piq']
    // rating['priority'] приоритет при записи подсчитывать как приоритет / 10 (максимум можно эксперту выбрать 10 пвк)
    // rating['expert']
    // rating['date']
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_RATINGS . " WHERE profession_id = :id;");
    $stmt->execute(['id' => $prof_id]);
    $tuples = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if (!$tuples) {
        return false;
    } else {
        foreach ($tuples as $key => $tuple) {
            $tuple['piq'] = getPiqById($tuple['piq_id']);
            $tuple['expert'] = getUserById($tuple['expert_id']);
            $tuples[$key] = $tuple;
        }
    }
    return $tuples;
}


// получить оценку эксперта одной профессии
function getRatingBy($userId, $professionId): array|bool
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_RATINGS . " WHERE profession_id = :profession_id AND expert_id = :expert_id;");
    $stmt->execute(['profession_id' => $professionId, 'expert_id' => $userId]);
    $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $return;
}

// получить все оценки экспертов
function getAllRatings(): array
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_RATINGS . ";");
    $stmt->execute();
    $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $return;
}

// удалить оценки эксперта одной профессии
function deleteRatingBy($userId, $professionId)
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("DELETE FROM " . DB_TABLE_RATINGS . " WHERE profession_id = :profession_id AND expert_id = :expert_id;");
    $stmt->execute(['profession_id' => $professionId, 'expert_id' => $userId]);
}

// получить веса тестов для ПВК из бд
function getPiqWeights(int $piqId)
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_WEIGHTS . " WHERE piq_id = :piqId;");
    $stmt->bindParam(':piqId', $piqId, \PDO::PARAM_INT);
    $stmt->execute();
    $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $return;
}

// вставить данные о соответствии пользователя пвк
function insertUsersPiq(int $userId, int $piqId, int $level)
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("INSERT INTO " . DB_TABLE_PIQ_LEVEL . " (user_id, piq_id, level) VALUES (:userId, :piqId, :level) ON CONFLICT (user_id, piq_id) DO UPDATE SET level=:level;");
    $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    $stmt->bindParam(':piqId', $piqId, \PDO::PARAM_INT);
    $stmt->bindParam(':level', $level, \PDO::PARAM_INT);
    $stmt->execute();
}

// получить уровени соответствия всем ПВК из бд
function getPiqsLevelFromDB(int $userId)
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_PIQ_LEVEL . " WHERE user_id = :userId;");
    $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    $stmt->execute();
    $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    return $return;
}

// получить уровень соответствия конкретному ПВК из бд
function getOnePiqLevelFromDB(int $userId, int $piqId)
{
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_PIQ_LEVEL . " WHERE user_id = :userId AND piq_id = :piqId limit 1;");
    $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
    $stmt->bindParam(':piqId', $piqId, \PDO::PARAM_INT);
    $stmt->execute();
    $return = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $return;
}

// обновить значение соответствия ПВК для заданного пользвателя
// если $userId = 0 (по умолчанию), обновляется для всех пользователей
function updatePiqLevels(?int $userId = null): void
{
    if ($userId == -1) {
        return;
    }
    if ($userId == null) {
        for ($i = 0; $i < count(getUsers()); $i++) {
            updateUserPiqs($i);
        }
    } else {
        foreach (REQUIERED_PIQS as $piqId) {
            $level = getPiqLevel($userId, $piqId);
            insertUsersPiq($userId, $piqId, $level);
        }
    }
}

function setWeights($weights): bool
{
    // Подготовка SQL запроса
    $sql = "
            INSERT INTO " . DB_TABLE_WEIGHTS . " (piq_id, test_id, stat_name, weight)
            VALUES (:piq_id, :test_id, :stat_name, :weight) ON CONFLICT (piq_id, test_id, stat_name) DO UPDATE SET 
            weight = EXCLUDED.weight;
        ";

    // Подготовка PDO запроса
    $pdo = getPDO();
    $success = true;

    foreach ($weights as $weight) {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam('piq_id', $weight['piq_id'], \PDO::PARAM_INT);
        $stmt->bindParam('test_id', $weight['test_id'], \PDO::PARAM_INT);
        $stmt->bindParam('stat_name', $weight['stat_name']);
        $stmt->bindParam('weight', $weight['weight']);
        if (!$stmt->execute() && $success) {
            $success = false;
        };
    }
    return $success;
}

function getWeights(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TABLE_WEIGHTS);
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}