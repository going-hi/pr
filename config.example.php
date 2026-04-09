<?php
/**
 * Скопируйте в config.php при необходимости.
 * Локально без Docker подставляются значения по умолчанию; в Docker задаётся docker-compose.yml (DB_*).
 */
declare(strict_types=1);

return [
    // Публичный URL — для canonical и Open Graph. Перекрывается переменной SITE_URL. (sitemap.xml статический.)
    'site' => [
        'url' => getenv('SITE_URL') !== false && getenv('SITE_URL') !== ''
            ? rtrim((string) getenv('SITE_URL'), '/')
            : '',
    ],
    'db' => [
        'host' => getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? (string) getenv('DB_HOST') : '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') !== false && getenv('DB_PORT') !== '' ? getenv('DB_PORT') : '3306'),
        'name' => getenv('DB_NAME') !== false && getenv('DB_NAME') !== '' ? (string) getenv('DB_NAME') : 'culinary_blog',
        'user' => getenv('DB_USER') !== false && getenv('DB_USER') !== '' ? (string) getenv('DB_USER') : 'root',
        'pass' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
];
