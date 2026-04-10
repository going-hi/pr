<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string|null $activeNav */
$activeNav = $activeNav ?? '';
$user = function_exists('current_user') ? current_user() : null;

$script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$baseFile = basename($script);
$privateBasenames = ['login.php', 'register.php', 'cabinet.php', 'recipe-create.php', 'recipe-edit.php', 'logout.php', 'seed.php'];
$isPrivateRoute = str_contains($script, '/admin/') || in_array($baseFile, $privateBasenames, true);

if (!isset($metaRobots) || (string) $metaRobots === '') {
    $metaRobots = $isPrivateRoute ? 'noindex, nofollow' : 'index, follow';
}

$metaDescriptionRaw = isset($metaDescription) ? trim((string) $metaDescription) : '';
if ($metaDescriptionRaw === '') {
    $metaDescriptionRaw = SITE_NAME . ' — домашние рецепты с пошаговыми инструкциями и фото. Каталог блюд, избранное, лайки и комментарии.';
}
$metaDescriptionSeo = seo_meta_clamp($metaDescriptionRaw);

$ogType = isset($ogType) ? (string) $ogType : 'website';
$ogSiteName = SITE_NAME;
$pageTitleForMeta = (string) ($pageTitle ?? SITE_NAME);

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
if (!isset($canonicalUrl) || (string) $canonicalUrl === '') {
    $canonicalUrl = site_public_origin() . $requestUri;
}

if (!isset($ogImage) || (string) $ogImage === '') {
    $ogImage = absolute_url('/assets/img/hero-main.jpg');
}

$twitterCard = isset($twitterCard) ? (string) $twitterCard : 'summary_large_image';
$metaKeywords = isset($metaKeywords) ? trim((string) $metaKeywords) : '';

$articlePublished = isset($articlePublished) ? (string) $articlePublished : '';
$articleModified = isset($articleModified) ? (string) $articleModified : '';

$schemaPayload = isset($schemaJsonLd) ? $schemaJsonLd : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=108492679', 'ym');

        ym(108492679, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/108492679" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <title><?= e($pageTitleForMeta) ?></title>
    <meta name="description" content="<?= e($metaDescriptionSeo) ?>">
    <meta name="robots" content="<?= e($metaRobots) ?>">
    <?php if ($metaKeywords !== ''): ?>
        <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta name="theme-color" content="#c2410c">

    <meta property="og:locale" content="ru_RU">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= e($pageTitleForMeta) ?>">
    <meta property="og:description" content="<?= e($metaDescriptionSeo) ?>">
    <meta property="og:site_name" content="<?= e($ogSiteName) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="<?= e($twitterCard) ?>">
    <meta name="twitter:title" content="<?= e($pageTitleForMeta) ?>">
    <meta name="twitter:description" content="<?= e($metaDescriptionSeo) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">

    <?php if ($articlePublished !== ''): ?>
        <meta property="article:published_time" content="<?= e($articlePublished) ?>">
    <?php endif; ?>
    <?php if ($articleModified !== ''): ?>
        <meta property="article:modified_time" content="<?= e($articleModified) ?>">
    <?php endif; ?>

    <link rel="icon" href="<?= e(site_logo_url()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="/assets/css/style.css">

    <?php
    if ($schemaPayload !== null) {
        $json = is_array($schemaPayload)
            ? json_encode($schemaPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
            : (string) $schemaPayload;
        if ($json !== '' && $json !== 'null'): ?>
    <script type="application/ld+json"><?= $json ?></script>
        <?php endif;
    }
    ?>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/index.php">
            <img class="logo-img" src="<?= e(site_logo_url()) ?>" alt="<?= e(SITE_NAME) ?>" width="42" height="42">
            <span class="logo-text"><?= e(SITE_NAME) ?></span>
        </a>
        <nav class="nav">
            <a href="/index.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Главная</a>
            <a href="/recipes.php" class="<?= $activeNav === 'recipes' ? 'active' : '' ?>">Рецепты</a>
            <a href="/contacts.php" class="<?= $activeNav === 'contacts' ? 'active' : '' ?>">Контакты</a>
            <?php if ($user): ?>
                <a href="/recipe-create.php" class="<?= $activeNav === 'create' ? 'active' : '' ?>">+ Добавить</a>
                <a href="/cabinet.php" class="<?= $activeNav === 'cabinet' ? 'active' : '' ?>">Кабинет</a>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a href="/admin/index.php" class="<?= str_starts_with($activeNav, 'admin') ? 'active' : '' ?>">Админ</a>
                <?php endif; ?>
                <a href="/logout.php">Выход</a>
            <?php else: ?>
                <a href="/login.php" class="<?= $activeNav === 'login' ? 'active' : '' ?>">Войти</a>
                <a href="/register.php" class="btn-nav <?= $activeNav === 'register' ? 'active' : '' ?>">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="<?= (isset($noMainPadding) && $noMainPadding) ? '' : 'main-content container' ?>">
