<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (current_user()) { redirect('/cabinet.php'); }

$pageTitle = 'Вход — ' . SITE_NAME;
$activeNav = 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $st    = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $row = $st->fetch();
    if (!$row || !password_verify($pass, (string)$row['password_hash'])) {
        $error = 'Неверный email или пароль.';
    } else {
        login_user((int)$row['id']);
        redirect('/cabinet.php');
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="form-card" style="width:100%">
        <h1 style="font-size:1.75rem;margin-bottom:.5rem">Вход</h1>
        <p style="color:var(--muted);margin-bottom:1.5rem">Рады видеть вас снова!</p>

        <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autocomplete="username"
                       value="<?= e((string)($_POST['email'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary" type="submit" style="width:100%">Войти</button>
        </form>
        <p style="text-align:center;margin-top:1.25rem;color:var(--muted);font-size:.9rem">
            Нет аккаунта? <a href="/register.php">Зарегистрируйтесь</a>
        </p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
