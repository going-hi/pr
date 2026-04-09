<?php
declare(strict_types=1);

/**
 * Скопируйте в config.php и подставьте свои значения в дефолты или задайте переменные окружения:
 *   SITE_URL, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 * В Docker см. docker-compose.yml (DB_* и SITE_URL).
 *
 * @var array<string, mixed>
 */
return [
    'site' => [
        'url' => getenv('SITE_URL') !== false && getenv('SITE_URL') !== ''
            ? rtrim((string) getenv('SITE_URL'), '/')
            : '',
    ],
    'db' => [
        'host' => getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? (string) getenv('DB_HOST') : 'localhost',
        'port' => (int) (getenv('DB_PORT') !== false && getenv('DB_PORT') !== '' ? getenv('DB_PORT') : '3306'),
        'name' => getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? (string) getenv('DB_NAME') : 'culinary_blog',
        'user' => getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? (string) getenv('DB_USER') : 'root',
        'pass' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
];
