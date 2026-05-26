<?php
/**
 * Базовый файл инициализации приложения.
 *
 * Что делает:
 * - запускает PHP-сессию;
 * - подключает конфиг и БД;
 * - дает доступ к общим настройкам во всех страницах.
 *
 * Куда вставлять:
 * - положить файл в папку /includes.
 *
 * Как использовать:
 * - подключать в начале каждой страницы:
 *   require_once __DIR__ . '/../includes/bootstrap.php';
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appConfig = require __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';
