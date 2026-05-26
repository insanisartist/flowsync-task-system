<?php
declare(strict_types=1);

/**
 * Функции логгирования и журнала аудита.
 */

const AUDIT_LOG_LIMIT = 100;

function getClientIp(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    return is_string($ip) && $ip !== '' ? $ip : 'unknown';
}

function getAuditContextAsJson(array $context): ?string
{
    if ($context === []) {
        return null;
    }

    $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $json !== false ? $json : null;
}

function writeAuditLog(PDO $pdo, ?int $userId, string $actionType, string $actionText, array $context = []): void
{
    $statement = $pdo->prepare(
        'INSERT INTO audit_log (user_id, action_type, action_text, ip_address, context_json)
         VALUES (:user_id, :action_type, :action_text, :ip_address, :context_json)'
    );
    $statement->execute([
        'user_id' => $userId,
        'action_type' => $actionType,
        'action_text' => $actionText,
        'ip_address' => getClientIp(),
        'context_json' => getAuditContextAsJson($context),
    ]);

    trimAuditLog($pdo);
}

function trimAuditLog(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();

    if ($count <= AUDIT_LOG_LIMIT) {
        return;
    }

    $overflow = $count - AUDIT_LOG_LIMIT;
    $statement = $pdo->prepare('DELETE FROM audit_log ORDER BY created_at ASC, id ASC LIMIT :overflow');
    $statement->bindValue(':overflow', $overflow, PDO::PARAM_INT);
    $statement->execute();
}

function getAuditLogs(PDO $pdo): array
{
    $sql = '
        SELECT
            al.id,
            al.user_id,
            al.action_type,
            al.action_text,
            al.ip_address,
            al.context_json,
            al.created_at,
            u.full_name,
            u.email
        FROM audit_log al
        LEFT JOIN users u ON u.id = al.user_id
        ORDER BY al.created_at DESC, al.id DESC
    ';

    return $pdo->query($sql)->fetchAll();
}

function getAuditTypeLabel(string $actionType): string
{
    $labels = [
        'login_success' => 'Успешный вход',
        'login_failed' => 'Неудачный вход',
        'logout' => 'Выход',
        'task_created' => 'Создание задачи',
        'task_updated' => 'Редактирование задачи',
        'task_status_changed' => 'Изменение статуса задачи',
        'user_created' => 'Создание пользователя',
        'project_created' => 'Создание проекта',
    ];

    return $labels[$actionType] ?? $actionType;
}
