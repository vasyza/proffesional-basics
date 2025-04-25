\c opd;

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    login VARCHAR(90) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    pass VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user', -- user, admin, expert, consultant
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS professions (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    type VARCHAR(50) DEFAULT 'Не указана' NOT NULL,
    description TEXT NOT NULL,
    skills TEXT DEFAULT 'Не указаны',
    salary_range VARCHAR(100),
    demand_level INTEGER, -- от 1 до 5
    image_path VARCHAR(255),
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS expert_ratings (
    id SERIAL PRIMARY KEY,
    profession_id INTEGER REFERENCES professions(id),
    expert_id INTEGER REFERENCES users(id),
    rating INTEGER NOT NULL, -- от 1 до 5
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS consultations (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    consultant_id INTEGER REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'pending', -- pending, accepted, completed, cancelled
    topic VARCHAR(255) NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_at TIMESTAMP,
    completed_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_groups (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS group_members (
    id SERIAL PRIMARY KEY,
    group_id INTEGER REFERENCES student_groups(id),
    user_id INTEGER REFERENCES users(id),
    role VARCHAR(50) NOT NULL, -- leader, developer, designer, etc.
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_login ON users(login);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_professions_title ON professions(title);
CREATE INDEX IF NOT EXISTS idx_consultations_user ON consultations(user_id);
CREATE INDEX IF NOT EXISTS idx_consultations_consultant ON consultations(consultant_id);

COMMENT ON TABLE users IS 'Таблица пользователей системы';
COMMENT ON COLUMN users.id IS 'Уникальный идентификатор пользователя';
COMMENT ON COLUMN users.login IS 'Логин пользователя (уникальный)';
COMMENT ON COLUMN users.name IS 'Имя пользователя';
COMMENT ON COLUMN users.pass IS 'Хешированный пароль пользователя';
COMMENT ON COLUMN users.role IS 'Роль пользователя (user, admin, expert, consultant)';
COMMENT ON COLUMN users.created_at IS 'Дата и время регистрации пользователя';

COMMENT ON TABLE professions IS 'Таблица профессий в ИТ';
COMMENT ON TABLE expert_ratings IS 'Оценки профессий от экспертов';
COMMENT ON TABLE consultations IS 'Консультации между пользователями и консультантами';
COMMENT ON TABLE student_groups IS 'Рабочие группы студентов';
COMMENT ON TABLE group_members IS 'Участники рабочих групп с указанием ролей';
