<?php
declare(strict_types=1);

/**
 * Выполняет SQL-файл без CREATE DATABASE / USE (для уже выбранной базы на хостинге).
 * Разбивка по `;` — комментарии вида -- и /* */ удаляются.
 *
 * @throws RuntimeException если файл не найден
 */
function install_schema_from_file(PDO $pdo, string $absolutePath): void
{
    if (!is_readable($absolutePath)) {
        throw new RuntimeException('Не найден файл схемы: ' . $absolutePath);
    }
    $sql = file_get_contents($absolutePath);
    if ($sql === false) {
        throw new RuntimeException('Не удалось прочитать: ' . $absolutePath);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql) ?? '';
    $parts = preg_split('/;\s*(?=\R|\z)/', $sql) ?: [];
    foreach ($parts as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }
        $pdo->exec($chunk);
    }
}
