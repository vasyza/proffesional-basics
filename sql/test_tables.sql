-- Таблица для хранения сессий тестирования
CREATE TABLE IF NOT EXISTS test_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    test_type VARCHAR(50) NOT NULL,
    average_time FLOAT,
    accuracy FLOAT,
    created_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица для хранения результатов попыток в рамках сессии тестирования
CREATE TABLE IF NOT EXISTS test_attempts (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL,
    trial_number INTEGER NOT NULL,
    stimulus_value VARCHAR(255),
    response_value VARCHAR(255),
    reaction_time INTEGER,
    is_correct SMALLINT,
    FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE
);

-- Таблица для хранения приглашений на тестирование
CREATE TABLE IF NOT EXISTS test_invitations (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    group_id INTEGER,
    created_by INTEGER NOT NULL,
    is_completed SMALLINT DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица для привязки типов тестов к приглашению
CREATE TABLE IF NOT EXISTS invitation_test_types (
    id SERIAL PRIMARY KEY,
    invitation_id INTEGER NOT NULL,
    test_type VARCHAR(50) NOT NULL,
    sequence_order INTEGER,
    FOREIGN KEY (invitation_id) REFERENCES test_invitations(id) ON DELETE CASCADE
);

-- Таблица для связи между приглашениями и пройденными тестами
CREATE TABLE IF NOT EXISTS invitation_tests (
    id SERIAL PRIMARY KEY,
    invitation_id INTEGER NOT NULL,
    test_session_id INTEGER NOT NULL,
    FOREIGN KEY (invitation_id) REFERENCES test_invitations(id) ON DELETE CASCADE,
    FOREIGN KEY (test_session_id) REFERENCES test_sessions(id) ON DELETE CASCADE
);

-- Таблица для хранения нормативных значений по тестам с учетом пола и возраста
CREATE TABLE IF NOT EXISTS test_norms (
    id SERIAL PRIMARY KEY,
    test_type VARCHAR(50) NOT NULL,
    gender VARCHAR(10),
    age_min INTEGER,
    age_max INTEGER,
    norm_value_min FLOAT,
    norm_value_avg FLOAT,
    norm_value_max FLOAT,
    sample_size INTEGER,
    updated_at TIMESTAMP
);

-- Индексы для ускорения запросов
CREATE INDEX idx_test_sessions_user_id ON test_sessions(user_id);
CREATE INDEX idx_test_sessions_test_type ON test_sessions(test_type);
CREATE INDEX idx_test_attempts_session_id ON test_attempts(session_id);
CREATE INDEX idx_invitation_tests_invitation_id ON invitation_tests(invitation_id);
CREATE INDEX idx_test_invitations_user_id ON test_invitations(user_id);
CREATE INDEX idx_test_invitations_completed ON test_invitations(is_completed); 