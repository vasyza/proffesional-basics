<?php
require_once 'config.php';

// Функция для выполнения SQL запроса
function executeQuery($pdo, $sql) {
    try {
        $pdo->exec($sql);
        echo "Успешно выполнен запрос: " . substr($sql, 0, 50) . "...<br>";
    } catch (PDOException $e) {
        echo "Ошибка при выполнении запроса: " . $e->getMessage() . "<br>";
    }
}

// Подключение к PostgreSQL
try {
    $pdo = getDbConnection();
    echo "Подключение к базе данных " . DB_NAME . " выполнено успешно<br>";

    // Добавление колонки 'type' в таблицу professions, если она еще не существует
    $sql = "
    DO
    $$
    BEGIN
        IF NOT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'professions' AND column_name = 'type'
        ) THEN
            ALTER TABLE professions ADD COLUMN type VARCHAR(50);
            -- Обновляем существующие записи, установив значение по умолчанию
            UPDATE professions SET type = 'ИТ-специалист' WHERE type IS NULL;
        END IF;
    END
    $$;
    ";
    executeQuery($pdo, $sql);

    // Создание таблицы group_professions, если она еще не существует
    $sql = "CREATE TABLE IF NOT EXISTS group_professions (
        id SERIAL PRIMARY KEY,
        group_id INTEGER REFERENCES student_groups(id) ON DELETE CASCADE,
        profession_id INTEGER REFERENCES professions(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);
    
    // Добавление индекса для улучшения производительности
    $sql = "CREATE INDEX IF NOT EXISTS idx_group_professions_group ON group_professions(group_id)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_group_professions_profession ON group_professions(profession_id)";
    executeQuery($pdo, $sql);

    echo "Обновление структуры базы данных завершено успешно<br>";

} catch (PDOException $e) {
    echo "Ошибка подключения к PostgreSQL: " . $e->getMessage() . "<br>";
}
?> 