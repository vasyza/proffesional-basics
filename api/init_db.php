<?php
require_once 'config.php';

// Функция для выполнения SQL запроса
function executeQuery($pdo, $sql) {
    try {
        $pdo->exec($sql);
        echo "Успешно выполнен запрос: " . substr($sql, 0, 50) . "...\n";
    } catch (PDOException $e) {
        echo "Ошибка при выполнении запроса: " . $e->getMessage() . "\n";
    }
}

// Подключение к PostgreSQL
try {
    $pdo = getDbConnection();
    echo "Подключение к базе данных " . DB_NAME . " выполнено успешно\n";

    // Тут ссылка на файл с SQL запросами но я просто уберу кнопку.

    // Добавление администратора для тестирования
    $adminLogin = 'admin';
    $adminName = 'Администратор';
    $adminPass = md5('admin123' . "hiferhifurie");;
    $adminRole = 'admin';
    
    $sql = "INSERT INTO users (login, name, pass, role) 
            VALUES (:login, :name, :pass, :role)
            ON CONFLICT (login) DO NOTHING";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':login' => $adminLogin,
        ':name' => $adminName,
        ':pass' => $adminPass,
        ':role' => $adminRole
    ]);
    
    echo "Тестовый администратор добавлен или уже существует\n";
    
    echo "Инициализация базы данных завершена успешно\n";

} catch (PDOException $e) {
    echo "Ошибка подключения к PostgreSQL: " . $e->getMessage() . "\n";
}
?>