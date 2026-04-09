<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Все рецепты — ' . SITE_NAME;
$activeNav = 'recipes';

$cats = categories();
$activeCat = isset($_GET['category']) && array_key_exists($_GET['category'], $cats)
    ? $_GET['category'] : '';

$search = trim((string) ($_GET['q'] ?? ''));

$metaDescription = SITE_NAME . ': каталог домашних рецептов с фото и описаниями.';
if ($activeCat !== '') {
    $metaDescription .= ' Раздел «' . category_label($activeCat) . '».';
}
if ($search !== '') {
    $metaDescription .= ' Поиск: «' . $search . '».';
}
$ogImage = absolute_url('/assets/img/recipe-default.jpg');

if ($search !== '') {
    $pageTitle = 'Поиск рецептов · ' . SITE_NAME;
} elseif ($activeCat !== '') {
    $pageTitle = category_label($activeCat) . ' — рецепты · ' . SITE_NAME;
}

// Построение запроса
$where  = ["p.status = 'published'"];
$params = [];

if ($activeCat !== '') {
    $where[]  = 'p.category = ?';
    $params[] = $activeCat;
}

if ($search !== '') {
    $where[]  = '(p.title LIKE ? OR p.excerpt LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$whereClause = implode(' AND ', $where);

$st = db()->prepare(
    "SELECT p.id, p.title, p.slug, p.category, p.image_path, p.excerpt, p.created_at,
            u.full_name AS author,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
     FROM posts p JOIN users u ON u.id = p.user_id
     WHERE {$whereClause}
     ORDER BY p.created_at DESC
     LIMIT 60"
);
$st->execute($params);
$posts = $st->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
    <h1><?= $activeCat !== '' ? (e($cats[$activeCat]['emoji']) . ' ' . e($cats[$activeCat]['label'])) : 'Все рецепты' ?></h1>
    <a href="/recipe-create.php" class="btn btn-primary btn-sm">+ Добавить рецепт</a>
</div>

<!-- Поиск -->
<form method="get" action="" style="margin-bottom:1.25rem">
    <?php if ($activeCat): ?><input type="hidden" name="category" value="<?= e($activeCat) ?>"><?php endif; ?>
    <div style="display:flex;gap:.5rem;max-width:480px">
        <input name="q" placeholder="Поиск рецептов..." value="<?= e($search) ?>"
               style="flex:1;padding:.55rem .75rem;border:1.5px solid var(--border);border-radius:8px;font:inherit">
        <button class="btn btn-primary btn-sm" type="submit">Найти</button>
        <?php if ($search || $activeCat): ?>
            <a href="/recipes.php" class="btn btn-secondary btn-sm">Сброс</a>
        <?php endif; ?>
    </div>
</form>

<!-- Категории -->
<div class="category-pills">
    <a href="/recipes.php<?= $search ? '?q='.urlencode($search) : '' ?>"
       class="cat-pill <?= $activeCat === '' ? 'active' : '' ?>">🍽️ Все</a>
    <?php foreach ($cats as $slug => $cat): ?>
        <a href="/recipes.php?category=<?= e($slug) ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           class="cat-pill <?= $activeCat === $slug ? 'active' : '' ?>">
            <?= $cat['emoji'] ?> <?= e($cat['label']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Результаты -->
<?php if (empty($posts)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h3>Рецептов не найдено</h3>
        <p>Попробуйте другой запрос или посмотрите все рецепты.</p>
        <a href="/recipes.php" class="btn btn-ghost mt-2">Все рецепты</a>
    </div>
<?php else: ?>
    <p style="color:var(--muted);font-size:.875rem;margin-bottom:1.5rem">Найдено: <?= count($posts) ?></p>
    <div class="recipe-grid">
        <?php foreach ($posts as $p) {
            render_recipe_card($p, true);
        } ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
