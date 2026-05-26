-- SQL-файл для быстрого создания базы данных дипломного проекта.
-- Как использовать локально:
-- 1. Открой phpMyAdmin в XAMPP.
-- 2. Создай базу данных с именем diplom_tasks.
-- 3. Перейди во вкладку "Импорт".
-- 4. Выбери этот файл dump.sql и нажми "Вперед".

CREATE DATABASE IF NOT EXISTS diplom_tasks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE diplom_tasks;

-- Таблица пользователей.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('manager', 'executor', 'reviewer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица проектов.
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица задач.
CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('created', 'in_progress', 'on_review', 'approved', 'needs_revision', 'closed') NOT NULL DEFAULT 'created',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    created_by INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    reviewer_id INT UNSIGNED NULL,
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_executor FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_tasks_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Таблица истории изменений по задачам.
CREATE TABLE IF NOT EXISTS task_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    action_text VARCHAR(255) NOT NULL,
    comment_text TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица журнала аудита.
CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action_type VARCHAR(100) NOT NULL,
    action_text VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    context_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Тестовые пользователи для первого входа.
-- Пароль для всех пользователей: 123456
INSERT INTO users (full_name, email, password_hash, role)
VALUES
    ('Менеджер проекта', 'manager@example.com', '$2y$10$miXRla4x5rG5iFS4c41UOeV99lkf7RB9XIMrK3YiDrmkdUPS1sl5m', 'manager'),
    ('Исполнитель задачи', 'executor@example.com', '$2y$10$miXRla4x5rG5iFS4c41UOeV99lkf7RB9XIMrK3YiDrmkdUPS1sl5m', 'executor'),
    ('Проверяющий', 'reviewer@example.com', '$2y$10$miXRla4x5rG5iFS4c41UOeV99lkf7RB9XIMrK3YiDrmkdUPS1sl5m', 'reviewer')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    role = VALUES(role);

-- Тестовый проект.
INSERT INTO projects (name, description)
VALUES ('Внутренний IT-проект', 'Демонстрационный проект для дипломной работы');
