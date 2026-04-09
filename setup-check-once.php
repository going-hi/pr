<?php
/**
 * Одноразовая диагностика на хостинге. Откройте в браузере:
 *   https://goingh0o.beget.tech/setup-check-once.php
 * Затем УДАЛИТЕ этот файл с сервера (безопасность).
 */
declare(strict_types=1);

// Буфер: иначе после echo session_start() в bootstrap выдаёт «headers already sent».
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=UTF-8');

echo "=== setup-check-once.php ===\n\n";

echo 'PHP: ' . PHP_VERSION . (version_compare(PHP_VERSION, '8.0', '>=') ? " (ok, нужен 8+)\n" : " (СЛИШКОМ СТАРЫЙ — в панели Beget включите PHP 8.1/8.2)\n");

echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? "ok\n" : "НЕТ — включите расширение PDO MySQL в панели\n");

$configPath = __DIR__ . '/config.php';
if (!is_readable($configPath)) {
    echo "config.php: НЕ НАЙДЕН или нет прав на чтение — залейте config.php в корень сайта\n";
    exit;
}
echo "config.php: читается\n";

/** @var array $config */
$config = require $configPath;
$db = $config['db'] ?? [];
echo 'DB host: ' . ($db['host'] ?? '?') . "\n";
echo 'DB name: ' . ($db['name'] ?? '?') . "\n";
echo 'DB user: ' . ($db['user'] ?? '?') . "\n";
echo 'DB pass: ' . (isset($db['pass']) && $db['pass'] !== '' ? '(задан)' : '(пустой)') . "\n";

require_once __DIR__ . '/includes/db.php';

try {
    $pdo = db_connect($config);
    $pdo->query('SELECT 1');
    echo "\nПодключение к MySQL: OK\n";
} catch (Throwable $e) {
    echo "\nПодключение к MySQL: ОШИБКА\n";
    echo $e->getMessage() . "\n";
    echo "\n(Часто: неверный host/user/pass или база не создана / sql/schema.sql не импортирован)\n";
    echo "\n--- Конец. Удалите этот файл с сервера. ---\n";
    exit;
}

echo "\n--- Таблицы (как в sql/schema.sql) ---\n";
foreach (['users', 'posts', 'likes', 'favorites', 'comments'] as $t) {
    try {
        $pdo->query('SELECT 1 FROM `' . str_replace('`', '', $t) . '` LIMIT 1');
        echo "Таблица «{$t}»: OK\n";
    } catch (Throwable $e) {
        echo "Таблица «{$t}»: НЕТ — импортируйте sql/schema.sql в phpMyAdmin\n";
        echo '  → ' . $e->getMessage() . "\n";
    }
}

echo "\n--- Колонка posts.status (без неё главная даёт HTTP 500) ---\n";
try {
    $pdo->query("SELECT status FROM posts LIMIT 1");
    echo "posts.status: OK\n";
} catch (Throwable $e) {
    echo "posts.status: ОШИБКА — в БД старая схема (было «published», нужен «status»).\n";
    echo '  → ' . $e->getMessage() . "\n";
    echo "  Исправление: заново импортировать актуальный sql/schema.sql или выполнить sql/migrate_published_to_status.sql (если ещё есть колонка published).\n";
}

echo "\n--- Как при открытии index.php (bootstrap + запрос главной) ---\n";
try {
    require __DIR__ . '/includes/bootstrap.php';
    db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    echo "bootstrap + запрос главной: OK\n";
} catch (Throwable $e) {
    echo "ОШИБКА (это и есть причина 500 на сайте):\n";
    echo $e->getMessage() . "\n";
    echo 'Файл: ' . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo "\n--- Конец. Удалите этот файл с сервера. ---\n";
ob_end_flush();
