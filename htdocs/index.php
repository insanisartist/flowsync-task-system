<?php
/**
 * Стартовая страница проекта.
 *
 * Что делает:
 * - проверяет, что конфиг и подключение к БД работают;
 * - показывает базовую информацию для первого запуска.
 *
 * Куда вставлять:
 * - положить файл в папку /htdocs.
 *
 * Как запустить:
 * - открыть в браузере адрес:
 *   http://localhost/diplom/htdocs/
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;
?>
