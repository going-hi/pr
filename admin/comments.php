<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Комментарии — админ · ' . SITE_NAME;
$activeNav = 'admin-comments';

$flash = '';

$postFilter = (int) ($_GET['post_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'delete') {
    $cid = (int) ($_POST['comment_id'] ?? 0);
    if ($cid > 0) {
        db()->prepare('DELETE FROM comments WHERE id = ?')->execute([$cid]);
        $redir = '/admin/comments.php?msg=del';
        if ($postFilter > 0) {
            $redir .= '&post_id=' . $postFilter;
        }
        redirect($redir);
    }
    $flash = 'error:Некорректный комментарий.';
}

if (isset($_GET['msg']) && $_GET['msg'] === 'del') {
    $flash = 'ok:Комментарий удалён.';
}

$sql = 'SELECT c.id, c.body, c.created_at,
        p.id AS post_id, p.title AS post_title, p.slug AS post_slug,
        u.full_name AS author_name, u.email AS author_email
     FROM comments c
     JOIN posts p ON p.id = c.post_id
     JOIN users u ON u.id = c.user_id';
$params = [];
if ($postFilter > 0) {
    $sql .= ' WHERE c.post_id = ?';
    $params[] = $postFilter;
}
$sql .= ' ORDER BY c.created_at DESC LIMIT 200';

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
        <h1>Комментарии</h1>
        <?php if ($postFilter > 0): ?>
            <p class="lead">
                Только к рецепту #<?= $postFilter ?>.
                <a href="/admin/comments.php">Показать все</a>
            </p>
        <?php else: ?>
            <p class="lead">Последние 200 комментариев. Удаление необратимо.</p>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Дата</th>
                    <th>Рецепт</th>
                    <th>Автор</th>
                    <th>Текст</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e(date('d.m.Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td>
                            <a href="/post.php?slug=<?= e(urlencode((string) $r['post_slug'])) ?>"><?= e((string) $r['post_title']) ?></a>
                        </td>
                        <td><?= e((string) $r['author_name']) ?></td>
                        <td class="comment-cell"><?= e(mb_substr((string) $r['body'], 0, 200, 'UTF-8')) ?><?= mb_strlen((string) $r['body'], 'UTF-8') > 200 ? '…' : '' ?></td>
                        <td class="row-actions">
                            <form method="post" action="" onsubmit="return confirm('Удалить комментарий?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="comment_id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="link-btn">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr><td colspan="5">Комментариев нет.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
