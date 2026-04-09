<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Базовый URL сайта без завершающего слэша (для canonical и Open Graph).
 */
function site_public_origin(): string
{
    global $config;
    $fromConfig = trim((string) (($config['site'] ?? [])['url'] ?? ''));
    if ($fromConfig !== '') {
        return rtrim($fromConfig, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
        || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host;
}

/** Абсолютный URL: путь от корня сайта или уже абсолютный http(s). */
function absolute_url(string $path): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = '/' . ltrim($path, '/');
    return site_public_origin() . $path;
}

/** Обрезка текста для meta description (≈155–160 символов). */
function seo_meta_clamp(string $text, int $max = 158): string
{
    $t = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if (mb_strlen($t, 'UTF-8') <= $max) {
        return $t;
    }
    $cut = mb_substr($t, 0, $max - 1, 'UTF-8');
    if (($p = mb_strrpos($cut, ' ', 0, 'UTF-8')) !== false && $p > 40) {
        $cut = mb_substr($cut, 0, $p, 'UTF-8');
    }
    return rtrim($cut, ".,;:\t ") . '…';
}

function slugify(string $title, PDO $pdo, ?int $ignoreId = null): string
{
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
    $s = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $trans) ?? '');
    $s = trim($s, '-');
    if ($s === '') {
        $s = 'recipe-' . substr(dechex(crc32($title)), 0, 12);
    }
    $base = $s;
    $n = 1;
    while (true) {
        $sql = 'SELECT id FROM posts WHERE slug = ?';
        $params = [$s];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if (!$st->fetch()) {
            return $s;
        }
        $s = $base . '-' . ++$n;
    }
}

/**
 * @return array<string, string>  slug => ['emoji', 'label']
 */
function categories(): array
{
    return [
        'soups'     => ['emoji' => '🍲', 'label' => 'Супы'],
        'salads'    => ['emoji' => '🥗', 'label' => 'Салаты'],
        'hot'       => ['emoji' => '🍳', 'label' => 'Горячее'],
        'breakfast' => ['emoji' => '🥞', 'label' => 'Завтраки'],
        'bakery'    => ['emoji' => '🥐', 'label' => 'Выпечка'],
        'desserts'  => ['emoji' => '🍰', 'label' => 'Десерты'],
        'drinks'    => ['emoji' => '☕', 'label' => 'Напитки'],
        'other'     => ['emoji' => '🍽️', 'label' => 'Другое'],
    ];
}

function category_label(string $slug): string
{
    return categories()[$slug]['label'] ?? 'Другое';
}

function category_emoji(string $slug): string
{
    return categories()[$slug]['emoji'] ?? '🍽️';
}

/** @return list<string> */
function post_status_values(): array
{
    return ['published', 'hidden', 'needs_edit'];
}

function post_is_published(string $status): bool
{
    return $status === 'published';
}

/** @return array<string, string> */
function post_status_labels(): array
{
    return [
        'published' => 'Опубликован',
        'hidden' => 'Скрыт',
        'needs_edit' => 'Требует правок',
    ];
}

function post_status_label(string $status): string
{
    return post_status_labels()[$status] ?? $status;
}

function post_status_hint(string $status): string
{
    return match ($status) {
        'published' => 'Виден в каталоге и на главной; лайки и комментарии включены.',
        'hidden' => 'Не в списках; автор и админ открывают рецепт по ссылке.',
        'needs_edit' => 'Не в каталоге; автору показано, что нужно доработать текст.',
        default => '',
    };
}

function post_status_badge_class(string $status): string
{
    return match ($status) {
        'published' => 'badge-pub',
        'hidden' => 'badge-draft',
        'needs_edit' => 'badge-warn',
        default => 'badge-draft',
    };
}

function normalize_post_status(string $value): ?string
{
    return in_array($value, post_status_values(), true) ? $value : null;
}

/** Градиент карточки по категории */
function category_gradient(string $slug): string
{
    $map = [
        'soups'     => 'linear-gradient(135deg,#c0392b,#e74c3c)',
        'salads'    => 'linear-gradient(135deg,#27ae60,#2ecc71)',
        'hot'       => 'linear-gradient(135deg,#e67e22,#f39c12)',
        'breakfast' => 'linear-gradient(135deg,#8e44ad,#9b59b6)',
        'bakery'    => 'linear-gradient(135deg,#d35400,#e67e22)',
        'desserts'  => 'linear-gradient(135deg,#c0392b,#e91e63)',
        'drinks'    => 'linear-gradient(135deg,#16a085,#1abc9c)',
        'other'     => 'linear-gradient(135deg,#7f8c8d,#95a5a6)',
    ];
    return $map[$slug] ?? $map['other'];
}

/**
 * URL логотипа: приоритет у файлов, которые пользователь положил в assets/img/.
 */
function site_logo_url(): string
{
    $base = dirname(__DIR__) . '/assets/img/';
    foreach (['logo.png', 'logo.webp', 'logo.jpg', 'logo.jpeg', 'logo.svg'] as $f) {
        if (is_file($base . $f)) {
            return '/assets/img/' . $f;
        }
    }
    return '/assets/img/logo.svg';
}

/**
 * URL обложки рецепта или дефолтное фото.
 */
function recipe_image_url(?string $path): string
{
    $default = '/assets/img/recipe-default.jpg';
    $p = trim((string) $path);
    if ($p === '') {
        return $default;
    }
    if (!preg_match('#^/(assets/img/|uploads/recipes/)#', $p)) {
        return $default;
    }
    $full = dirname(__DIR__) . $p;
    return is_file($full) ? $p : $default;
}

function time_ago(string $date): string
{
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return (int)($diff / 60) . ' мин. назад';
    if ($diff < 86400) return (int)($diff / 3600) . ' ч. назад';
    if ($diff < 604800) return (int)($diff / 86400) . ' дн. назад';
    return date('d.m.Y', strtotime($date));
}

/**
 * Карточка рецепта в сетке (главная, каталог, избранное).
 *
 * @param array<string,mixed> $p
 */
function render_recipe_card(array $p, bool $showExcerpt = true): void
{
    $slug = (string) $p['slug'];
    $url = '/post.php?slug=' . urlencode($slug);
    $img = recipe_image_url(isset($p['image_path']) ? (string) $p['image_path'] : null);
    $cat = (string) ($p['category'] ?? 'other');
    ?>
    <article class="recipe-card">
        <a href="<?= e($url) ?>" class="recipe-card-media">
            <img src="<?= e($img) ?>" alt="" loading="lazy" width="480" height="300">
            <span class="recipe-card-cat-badge"><?= category_emoji($cat) ?> <?= e(category_label($cat)) ?></span>
        </a>
        <div class="recipe-card-body">
            <h3><a href="<?= e($url) ?>"><?= e((string) $p['title']) ?></a></h3>
            <?php if ($showExcerpt && !empty($p['excerpt'])): ?>
                <p class="recipe-card-excerpt"><?= e((string) $p['excerpt']) ?></p>
            <?php endif; ?>
            <div class="recipe-card-footer">
                <span class="card-meta">👤 <?= e((string) ($p['author'] ?? '—')) ?></span>
                <span class="card-meta">❤️ <?= (int) ($p['likes_count'] ?? 0) ?><?php if (isset($p['comments_count'])): ?> · 💬 <?= (int) $p['comments_count'] ?><?php endif; ?></span>
            </div>
        </div>
    </article>
    <?php
}
