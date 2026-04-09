<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = SITE_NAME . ' — домашние рецепты и сообщество';
$activeNav = 'home';
$noMainPadding = true;

$metaDescription = 'Главная — ' . SITE_NAME . ': тысячи идей для кухни, рецепты с фото, категории, лайки и личный кабинет. Готовьте вместе с сообществом.';
$canonicalUrl = absolute_url('/index.php');
$ogImage = absolute_url('/assets/img/hero-main.jpg');
$schemaJsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => SITE_NAME,
    'url' => site_public_origin() . '/',
    'description' => seo_meta_clamp($metaDescription),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => site_public_origin() . '/recipes.php?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
];

$totalPosts  = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$totalUsers  = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalLikes  = (int) db()->query('SELECT COUNT(*) FROM likes')->fetchColumn();

$st = db()->query(
    "SELECT p.id, p.title, p.slug, p.category, p.image_path, p.excerpt, p.created_at,
            u.full_name AS author,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count
     FROM posts p JOIN users u ON u.id = p.user_id
     WHERE p.status = 'published'
     ORDER BY p.created_at DESC
     LIMIT 6"
);
$latest = $st->fetchAll();

$heroMain = '/assets/img/hero-main.jpg';
$heroSpread = '/assets/img/hero-spread.jpg';
$shotA = '/assets/img/recipes/pasta.jpg';
$shotB = '/assets/img/recipes/salad.jpg';
$shotC = '/assets/img/recipes/brownie.jpg';

require __DIR__ . '/includes/header.php';
?>

<section class="hero hero--landing hero--photo">
    <div class="hero-photo-layer" style="background-image:url('<?= e($heroMain) ?>')" aria-hidden="true"></div>
    <div class="hero-photo-scrim" aria-hidden="true"></div>
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container hero-inner">
        <p class="hero-eyebrow">Домашняя кухня · с фото и без лишней магии</p>
        <h1 class="hero-brand">
            <span class="hero-brand-line"><?= e(SITE_NAME) ?></span>
        </h1>
        <p class="hero-sub hero-sub--wide">
            Рецепты с обложками, лайки, избранное и честные комментарии. Загрузите фото блюда — или доверьтесь
            нашей красивой заглушке, пока не выложите шедевр.
        </p>
        <div class="hero-actions">
            <a href="/recipes.php" class="btn btn-primary btn-lg">Смотреть рецепты</a>
            <?php if (!current_user()): ?>
                <a href="/register.php" class="btn btn-outline btn-lg">Присоединиться</a>
            <?php else: ?>
                <a href="/recipe-create.php" class="btn btn-outline btn-lg">+ Рецепт с фото</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="landing-strip">
    <div class="container landing-strip-inner">
        <span class="landing-strip-icon" aria-hidden="true">✦</span>
        <p>«Судьба любит тех, кто помешивает вовремя» — <em>народная мудрость у плиты</em></p>
    </div>
</div>

<div class="stats-bar">
    <div class="container stats-inner">
        <div class="stat-item">
            <div class="stat-num"><?= number_format($totalPosts, 0, ',', ' ') ?>+</div>
            <div class="stat-label">рецептов в котле</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><?= number_format($totalUsers, 0, ',', ' ') ?>+</div>
            <div class="stat-label">поваров в строю</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><?= number_format($totalLikes, 0, ',', ' ') ?>+</div>
            <div class="stat-label">огоньков симпатии</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">∞</div>
            <div class="stat-label">вариантов ужина</div>
        </div>
    </div>
</div>

<section class="section container">
    <div class="section-intro" style="margin-bottom:1.25rem">
        <h2 class="section-title">Атмосфера кухни</h2>
        <p class="section-intro-text">Настоящие кадры еды — чтобы слюнки текли ещё до открытия рецепта.</p>
    </div>
    <div class="landing-gallery">
        <figure class="landing-gallery__main">
            <img src="<?= e($heroSpread) ?>" alt="" loading="lazy" width="800" height="600">
            <figcaption>Стол, которого ждут все</figcaption>
        </figure>
        <figure>
            <img src="<?= e($shotA) ?>" alt="" loading="lazy" width="600" height="400">
            <figcaption>Горячее и шуршащее</figcaption>
        </figure>
        <figure>
            <img src="<?= e($shotB) ?>" alt="" loading="lazy" width="600" height="400">
            <figcaption>Свежесть и цвет</figcaption>
        </figure>
    </div>

    <div class="landing-features">
        <article class="landing-feature">
            <div class="landing-feature-icon">📷</div>
            <h3>Фото к рецепту</h3>
            <p>Загрузите JPEG, PNG или WebP — или оставьте пустым: подставим стильную заглушку.</p>
        </article>
        <article class="landing-feature">
            <div class="landing-feature-icon">❤️</div>
            <h3>Лайки и избранное</h3>
            <p>Сохраняйте находки в кабинете и возвращайтесь к ним перед походом в магазин.</p>
        </article>
        <article class="landing-feature">
            <div class="landing-feature-icon">💬</div>
            <h3>Комментарии</h3>
            <p>Обсуждайте пропорции и замены — сообщество подскажет лучше любой книги.</p>
        </article>
    </div>
</section>

<div class="container">
    <section class="section-sm">
        <div class="section-intro">
            <h2 class="section-title">По направлениям к плите</h2>
            <p class="section-intro-text">Выберите категорию — или сразу <a href="/recipes.php">весь каталог</a>.</p>
        </div>
        <div class="category-pills">
            <a href="/recipes.php" class="cat-pill active">🍽️ Все рецепты</a>
            <?php foreach (categories() as $slug => $cat): ?>
                <a href="/recipes.php?category=<?= e($slug) ?>" class="cat-pill"><?= $cat['emoji'] ?> <?= e($cat['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Свежее с огня</h2>
            <a href="/recipes.php" class="section-link">Вся полевая кухня →</a>
        </div>

        <?php if (empty($latest)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🥘</div>
                <h3>Пока тихо на плите</h3>
                <p>Станьте первым, кто бросит ингредиенты в общий котёл.</p>
                <a href="/recipe-create.php" class="btn btn-primary mt-2">Добавить рецепт</a>
            </div>
        <?php else: ?>
            <div class="recipe-grid">
                <?php foreach ($latest as $p) {
                    render_recipe_card($p, true);
                } ?>
            </div>
            <div style="text-align:center;margin-top:2rem">
                <a href="/recipes.php" class="btn btn-ghost">Ещё рецепты — там их целая сковорода</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="how-it-works how-it-works--brand">
    <div class="container">
        <div class="section-header" style="justify-content:center;text-align:center;margin-bottom:2.5rem">
            <h2 class="section-title" style="color:#fff">Три шага до звезды «Мишлен» в глазах соседей</h2>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Зарегистрироваться</h3>
                <p>Один аккаунт — и вы уже не гость, а свой на кухне <?= e(SITE_NAME) ?>.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Рецепт + фото</h3>
                <p>Текст, категория, снимок блюда — и можно ждать первые лайки.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Собрать аплодисменты</h3>
                <p>Лайки, закладки, комментарии — статистика в кабинете покажет, что зашло.</p>
            </div>
        </div>
        <?php if (!current_user()): ?>
            <div style="text-align:center;margin-top:2.5rem">
                <a href="/register.php" class="btn btn-primary btn-lg">Раскалить сковороду</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
