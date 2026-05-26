<?php
declare(strict_types=1);

/**
 * Функции для простой административной панели.
 */

function requireManager(): void
{
    $user = getCurrentUser();

    if ($user === null || !isManager($user)) {
        setFlashMessage('error', 'Доступ к административной панели есть только у менеджера.');
        header('Location: dashboard.php');
        exit;
    }
}

function getAdminStats(PDO $pdo): array
{
    return [
        'users_count' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'projects_count' => (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
        'tasks_count' => (int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),
        'open_tasks_count' => (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status <> 'closed'")->fetchColumn(),
    ];
}

function getAllUsers(PDO $pdo): array
{
    return $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC')->fetchAll();
}

function getAllProjects(PDO $pdo): array
{
    $sql = '
        SELECT
            p.id,
            p.name,
            p.description,
            p.created_at,
            COUNT(t.id) AS tasks_count
        FROM projects p
        LEFT JOIN tasks t ON t.project_id = p.id
        GROUP BY p.id, p.name, p.description, p.created_at
        ORDER BY p.created_at DESC, p.id DESC
    ';

    return $pdo->query($sql)->fetchAll();
}

function validateUserForm(PDO $pdo, array $data): array
{
    $errors = [];

    if ($data['full_name'] === '') {
        $errors[] = 'Введите ФИО пользователя.';
    }

    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email.';
    }

    if ($data['password'] === '' || mb_strlen($data['password']) < 6) {
        $errors[] = 'Пароль должен содержать минимум 6 символов.';
    }

    if (!in_array($data['role'], ['manager', 'executor', 'reviewer'], true)) {
        $errors[] = 'Выберите роль пользователя.';
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $statement->execute(['email' => $data['email']]);

    if ((int) $statement->fetchColumn() > 0) {
        $errors[] = 'Пользователь с таким email уже существует.';
    }

    return $errors;
}

function createUser(PDO $pdo, array $data): void
{
    $statement = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, :role)');
    $statement->execute([
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'role' => $data['role'],
    ]);
}

function validateProjectForm(PDO $pdo, array $data): array
{
    $errors = [];

    if ($data['name'] === '') {
        $errors[] = 'Введите название проекта.';
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE name = :name');
    $statement->execute(['name' => $data['name']]);

    if ((int) $statement->fetchColumn() > 0) {
        $errors[] = 'Проект с таким названием уже существует.';
    }

    return $errors;
}

function createProject(PDO $pdo, array $data): void
{
    $statement = $pdo->prepare('INSERT INTO projects (name, description) VALUES (:name, :description)');
    $statement->execute([
        'name' => $data['name'],
        'description' => $data['description'] !== '' ? $data['description'] : null,
    ]);
}
