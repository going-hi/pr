<?php
declare(strict_types=1);

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Сковородка Судьбы');
}

if (PHP_SAPI !== 'cli') {
    ini_set('default_charset', 'UTF-8');
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configPath = dirname(__DIR__) . '/config.php';
if (!is_readable($configPath)) {
    throw new RuntimeException('Создайте config.php на основе config.example.php');
}
/** @var array $config */
$config = require $configPath;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/auth.php';
