<?php
require_once 'config.php';

try {
    $pdo = getDbConnection();
    
    // Включаем режим исключений для PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Таблица профессионально важных качеств
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS professional_qualities (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Таблица связей между профессиями и качествами
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quality_profession_relations (
            id SERIAL PRIMARY KEY,
            quality_id INTEGER NOT NULL,
            profession_id INTEGER NOT NULL,
            importance INTEGER NOT NULL DEFAULT 1,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(quality_id, profession_id),
            FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
            FOREIGN KEY (quality_id) REFERENCES professional_qualities(id) ON DELETE CASCADE
        )
    ");
    
    // Таблица оценок качеств экспертами
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS expert_quality_ratings (
            id SERIAL PRIMARY KEY,
            expert_id INTEGER NOT NULL,
            profession_id INTEGER NOT NULL,
            quality_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(expert_id, profession_id, quality_id),
            FOREIGN KEY (expert_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
            FOREIGN KEY (quality_id) REFERENCES professional_qualities(id) ON DELETE CASCADE
        )
    ");
    
    // Пример добавления нескольких качеств
    $qualities = [
        ['name' => 'Аналитическое мышление', 'category' => 'Когнитивные', 'description' => 'Способность анализировать информацию, выявлять закономерности и делать логические выводы.'],
        ['name' => 'Креативность', 'category' => 'Когнитивные', 'description' => 'Способность генерировать новые идеи и нестандартные решения.'],
        ['name' => 'Внимание к деталям', 'category' => 'Когнитивные', 'description' => 'Способность замечать и учитывать мелкие детали и нюансы.'],
        ['name' => 'Коммуникабельность', 'category' => 'Социальные', 'description' => 'Способность эффективно взаимодействовать с людьми.'],
        ['name' => 'Лидерские качества', 'category' => 'Социальные', 'description' => 'Способность вести за собой других и мотивировать команду.'],
        ['name' => 'Стрессоустойчивость', 'category' => 'Личностные', 'description' => 'Способность сохранять спокойствие и работоспособность в стрессовых ситуациях.'],
        ['name' => 'Ответственность', 'category' => 'Личностные', 'description' => 'Способность принимать ответственность за свои решения и действия.'],
        ['name' => 'Самоорганизация', 'category' => 'Личностные', 'description' => 'Способность организовывать свою работу и управлять временем.']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO professional_qualities (name, category, description) VALUES (?, ?, ?)");
    
    foreach ($qualities as $quality) {
        // Проверяем существование качества перед добавлением
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM professional_qualities WHERE name = ?");
        $checkStmt->execute([$quality['name']]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $stmt->execute([$quality['name'], $quality['category'], $quality['description']]);
        }
    }
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    echo "Таблицы ПВК успешно созданы и заполнены!";
    
} catch (PDOException $e) {
    // Откатываем транзакцию в случае ошибки, только если $pdo была определена
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo->rollBack();
    }
    
    echo "Ошибка создания таблиц ПВК: " . $e->getMessage();
    error_log("Ошибка создания таблиц ПВК: " . $e->getMessage());
}
?> 