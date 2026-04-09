<?php
declare(strict_types=1);

/**
 * @param array<string, mixed> $config
 * @return PDO
 */
function db_connect(array $config): PDO
{
    $c = $config['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $c['host'],
        (int) $c['port'],
        $c['name'],
        $c['charset']
    );
    $pdo = new PDO($dsn, (string) $c['user'], (string) $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]);
    return $pdo;
}

function db(): PDO
{
    global $config;
    static $pdo = null;
    if ($pdo === null) {
        $pdo = db_connect($config);
    }
    return $pdo;
}
