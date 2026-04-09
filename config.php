<?php
declare(strict_types=1);

/**
 * Параметры БД: из переменных окружения (Docker) или значения по умолчанию для локального PHP.
 *
 * @var array<string, mixed>
 */
return [
    'site' => [
        'url' => getenv('SITE_URL') !== false && getenv('SITE_URL') !== ''
            ? rtrim((string) getenv('SITE_URL'), '/')
            : 'https://goingh0o.beget.tech',
    ],
    'db' => [
        'host' => getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? (string) getenv('DB_HOST') : 'localhost',
        'port' => (int) (getenv('DB_PORT') !== false && getenv('DB_PORT') !== '' ? getenv('DB_PORT') : '3306'),
        'name' => getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? (string) getenv('DB_NAME') : 'goingh0o_main',
        'user' => getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? (string) getenv('DB_USER') : 'goingh0o_main',
        'pass' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : 'goingh0o_log',
        'charset' => 'utf8mb4',
    ],
];
