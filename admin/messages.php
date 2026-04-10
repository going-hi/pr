<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Обратная связь — админ · ' . SITE_NAME;
$activeNav = 'admin-messages';

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $mid = (int) ($_POST['message_id'] ?? 0);
    if ($mid > 0) {
        if ($action === 'delete') {
            db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$mid]);
            redirect('/admin/messages.php?msg=del');
        }
        if ($action === 'mark_unread') {
            db()->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?')->execute([$mid]);
            redirect('/admin/messages.php?msg=unread&id=' . $mid);
        }
    }
    $flash = 'error:Некорректное действие.';
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'del') {
        $flash = 'ok:Сообщение удалено.';
    } elseif ($_GET['msg'] === 'unread') {
        $flash = 'ok:Помечено как непрочитанное.';
    }
}

$id = (int) ($_GET['id'] ?? 0);
$one = null;
if ($id > 0) {
    $st = db()->prepare('SELECT * FROM contact_messages WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $one = $st->fetch();
    if ($one) {
        db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
        $one['is_read'] = 1;
    } else {
        redirect('/admin/messages.php');
    }
}

if ($one === null) {
    $st = db()->query(
        'SELECT id, name, email, subject, body, is_read, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 300'
    );
    $rows = $st->fetchAll();
}

$unreadTotal = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

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
        <?php if ($one): ?>
            <p class="lead"><a href="/admin/messages.php">← Все сообщения</a></p>
            <h1>Сообщение #<?= (int) $one['id'] ?></h1>
            <div class="card" style="margin-top:1rem">
                <p><strong>Дата:</strong> <?= e(date('d.m.Y H:i', strtotime((string) $one['created_at']))) ?></p>
                <p><strong>Имя:</strong> <?= e((string) $one['name']) ?></p>
                <p><strong>Email:</strong> <a href="mailto:<?= e((string) $one['email']) ?>"><?= e((string) $one['email']) ?></a></p>
                <?php if (trim((string) ($one['subject'] ?? '')) !== ''): ?>
                    <p><strong>Тема:</strong> <?= e((string) $one['subject']) ?></p>
                <?php endif; ?>
                <hr style="border:0;border-top:1px solid var(--border);margin:1rem 0">
                <p style="white-space:pre-wrap;margin:0"><?= e((string) $one['body']) ?></p>
            </div>
            <div class="row-actions" style="margin-top:1rem">
                <form method="post" action="" onsubmit="return confirm('Удалить сообщение?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="message_id" value="<?= (int) $one['id'] ?>">
                    <button type="submit" class="btn btn-secondary">Удалить</button>
                </form>
                <form method="post" action="">
                    <input type="hidden" name="action" value="mark_unread">
                    <input type="hidden" name="message_id" value="<?= (int) $one['id'] ?>">
                    <button type="submit" class="btn btn-outline">Как непрочитанное</button>
                </form>
            </div>
        <?php else: ?>
            <h1>Обратная связь</h1>
            <p class="lead">
                Сообщения с формы <a href="/contacts.php">«Контакты»</a>.
                <?php if ($unreadTotal > 0): ?>
                    <strong>Непрочитанных: <?= $unreadTotal ?></strong>
                <?php endif; ?>
            </p>

            <?php if ($rows === []): ?>
                <p class="alert alert-info">Пока нет ни одного сообщения.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Дата</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Тема</th>
                            <th>Текст</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr class="<?= (int) $r['is_read'] === 0 ? 'row-unread' : '' ?>">
                                <td>
                                    <?php if ((int) $r['is_read'] === 0): ?>
                                        <span class="badge-unread" title="Непрочитано">●</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d.m.Y H:i', strtotime((string) $r['created_at']))) ?></td>
                                <td><?= e((string) $r['name']) ?></td>
                                <td><a href="mailto:<?= e((string) $r['email']) ?>"><?= e((string) $r['email']) ?></a></td>
                                <td><?= e((string) ($r['subject'] ?? '') ?: '—') ?></td>
                                <td class="comment-cell"><?= e(mb_substr((string) $r['body'], 0, 120, 'UTF-8')) ?><?= mb_strlen((string) $r['body'], 'UTF-8') > 120 ? '…' : '' ?></td>
                                <td class="row-actions">
                                    <a class="link-btn" href="/admin/messages.php?id=<?= (int) $r['id'] ?>">Открыть</a>
                                    <form method="post" action="" style="display:inline" onsubmit="return confirm('Удалить?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="message_id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit" class="link-btn">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
