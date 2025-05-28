-- Laboratory Work 7: Database Schema for PVK Assessment and Neurointerface Integration
-- This file contains all the new tables needed for Lab 7 implementation

SET client_encoding = 'UTF8';

-- 1. New Table: pvk_criteria
-- Purpose: To define specific criteria for evaluating the development level of one or more PVK
CREATE TABLE IF NOT EXISTS pvk_criteria (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. New Table: profession_to_criteria
-- Purpose: Links a profession to one or more evaluation criteria with weights
CREATE TABLE IF NOT EXISTS profession_to_criteria (
    id SERIAL PRIMARY KEY,
    profession_id INTEGER NOT NULL REFERENCES professions(id) ON DELETE CASCADE,
    criterion_id INTEGER NOT NULL REFERENCES pvk_criteria(id) ON DELETE CASCADE,
    criterion_weight FLOAT DEFAULT 1.0 CHECK (criterion_weight > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (profession_id, criterion_id)
);

-- 3. New Table: criterion_to_pvk
-- Purpose: Links a criterion to one or more PVK it helps evaluate
CREATE TABLE IF NOT EXISTS criterion_to_pvk (
    id SERIAL PRIMARY KEY,
    criterion_id INTEGER NOT NULL REFERENCES pvk_criteria(id) ON DELETE CASCADE,
    pvk_id INTEGER NOT NULL REFERENCES professional_qualities(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (criterion_id, pvk_id)
);

-- 4. New Table: criterion_test_indicators
-- Purpose: Defines which test indicators contribute to a criterion, their weights and assessment rules
CREATE TABLE IF NOT EXISTS criterion_test_indicators (
    id SERIAL PRIMARY KEY,
    criterion_id INTEGER NOT NULL REFERENCES pvk_criteria(id) ON DELETE CASCADE,
    test_type VARCHAR(50) NOT NULL,
    indicator_name VARCHAR(100) NOT NULL,
    indicator_weight FLOAT DEFAULT 1.0 CHECK (indicator_weight > 0),
    assessment_direction VARCHAR(15) NOT NULL CHECK (assessment_direction IN ('higher_is_better', 'lower_is_better')),
    cutoff_value FLOAT,
    cutoff_comparison_operator VARCHAR(2) CHECK (cutoff_comparison_operator IN ('>=', '<=', '>', '<', '==', '!=')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (criterion_id, test_type, indicator_name)
);

-- 5. New Table: physiological_recordings
-- Purpose: To store metadata about physiological recording sessions
CREATE TABLE IF NOT EXISTS physiological_recordings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    test_session_id INTEGER REFERENCES test_sessions(id) ON DELETE CASCADE,
    recording_datetime_start TIMESTAMP NOT NULL,
    recording_datetime_end TIMESTAMP,
    device_type VARCHAR(50) DEFAULT 'Unknown',
    recorded_parameters TEXT,
    file_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. New Table: physiological_data_points
-- Purpose: To store individual data points from physiological recordings
CREATE TABLE IF NOT EXISTS physiological_data_points (
    id BIGSERIAL PRIMARY KEY,
    recording_id INTEGER NOT NULL REFERENCES physiological_recordings(id) ON DELETE CASCADE,
    timestamp_offset_ms BIGINT,
    phase VARCHAR(20) NOT NULL CHECK (phase IN ('baseline_before', 'during_test', 'baseline_after')),
    parameter_type VARCHAR(50) NOT NULL,
    value FLOAT NOT NULL,
    unit VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. New Table: user_pvk_assessments
-- Purpose: To store calculated PVK development levels for users based on criteria
CREATE TABLE IF NOT EXISTS user_pvk_assessments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    profession_id INTEGER NOT NULL REFERENCES professions(id) ON DELETE CASCADE,
    pvk_id INTEGER NOT NULL REFERENCES professional_qualities(id) ON DELETE CASCADE,
    assessment_score FLOAT NOT NULL CHECK (assessment_score >= 0 AND assessment_score <= 10),
    assessment_level VARCHAR(20) NOT NULL CHECK (assessment_level IN ('low', 'below_average', 'average', 'above_average', 'high')),
    calculation_details JSONB,
    last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, profession_id, pvk_id)
);

-- Add indexes for performance optimization
CREATE INDEX IF NOT EXISTS idx_profession_criteria_profession ON profession_to_criteria(profession_id);
CREATE INDEX IF NOT EXISTS idx_profession_criteria_criterion ON profession_to_criteria(criterion_id);
CREATE INDEX IF NOT EXISTS idx_criterion_pvk_criterion ON criterion_to_pvk(criterion_id);
CREATE INDEX IF NOT EXISTS idx_criterion_pvk_pvk ON criterion_to_pvk(pvk_id);
CREATE INDEX IF NOT EXISTS idx_criterion_indicators_criterion ON criterion_test_indicators(criterion_id);
CREATE INDEX IF NOT EXISTS idx_criterion_indicators_test_type ON criterion_test_indicators(test_type);
CREATE INDEX IF NOT EXISTS idx_physio_recordings_user ON physiological_recordings(user_id);
CREATE INDEX IF NOT EXISTS idx_physio_recordings_session ON physiological_recordings(test_session_id);
CREATE INDEX IF NOT EXISTS idx_physio_data_recording ON physiological_data_points(recording_id);
CREATE INDEX IF NOT EXISTS idx_physio_data_phase ON physiological_data_points(phase);
CREATE INDEX IF NOT EXISTS idx_physio_data_parameter ON physiological_data_points(parameter_type);
CREATE INDEX IF NOT EXISTS idx_user_pvk_assessments_user ON user_pvk_assessments(user_id);
CREATE INDEX IF NOT EXISTS idx_user_pvk_assessments_profession ON user_pvk_assessments(profession_id);

-- Add comments for documentation
COMMENT ON TABLE pvk_criteria IS 'Criteria for evaluating PVK development levels';
COMMENT ON TABLE profession_to_criteria IS 'Links professions to evaluation criteria with weights';
COMMENT ON TABLE criterion_to_pvk IS 'Links criteria to specific PVK that they evaluate';
COMMENT ON TABLE criterion_test_indicators IS 'Defines test indicators and their assessment rules for criteria';
COMMENT ON TABLE physiological_recordings IS 'Metadata for physiological recording sessions';
COMMENT ON TABLE physiological_data_points IS 'Individual physiological data points';
COMMENT ON TABLE user_pvk_assessments IS 'Calculated PVK development levels for users';

-- Insert some initial criteria examples
INSERT INTO pvk_criteria (name, description) VALUES 
('Скорость реакции и принятия решений', 'Оценивает способность быстро реагировать на стимулы и принимать решения'),
('Устойчивость внимания при монотонной работе', 'Оценивает способность поддерживать внимание в течение длительного времени'),
('Точность восприятия и обработки информации', 'Оценивает точность восприятия и обработки различных видов информации'),
('Зрительно-моторная координация', 'Оценивает координацию между зрительным восприятием и двигательными реакциями'),
('Кратковременная память', 'Оценивает способность запоминать и воспроизводить информацию в кратковременной памяти'),
('Аналитическое мышление', 'Оценивает способность к логическому анализу и установлению связей')
ON CONFLICT (name) DO NOTHING;
