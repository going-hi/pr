<?php
declare(strict_types=1);

function project_root(): string
{
    return dirname(__DIR__);
}

/**
 * Удаляет файл из uploads/recipes, если путь наш.
 */
function recipe_delete_stored_image(?string $webPath): void
{
    $p = trim((string) $webPath);
    if ($p === '' || !str_starts_with($p, '/uploads/recipes/')) {
        return;
    }
    $full = project_root() . $p;
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * Сохраняет загруженное фото рецепта. Возвращает веб-путь или null, если файла не было.
 *
 * @throws RuntimeException при ошибке валидации
 */
function recipe_save_uploaded_image(string $fieldName, int $postId): ?string
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }
    $f = $_FILES[$fieldName];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Не удалось загрузить файл.');
    }
    if (($f['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('Фото не больше 4 МБ.');
    }

    $tmp = (string) ($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Некорректная загрузка.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extMap[$mime])) {
        throw new RuntimeException('Допустимы только JPEG, PNG и WebP.');
    }
    $ext = $extMap[$mime];

    $dir = project_root() . '/uploads/recipes';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Не удалось создать папку для загрузок.');
    }

    $name = 'r_' . $postId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    return '/uploads/recipes/' . $name;
}
