<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Рецепты — админ · ' . SITE_NAME;
$activeNav = 'admin-posts';

$filter = (string) ($_GET['status'] ?? 'all');
if (!in_array($filter, ['all', 'published', 'hidden', 'needs_edit'], true)) {
    $filter = 'all';
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $pid = (int) ($_POST['post_id'] ?? 0);

    if ($pid <= 0) {
        $flash = 'error:Некорректный рецепт.';
    } elseif ($action === 'delete') {
        $st = db()->prepare('SELECT image_path FROM posts WHERE id = ?');
        $st->execute([$pid]);
        $row = $st->fetch();
        if ($row) {
            recipe_delete_stored_image(isset($row['image_path']) ? (string) $row['image_path'] : null);
            db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$pid]);
            $base = '/admin/posts.php';
            $qs = [];
            if ($filter !== 'all') {
                $qs['status'] = $filter;
            }
            $qs['msg'] = 'deleted';
            redirect($base . '?' . http_build_query($qs));
        }
        $flash = 'error:Запись не найдена.';
    } elseif ($action === 'set_status') {
        $stVal = normalize_post_status((string) ($_POST['status'] ?? ''));
        if ($stVal === null) {
            $flash = 'error:Неверный статус.';
        } else {
            db()->prepare('UPDATE posts SET status = ? WHERE id = ?')->execute([$stVal, $pid]);
            $flash = 'ok:Статус обновлён.';
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $flash = 'ok:Рецепт удалён.';
}

$sql = 'SELECT p.id, p.title, p.slug, p.status, p.created_at, u.full_name AS author,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
     FROM posts p
     JOIN users u ON u.id = p.user_id';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE p.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY p.created_at DESC';

$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php
if ($flash !== '') {
    [$kind, $msg] = explode(':', $flash, 2);
    $cls = $kind === 'ok' ? 'alert-success' : 'alert-error';
    echo '<p class="alert ' . e($cls) . '">' . e($msg) . '</p>';
}
?>

<div class="admin-layout">
    <?php require dirname(__DIR__) . '/includes/admin_nav.php'; ?>
    <div>
        <h1>Рецепты</h1>
        <p class="lead">
            <a class="btn btn-primary" href="/admin/post_edit.php">Добавить рецепт</a>
        </p>

        <p class="admin-filters">
            <span style="color:var(--muted);margin-right:.5rem">Фильтр:</span>
            <a href="?status=all" class="<?= $filter === 'all' ? 'active-filter' : '' ?>">Все</a>
            <a href="?status=published" class="<?= $filter === 'published' ? 'active-filter' : '' ?>">Опубликованы</a>
            <a href="?status=hidden" class="<?= $filter === 'hidden' ? 'active-filter' : '' ?>">Скрыты</a>
            <a href="?status=needs_edit" class="<?= $filter === 'needs_edit' ? 'active-filter' : '' ?>">Нужны правки</a>
        </p>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Автор</th>
                    <th>Статус</th>
                    <th>💬</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e((string) $r['title']) ?></td>
                        <td><?= e((string) $r['author']) ?></td>
                        <td>
                            <form method="post" action="" class="inline-status-form">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="post_id" value="<?= (int) $r['id'] ?>">
                                <select name="status" onchange="this.form.submit()" aria-label="Статус рецепта">
                                    <?php foreach (post_status_values() as $sv): ?>
                                        <option value="<?= e($sv) ?>" <?= ((string) $r['status'] === $sv) ? 'selected' : '' ?>>
                                            <?= e(post_status_label($sv)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td><?= (int) $r['comments_count'] ?></td>
                        <td><?= e(date('d.m.Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td class="row-actions">
                            <a href="/admin/post_edit.php?id=<?= (int) $r['id'] ?>">Изменить</a>
                            <?php if (post_is_published((string) $r['status'])): ?>
                                <a href="/post.php?slug=<?= e(urlencode((string) $r['slug'])) ?>">На сайте</a>
                            <?php else: ?>
                                <a href="/post.php?slug=<?= e(urlencode((string) $r['slug'])) ?>">Просмотр</a>
                            <?php endif; ?>
                            <a href="/admin/comments.php?post_id=<?= (int) $r['id'] ?>">Комментарии</a>
                            <form method="post" action="" class="inline-delete-form"
                                  onsubmit="return confirm('Удалить этот рецепт?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="link-btn">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6">Нет записей.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
