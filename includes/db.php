<?php
/**
 * Файл подключения к базе данных через PDO.
 *
 * Что делает:
 * - читает настройки из config.php;
 * - создает одно подключение к MySQL;
 * - включает режим исключений для удобной отладки.
 *
 * Куда вставлять:
 * - положить файл в папку /includes.
 *
 * Как использовать:
 * - подключать в нужных PHP-файлах строкой:
 *   require_once __DIR__ . '/../includes/db.php';
 * - после подключения будет доступна переменная $pdo.
 */

declare(strict_types=1);

$appConfig = require __DIR__ . '/config.php';

$dbConfig = $appConfig['db'];

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['dbname'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $exception) {
    exit('Ошибка подключения к базе данных: ' . $exception->getMessage());
}
