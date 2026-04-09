<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Регистрация — ' . SITE_NAME;
$activeNav = 'register';
$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $name  = trim((string) ($_POST['full_name'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password_confirm'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Укажите корректный email.';
    if (mb_strlen($name) < 2)  $errors[] = 'Имя не короче 2 символов.';
    if (strlen($pass) < 6)     $errors[] = 'Пароль не короче 6 символов.';
    if ($pass !== $pass2)      $errors[] = 'Пароли не совпадают.';

    if (!$errors) {
        $chk = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'Пользователь с таким email уже существует.';
    }

    if (!$errors) {
        db()->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?,?,?)')
            ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $name]);
        $ok = true;
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="form-card" style="width:100%">
        <h1 style="font-size:1.75rem;margin-bottom:.5rem">Регистрация</h1>
        <p style="color:var(--muted);margin-bottom:1.5rem">Присоединяйтесь к сообществу поваров</p>

        <?php if ($ok): ?>
            <p class="alert alert-success">🎉 Регистрация прошла успешно! <a href="/login.php">Войдите</a>, чтобы начать.</p>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><p class="alert alert-error"><?= e($err) ?></p><?php endforeach; ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email"
                           value="<?= e((string)($_POST['email'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label for="full_name">Имя</label>
                    <input id="full_name" name="full_name" required autocomplete="name"
                           value="<?= e((string)($_POST['full_name'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Повтор пароля</label>
                    <input id="password_confirm" name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%">Создать аккаунт</button>
            </form>
            <p style="text-align:center;margin-top:1.25rem;color:var(--muted);font-size:.9rem">
                Уже есть аккаунт? <a href="/login.php">Войти</a>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
