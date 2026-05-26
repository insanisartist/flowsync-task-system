<?php
/**
 * Функции авторизации и проверки ролей.
 *
 * Что делает:
 * - выполняет вход по email и паролю;
 * - хранит данные пользователя в сессии;
 * - защищает страницы от неавторизованного доступа.
 *
 * Куда вставлять:
 * - положить файл в папку /includes.
 */

declare(strict_types=1);

function getCurrentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return getCurrentUser() !== null;
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Сначала войдите в систему.';
        header('Location: login.php');
        exit;
    }
}

function requireGuest(): void
{
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

function attemptLogin(PDO $pdo, string $email, string $password): bool
{
    $sql = 'SELECT id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1';
    $statement = $pdo->prepare($sql);
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        writeAuditLog(
            $pdo,
            null,
            'login_failed',
            'Неудачная попытка входа в систему.',
            ['email' => $email]
        );
        return false;
    }

    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    writeAuditLog(
        $pdo,
        (int) $user['id'],
        'login_success',
        'Пользователь успешно вошел в систему.',
        ['email' => $user['email'], 'role' => $user['role']]
    );

    return true;
}

function logoutUser(PDO $pdo): void
{
    $user = getCurrentUser();

    if ($user !== null) {
        writeAuditLog(
            $pdo,
            (int) $user['id'],
            'logout',
            'Пользователь вышел из системы.',
            ['email' => $user['email'], 'role' => $user['role']]
        );
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function getRoleLabel(string $role): string
{
    $labels = [
        'manager' => 'Менеджер',
        'executor' => 'Исполнитель',
        'reviewer' => 'Проверяющий',
    ];

    return $labels[$role] ?? $role;
}

function setFlashMessage(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

function getFlashMessage(string $type): ?string
{
    $key = 'flash_' . $type;

    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $message;
}
