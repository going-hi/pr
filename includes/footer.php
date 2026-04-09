<?php declare(strict_types=1); ?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <div class="footer-logo">
                <img class="footer-logo-img" src="<?= e(site_logo_url()) ?>" alt="" width="36" height="36">
                <?= e(SITE_NAME) ?>
            </div>
            <p class="footer-copy">Рецепты, лайки и комментарии — всё, что сулит вам сегодняшняя сковородка</p>
        </div>
        <nav class="footer-links">
            <a href="/index.php">Главная</a>
            <a href="/recipes.php">Рецепты</a>
            <a href="/register.php">Регистрация</a>
        </nav>
    </div>
</footer>
</body>
</html>
