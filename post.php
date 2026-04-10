<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    render_not_found_page('Не указан рецепт.');
}

$user = current_user();

$st = db()->prepare(
    'SELECT p.id, p.title, p.slug, p.category, p.image_path, p.excerpt, p.body, p.created_at, p.updated_at, p.user_id, p.status,
            u.full_name AS author,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
     FROM posts p JOIN users u ON u.id = p.user_id
     WHERE p.slug = ? LIMIT 1'
);
$st->execute([$slug]);
$post = $st->fetch();

if (!$post) {
    render_not_found_page('Такого рецепта нет в базе.');
}

$status = (string) ($post['status'] ?? 'published');
$isOwner = $user !== null && (int) $user['id'] === (int) $post['user_id'];
$isAdmin = $user !== null && ($user['role'] ?? '') === 'admin';
$isPublic = post_is_published($status);
if (!$isPublic && !$isOwner && !$isAdmin) {
    render_not_found_page('Рецепт недоступен или снят с публикации.');
}

$allowSocial = $isPublic;
$isLiked    = false;
$isFavorite = false;

if ($user) {
    $stl = db()->prepare('SELECT id FROM likes WHERE post_id = ? AND user_id = ?');
    $stl->execute([(int)$post['id'], (int)$user['id']]);
    $isLiked = (bool) $stl->fetch();

    $stf = db()->prepare('SELECT id FROM favorites WHERE post_id = ? AND user_id = ?');
    $stf->execute([(int)$post['id'], (int)$user['id']]);
    $isFavorite = (bool) $stf->fetch();
}

// Загружаем комментарии
$stc = db()->prepare(
    'SELECT c.id, c.body, c.created_at, u.full_name, u.email
     FROM comments c JOIN users u ON u.id = c.user_id
     WHERE c.post_id = ?
     ORDER BY c.created_at ASC'
);
$stc->execute([(int)$post['id']]);
$comments = $stc->fetchAll();

// Сохранение комментария (только для опубликованных рецептов)
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_body'])) {
    if (!$user) { redirect('/login.php'); }
    if (!$allowSocial) {
        $commentError = 'Комментарии доступны только для опубликованных рецептов.';
    } else {
        $body = trim((string) ($_POST['comment_body'] ?? ''));
        if ($body === '') {
            $commentError = 'Комментарий не может быть пустым.';
        } else {
            $ins = db()->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (?,?,?)');
            $ins->execute([(int)$post['id'], (int)$user['id'], $body]);
            redirect('/post.php?slug=' . urlencode($slug) . '#comments');
        }
    }
}

$canEdit = $user && ((int)$user['id'] === (int)$post['user_id'] || $user['role'] === 'admin');

$pageTitle = (string)$post['title'] . ' — ' . SITE_NAME;
$activeNav = 'recipes';

$descSource = trim((string)($post['excerpt'] ?? ''));
if ($descSource === '') {
    $plain = preg_replace('/\s+/u', ' ', strip_tags((string)$post['body'])) ?? '';
    $descSource = mb_substr($plain, 0, 400, 'UTF-8');
}
$metaDescription = (string)$post['title'] . ' — рецепт на ' . SITE_NAME . '. ' . $descSource;
$canonicalUrl = absolute_url('/post.php?slug=' . urlencode((string)$post['slug']));
$coverAbs = absolute_url(recipe_image_url(isset($post['image_path']) ? (string)$post['image_path'] : null));
$ogImage = $coverAbs;
$ogType = 'article';
$articlePublished = date('c', strtotime((string)$post['created_at']));
$articleModified = date('c', strtotime((string)($post['updated_at'] ?? $post['created_at'])));
if (!$isPublic) {
    $metaRobots = 'noindex, nofollow';
}
$schemaJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Recipe',
    'name' => (string)$post['title'],
    'description' => seo_meta_clamp($descSource !== '' ? $descSource : (string)$post['title']),
    'image' => [$coverAbs],
    'author' => [
        '@type' => 'Person',
        'name' => (string)$post['author'],
    ],
    'datePublished' => $articlePublished,
    'dateModified' => $articleModified,
    'recipeCategory' => category_label((string)($post['category'] ?? 'other')),
    'recipeInstructions' => seo_meta_clamp(
        preg_replace('/\s+/u', ' ', strip_tags((string)$post['body'])) ?? '',
        4000
    ),
];

require __DIR__ . '/includes/header.php';
?>

<!-- Обложка рецепта -->
<?php $cover = recipe_image_url(isset($post['image_path']) ? (string) $post['image_path'] : null); ?>
<div class="post-hero">
    <img src="<?= e($cover) ?>" alt="<?= e((string)$post['title']) ?>" width="1200" height="630">
</div>

<?php if (!$isPublic && ($isOwner || $isAdmin)): ?>
    <p class="alert alert-warn" style="margin-bottom:1.25rem">
        <strong>Статус:</strong> <?= e(post_status_label($status)) ?>.
        <?php if ($status === 'hidden'): ?>
            Рецепт скрыт от каталога и поиска.
        <?php elseif ($status === 'needs_edit'): ?>
            После правок администратор сможет снова опубликовать рецепт.
        <?php endif; ?>
    </p>
<?php endif; ?>

<div class="post-meta-bar">
    <span class="cat-badge">
        <?= category_emoji((string)($post['category'] ?? 'other')) ?>
        <?= e(category_label((string)($post['category'] ?? 'other'))) ?>
    </span>
    <span>👤 <?= e((string)$post['author']) ?></span>
    <span>🕐 <?= e(date('d.m.Y', strtotime((string)$post['created_at']))) ?></span>
    <span>❤️ <?= (int)$post['likes_count'] ?></span>
    <span>💬 <?= (int)$post['comments_count'] ?></span>
</div>

<h1 style="font-size:2rem;margin-bottom:1rem"><?= e((string)$post['title']) ?></h1>

<?php if (!empty($post['excerpt'])): ?>
    <p style="color:var(--muted);font-size:1.05rem;margin-bottom:1.5rem"><?= e((string)$post['excerpt']) ?></p>
<?php endif; ?>

<!-- Кнопки действий -->
<div class="action-bar">
    <?php if ($user && $allowSocial): ?>
        <button class="action-btn <?= $isLiked ? 'liked' : '' ?>" id="btn-like"
                data-post="<?= (int)$post['id'] ?>" data-liked="<?= $isLiked ? '1' : '0' ?>">
            <?= $isLiked ? '❤️' : '🤍' ?> <span id="like-count"><?= (int)$post['likes_count'] ?></span> лайков
        </button>
        <button class="action-btn <?= $isFavorite ? 'saved' : '' ?>" id="btn-fav"
                data-post="<?= (int)$post['id'] ?>" data-saved="<?= $isFavorite ? '1' : '0' ?>">
            <?= $isFavorite ? '🔖' : '📄' ?> <?= $isFavorite ? 'В избранном' : 'В избранное' ?>
        </button>
    <?php elseif (!$user && $allowSocial): ?>
        <a href="/login.php" class="action-btn">🤍 <?= (int)$post['likes_count'] ?> лайков</a>
        <a href="/login.php" class="action-btn">📄 В избранное</a>
    <?php elseif (!$allowSocial): ?>
        <span class="action-btn" style="opacity:.75;cursor:default">Лайки и избранное — после публикации</span>
    <?php endif; ?>

    <?php if ($canEdit): ?>
        <a href="/recipe-edit.php?id=<?= (int)$post['id'] ?>" class="btn btn-secondary btn-sm">✏️ Редактировать</a>
    <?php endif; ?>
</div>

<!-- Текст рецепта -->
<div class="post-body-content"><?= e((string)$post['body']) ?></div>

<!-- Комментарии -->
<section id="comments" style="margin-top:3rem">
    <h2 style="margin-bottom:1.5rem">Комментарии <span style="font-size:1rem;color:var(--muted);font-family:'Inter',sans-serif"><?= (int)$post['comments_count'] ?></span></h2>

    <?php if (!empty($comments)): ?>
        <div class="comment-list">
            <?php foreach ($comments as $c): ?>
                <?php
                    $name  = (string)$c['full_name'];
                    $abbr  = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    $hue   = crc32((string)$c['email']) % 360;
                    $color = "hsl({$hue},55%,50%)";
                ?>
                <div class="comment">
                    <div class="comment-header">
                        <div class="avatar" style="background:<?= e($color) ?>"><?= e($abbr) ?></div>
                        <span class="comment-author"><?= e($name) ?></span>
                        <span class="comment-time"><?= e(time_ago((string)$c['created_at'])) ?></span>
                    </div>
                    <p class="comment-body"><?= e((string)$c['body']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:var(--muted)">Комментариев пока нет. Будьте первым!</p>
    <?php endif; ?>

    <?php if ($allowSocial && $user): ?>
        <div class="comment-form">
            <h3 style="margin-bottom:1rem;font-size:1.1rem">Оставить комментарий</h3>
            <?php if ($commentError): ?>
                <p class="alert alert-error"><?= e($commentError) ?></p>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group">
                    <textarea name="comment_body" rows="4" placeholder="Ваш отзыв о рецепте..."></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Отправить</button>
            </form>
        </div>
    <?php elseif ($allowSocial): ?>
        <p style="margin-top:1.5rem"><a href="/login.php" class="btn btn-ghost">Войдите, чтобы оставить комментарий</a></p>
    <?php else: ?>
        <p style="margin-top:1.5rem;color:var(--muted)">Новые комментарии появятся после публикации рецепта.</p>
    <?php endif; ?>
</section>

<p style="margin-top:2rem"><a href="/recipes.php">← Все рецепты</a></p>

<!-- AJAX лайки / избранное -->
<script>
(function () {
    function setup(btnId, url, activeClass, stateAttr, onToggle) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const fd = new FormData();
                fd.append('post_id', btn.dataset.post);
                const res = await fetch(url, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.ok) { onToggle(btn, data); }
            } finally {
                btn.disabled = false;
            }
        });
    }

    setup('btn-like', '/actions/like.php', 'liked', 'liked', (btn, data) => {
        btn.dataset.liked = data.liked ? '1' : '0';
        btn.classList.toggle('liked', data.liked);
        btn.querySelector('#like-count').textContent = data.count;
        btn.innerHTML = (data.liked ? '❤️' : '🤍') + ' <span id="like-count">' + data.count + '</span> лайков';
    });

    setup('btn-fav', '/actions/favorite.php', 'saved', 'saved', (btn, data) => {
        btn.dataset.saved = data.saved ? '1' : '0';
        btn.classList.toggle('saved', data.saved);
        btn.textContent = data.saved ? '🔖 В избранном' : '📄 В избранное';
    });
}());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
