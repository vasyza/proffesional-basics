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

    // Таблица пользователей
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        login VARCHAR(90) NOT NULL UNIQUE,
        name VARCHAR(50) NOT NULL,
        pass VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Таблица профессий в ИТ
    $sql = "CREATE TABLE IF NOT EXISTS professions (
        id SERIAL PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        skills TEXT NOT NULL,
        salary_range VARCHAR(100),
        demand_level INTEGER,
        image_path VARCHAR(255),
        type VARCHAR(50) DEFAULT 'ИТ-специалист',
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Таблица оценок профессий экспертами
    $sql = "CREATE TABLE IF NOT EXISTS expert_ratings (
        id SERIAL PRIMARY KEY,
        profession_id INTEGER REFERENCES professions(id),
        expert_id INTEGER REFERENCES users(id),
        rating INTEGER NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Таблица консультаций
    $sql = "CREATE TABLE IF NOT EXISTS consultations (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        consultant_id INTEGER REFERENCES users(id),
        status VARCHAR(20) DEFAULT 'pending',
        topic VARCHAR(255) NOT NULL,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        scheduled_at TIMESTAMP,
        completed_at TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Таблица рабочих групп студентов
    $sql = "CREATE TABLE IF NOT EXISTS student_groups (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Таблица участников рабочих групп с ролями
    $sql = "CREATE TABLE IF NOT EXISTS group_members (
        id SERIAL PRIMARY KEY,
        group_id INTEGER REFERENCES student_groups(id),
        user_id INTEGER REFERENCES users(id),
        role VARCHAR(50) NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Таблица профессий, связанных с группами
    $sql = "CREATE TABLE IF NOT EXISTS group_professions (
        id SERIAL PRIMARY KEY,
        group_id INTEGER REFERENCES student_groups(id) ON DELETE CASCADE,
        profession_id INTEGER REFERENCES professions(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($pdo, $sql);

    // Создание индексов
    $sql = "CREATE INDEX IF NOT EXISTS idx_users_login ON users(login)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_professions_title ON professions(title)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_consultations_user ON consultations(user_id)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_consultations_consultant ON consultations(consultant_id)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_group_professions_group ON group_professions(group_id)";
    executeQuery($pdo, $sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_group_professions_profession ON group_professions(profession_id)";
    executeQuery($pdo, $sql);

    // Добавление администратора для тестирования
    $adminLogin = 'admin';
    $adminName = 'Администратор';
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
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