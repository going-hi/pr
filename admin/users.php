<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$admin = require_admin();

$pageTitle = 'Пользователи — админ · ' . SITE_NAME;
$activeNav = 'admin-users';

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int) ($_POST['user_id'] ?? 0);
    $newRole = (string) ($_POST['role'] ?? '');

    if ($uid <= 0 || !in_array($newRole, ['user', 'admin'], true)) {
        $flash = 'error:Некорректные данные.';
    } elseif ($uid === (int) $admin['id']) {
        $flash = 'error:Нельзя изменить роль своей учётной записи.';
    } else {
        $st = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $st->execute([$newRole, $uid]);
        $flash = 'ok:Роль обновлена.';
    }
}

$st = db()->query(
    'SELECT id, email, full_name, role, created_at FROM users ORDER BY created_at DESC'
);
$users = $st->fetchAll();

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
        <h1>Пользователи</h1>
        <p class="lead">Назначение роли «Администратор» даёт доступ к этой панели.</p>
        <div class="table-wrap">
            <table class="data">
                <thead>
                <tr>
                    <th>Email</th>
                    <th>Имя</th>
                    <th>Роль</th>
                    <th>Регистрация</th>
                    <th>Действие</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e((string) $u['email']) ?></td>
                        <td><?= e((string) $u['full_name']) ?></td>
                        <td>
                            <span class="badge <?= ($u['role'] ?? '') === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                <?= ($u['role'] ?? '') === 'admin' ? 'Админ' : 'Пользователь' ?>
                            </span>
                        </td>
                        <td><?= e(date('d.m.Y', strtotime((string) $u['created_at']))) ?></td>
                        <td>
                            <?php if ((int) $u['id'] === (int) $admin['id']): ?>
                                <span class="meta">это вы</span>
                            <?php else: ?>
                                <form method="post" action="" class="row-actions" style="margin:0">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <select name="role" onchange="this.form.submit()">
                                        <option value="user" <?= ($u['role'] ?? '') === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                        <option value="admin" <?= ($u['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
