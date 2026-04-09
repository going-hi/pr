<?php
declare(strict_types=1);
/** @var string $activeNav */
?>
<aside class="admin-nav">
    <a href="/admin/index.php" class="<?= $activeNav === 'admin' ? 'active' : '' ?>">Обзор</a>
    <a href="/admin/posts.php" class="<?= str_starts_with($activeNav, 'admin-posts') ? 'active' : '' ?>">Рецепты</a>
    <a href="/admin/comments.php" class="<?= $activeNav === 'admin-comments' ? 'active' : '' ?>">Комментарии</a>
    <a href="/admin/users.php" class="<?= $activeNav === 'admin-users' ? 'active' : '' ?>">Пользователи</a>
    <a href="/index.php">На сайт</a>
</aside>
