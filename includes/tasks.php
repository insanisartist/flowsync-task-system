<?php
/**
 * Функции для работы с задачами.
 *
 * Что делает:
 * - загружает задачи, проекты и пользователей;
 * - создает и обновляет задачи;
 * - проверяет, какие статусы может менять текущий пользователь;
 * - сохраняет историю действий по задаче.
 *
 * Куда вставлять:
 * - положить файл в папку /includes.
 */

declare(strict_types=1);

function isManager(array $user): bool
{
    return ($user['role'] ?? '') === 'manager';
}

function isExecutor(array $user): bool
{
    return ($user['role'] ?? '') === 'executor';
}

function isReviewer(array $user): bool
{
    return ($user['role'] ?? '') === 'reviewer';
}

function getStatusLabel(string $status): string
{
    $labels = [
        'created' => 'Создана',
        'in_progress' => 'В работе',
        'on_review' => 'На проверке',
        'approved' => 'Утверждена',
        'needs_revision' => 'На доработке',
        'closed' => 'Закрыта',
    ];

    return $labels[$status] ?? $status;
}

function getPriorityLabel(string $priority): string
{
    $labels = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
    ];

    return $labels[$priority] ?? $priority;
}

function getStatusBadgeClass(string $status): string
{
    $classes = [
        'created' => 'secondary',
        'in_progress' => 'primary',
        'on_review' => 'warning',
        'approved' => 'success',
        'needs_revision' => 'danger',
        'closed' => 'dark',
    ];

    return $classes[$status] ?? 'secondary';
}

function getPriorityBadgeClass(string $priority): string
{
    $classes = [
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger',
    ];

    return $classes[$priority] ?? 'secondary';
}

function getProjects(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM projects ORDER BY name')->fetchAll();
}

function getUsersByRole(PDO $pdo, string $role): array
{
    $statement = $pdo->prepare('SELECT id, full_name FROM users WHERE role = :role ORDER BY full_name');
    $statement->execute(['role' => $role]);

    return $statement->fetchAll();
}

function getTaskFilters(): array
{
    return [
        'status' => trim($_GET['status'] ?? ''),
        'project_id' => trim($_GET['project_id'] ?? ''),
        'assigned_to' => trim($_GET['assigned_to'] ?? ''),
        'date_from' => trim($_GET['date_from'] ?? ''),
        'date_to' => trim($_GET['date_to'] ?? ''),
    ];
}

function getTaskFilterQueryString(array $filters): string
{
    $prepared = [];

    foreach ($filters as $key => $value) {
        if ($value !== '') {
            $prepared[$key] = $value;
        }
    }

    return http_build_query($prepared);
}

function getTasks(PDO $pdo, array $filters = []): array
{
    $sql = "
        SELECT
            t.*,
            p.name AS project_name,
            creator.full_name AS creator_name,
            executor.full_name AS executor_name,
            reviewer.full_name AS reviewer_name
        FROM tasks t
        INNER JOIN projects p ON p.id = t.project_id
        INNER JOIN users creator ON creator.id = t.created_by
        LEFT JOIN users executor ON executor.id = t.assigned_to
        LEFT JOIN users reviewer ON reviewer.id = t.reviewer_id
        WHERE 1 = 1
    ";

    $params = [];

    if (($filters['status'] ?? '') !== '') {
        $sql .= " AND t.status = :status";
        $params['status'] = $filters['status'];
    }

    if (($filters['project_id'] ?? '') !== '' && ctype_digit((string) $filters['project_id'])) {
        $sql .= " AND t.project_id = :project_id";
        $params['project_id'] = (int) $filters['project_id'];
    }

    if (($filters['assigned_to'] ?? '') !== '' && ctype_digit((string) $filters['assigned_to'])) {
        $sql .= " AND t.assigned_to = :assigned_to";
        $params['assigned_to'] = (int) $filters['assigned_to'];
    }

    if (($filters['date_from'] ?? '') !== '') {
        $sql .= " AND t.due_date >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }

    if (($filters['date_to'] ?? '') !== '') {
        $sql .= " AND t.due_date <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }

    $sql .= "
        ORDER BY
            CASE t.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                ELSE 3
            END,
            t.created_at DESC
    ";

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function getTaskById(PDO $pdo, int $taskId): ?array
{
    $sql = "
        SELECT
            t.*,
            p.name AS project_name,
            creator.full_name AS creator_name,
            executor.full_name AS executor_name,
            reviewer.full_name AS reviewer_name
        FROM tasks t
        INNER JOIN projects p ON p.id = t.project_id
        INNER JOIN users creator ON creator.id = t.created_by
        LEFT JOIN users executor ON executor.id = t.assigned_to
        LEFT JOIN users reviewer ON reviewer.id = t.reviewer_id
        WHERE t.id = :task_id
        LIMIT 1
    ";

    $statement = $pdo->prepare($sql);
    $statement->execute(['task_id' => $taskId]);
    $task = $statement->fetch();

    return $task ?: null;
}

function getTaskHistory(PDO $pdo, int $taskId): array
{
    $sql = "
        SELECT th.*, u.full_name
        FROM task_history th
        INNER JOIN users u ON u.id = th.user_id
        WHERE th.task_id = :task_id
        ORDER BY th.created_at DESC, th.id DESC
    ";

    $statement = $pdo->prepare($sql);
    $statement->execute(['task_id' => $taskId]);

    return $statement->fetchAll();
}

function addTaskHistory(PDO $pdo, int $taskId, int $userId, string $actionText, ?string $commentText = null): void
{
    $sql = 'INSERT INTO task_history (task_id, user_id, action_text, comment_text) VALUES (:task_id, :user_id, :action_text, :comment_text)';
    $statement = $pdo->prepare($sql);
    $statement->execute([
        'task_id' => $taskId,
        'user_id' => $userId,
        'action_text' => $actionText,
        'comment_text' => $commentText !== '' ? $commentText : null,
    ]);
}

function createTask(PDO $pdo, array $data, int $userId): int
{
    $sql = '
        INSERT INTO tasks (project_id, title, description, status, priority, created_by, assigned_to, reviewer_id, due_date)
        VALUES (:project_id, :title, :description, :status, :priority, :created_by, :assigned_to, :reviewer_id, :due_date)
    ';

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'project_id' => $data['project_id'],
        'title' => $data['title'],
        'description' => $data['description'],
        'status' => 'created',
        'priority' => $data['priority'],
        'created_by' => $userId,
        'assigned_to' => $data['assigned_to'] ?: null,
        'reviewer_id' => $data['reviewer_id'] ?: null,
        'due_date' => $data['due_date'] ?: null,
    ]);

    $taskId = (int) $pdo->lastInsertId();

    writeAuditLog(
        $pdo,
        $userId,
        'task_created',
        'Создана новая задача.',
        [
            'task_id' => $taskId,
            'title' => $data['title'],
            'project_id' => $data['project_id'],
            'assigned_to' => $data['assigned_to'] ?: null,
            'reviewer_id' => $data['reviewer_id'] ?: null,
        ]
    );

    return $taskId;
}

function updateTask(PDO $pdo, int $taskId, array $data, ?int $userId = null): void
{
    $sql = '
        UPDATE tasks
        SET project_id = :project_id,
            title = :title,
            description = :description,
            priority = :priority,
            assigned_to = :assigned_to,
            reviewer_id = :reviewer_id,
            due_date = :due_date
        WHERE id = :task_id
    ';

    $statement = $pdo->prepare($sql);
    $statement->execute([
        'project_id' => $data['project_id'],
        'title' => $data['title'],
        'description' => $data['description'],
        'priority' => $data['priority'],
        'assigned_to' => $data['assigned_to'] ?: null,
        'reviewer_id' => $data['reviewer_id'] ?: null,
        'due_date' => $data['due_date'] ?: null,
        'task_id' => $taskId,
    ]);

    if ($userId !== null) {
        writeAuditLog(
            $pdo,
            $userId,
            'task_updated',
            'Задача была отредактирована.',
            [
                'task_id' => $taskId,
                'title' => $data['title'],
                'project_id' => $data['project_id'],
                'assigned_to' => $data['assigned_to'] ?: null,
                'reviewer_id' => $data['reviewer_id'] ?: null,
            ]
        );
    }
}

function validateTaskForm(array $data): array
{
    $errors = [];

    if ($data['project_id'] === '') {
        $errors[] = 'Выберите проект.';
    }

    if ($data['title'] === '') {
        $errors[] = 'Введите название задачи.';
    }

    if ($data['priority'] === '') {
        $errors[] = 'Выберите приоритет.';
    }

    return $errors;
}

function getAvailableStatusOptions(array $task, array $user): array
{
    $options = [];
    $status = $task['status'];
    $taskExecutorId = (int) ($task['assigned_to'] ?? 0);
    $taskReviewerId = (int) ($task['reviewer_id'] ?? 0);
    $currentUserId = (int) ($user['id'] ?? 0);

    if (isManager($user)) {
        if ($status === 'created') {
            $options['in_progress'] = 'Перевести в работу';
        }

        if ($status === 'approved') {
            $options['closed'] = 'Закрыть задачу';
        }

        if (in_array($status, ['approved', 'closed'], true)) {
            $options['needs_revision'] = 'Вернуть на доработку';
        }
    }

    if (isExecutor($user) && $taskExecutorId === $currentUserId) {
        if (in_array($status, ['created', 'needs_revision'], true)) {
            $options['in_progress'] = 'Взять в работу';
        }

        if ($status === 'in_progress') {
            $options['on_review'] = 'Отправить на проверку';
        }
    }

    if (isReviewer($user) && $taskReviewerId === $currentUserId) {
        if ($status === 'on_review') {
            $options['approved'] = 'Утвердить';
            $options['needs_revision'] = 'Вернуть на доработку';
        }

        if (in_array($status, ['approved', 'closed'], true)) {
            $options['needs_revision'] = 'Вернуть на доработку';
        }
    }

    return $options;
}

function updateTaskStatus(PDO $pdo, int $taskId, string $newStatus, ?int $userId = null): void
{
    $statement = $pdo->prepare('UPDATE tasks SET status = :status WHERE id = :task_id');
    $statement->execute([
        'status' => $newStatus,
        'task_id' => $taskId,
    ]);

    if ($userId !== null) {
        writeAuditLog(
            $pdo,
            $userId,
            'task_status_changed',
            'Изменен статус задачи.',
            ['task_id' => $taskId, 'new_status' => $newStatus]
        );
    }
}
