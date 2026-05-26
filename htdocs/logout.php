<?php
/**
 * Выход из системы.
 *
 * Что делает:
 * - очищает сессию пользователя;
 * - возвращает его на страницу входа.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser($pdo);
session_start();
setFlashMessage('success', 'Вы вышли из системы.');

header('Location: login.php');
exit;
