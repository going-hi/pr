<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Админ-панель — ' . SITE_NAME;
$activeNav = 'admin';

$pdo = db();
$usersCount     = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$postsCount     = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$publishedCount = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$hiddenCount    = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'hidden'")->fetchColumn();
$needsEditCount = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'needs_edit'")->fetchColumn();
$commentsCount  = (int) $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
$messagesCount  = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
$unreadMsgCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

require dirname(__DIR__) . '/includes/header.php';
?>
<div class="admin-layout">
    <?php require dirname(__DIR__) . '/includes/admin_nav.php'; ?>
    <div>
        <h1>Админ-панель</h1>
        <p class="lead">Управление контентом, модерация и пользователи.</p>
        <div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
            <div class="card">
                <strong>Пользователей</strong>
                <p class="admin-stat-num"><?= $usersCount ?></p>
            </div>
            <div class="card">
                <strong>Всего рецептов</strong>
                <p class="admin-stat-num"><?= $postsCount ?></p>
            </div>
            <div class="card">
                <strong>Опубликовано</strong>
                <p class="admin-stat-num"><?= $publishedCount ?></p>
            </div>
            <div class="card">
                <strong>Скрыто</strong>
                <p class="admin-stat-num"><?= $hiddenCount ?></p>
            </div>
            <div class="card">
                <strong>Нужны правки</strong>
                <p class="admin-stat-num"><?= $needsEditCount ?></p>
            </div>
            <div class="card">
                <strong>Комментариев</strong>
                <p class="admin-stat-num"><?= $commentsCount ?></p>
            </div>
            <div class="card">
                <strong>Сообщений с формы</strong>
                <p class="admin-stat-num"><?= (int) $messagesCount ?><?php if ($unreadMsgCount > 0): ?> <span style="font-size:.85rem;color:var(--primary)">(<?= (int) $unreadMsgCount ?> нов.)</span><?php endif; ?></p>
            </div>
        </div>
        <p style="margin-top:1.5rem" class="row-actions">
            <a class="btn btn-primary" href="/admin/posts.php">Все рецепты</a>
            <a class="btn btn-secondary" href="/admin/comments.php">Модерация комментариев</a>
            <a class="btn btn-secondary" href="/admin/messages.php">Обратная связь<?= $unreadMsgCount > 0 ? ' (' . $unreadMsgCount . ')' : '' ?></a>
            <a class="btn btn-secondary" href="/admin/users.php">Пользователи</a>
        </p>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
