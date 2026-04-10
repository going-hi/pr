<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Контакты — ' . SITE_NAME;
$activeNav = 'contacts';
$metaDescription = 'Напишите нам: вопросы по сайту, идеи рецептов и сотрудничество. Форма обратной связи «Сковородка Судьбы».';
$canonicalUrl = absolute_url('/contacts.php');

$error = '';
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $honeypot = trim((string) ($_POST['website_url'] ?? ''));
    if ($honeypot !== '') {
        redirect('/contacts.php?sent=1');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));

    if ($name === '' || mb_strlen($name, 'UTF-8') > 120) {
        $error = 'Укажите имя (до 120 символов).';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email.';
    } elseif (mb_strlen($email, 'UTF-8') > 255) {
        $error = 'Email слишком длинный.';
    } elseif (mb_strlen($subject, 'UTF-8') > 200) {
        $error = 'Тема не длиннее 200 символов.';
    } elseif (mb_strlen($body, 'UTF-8') < 10) {
        $error = 'Сообщение слишком короткое (минимум 10 символов).';
    } elseif (mb_strlen($body, 'UTF-8') > 8000) {
        $error = 'Сообщение не длиннее 8000 символов.';
    } else {
        $ins = db()->prepare(
            'INSERT INTO contact_messages (name, email, subject, body, is_read) VALUES (?, ?, ?, ?, 0)'
        );
        $subj = $subject === '' ? null : $subject;
        $ins->execute([$name, $email, $subj, $body]);
        redirect('/contacts.php?sent=1');
    }
    $sent = false;
}

require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="form-card" style="width:100%;max-width:32rem">
        <h1 style="font-size:1.75rem;margin-bottom:.5rem">Контакты</h1>
        <p style="color:var(--muted);margin-bottom:1.5rem">
            Вопросы по сайту, предложения и отзывы — заполните форму, мы прочитаем сообщение в админ-панели.
        </p>

        <?php if ($sent): ?>
            <p class="alert alert-success">Спасибо! Сообщение отправлено.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="alert alert-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group" aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">
                <label for="website_url">Не заполнять</label>
                <input type="text" id="website_url" name="website_url" value="" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="name">Ваше имя</label>
                <input id="name" name="name" type="text" required maxlength="120"
                       value="<?= e((string) ($_POST['name'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="email">Email для ответа</label>
                <input id="email" name="email" type="email" required maxlength="255" autocomplete="email"
                       value="<?= e((string) ($_POST['email'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label for="subject">Тема <span style="color:var(--muted);font-weight:400">(необязательно)</span></label>
                <input id="subject" name="subject" type="text" maxlength="200"
                       value="<?= e((string) ($_POST['subject'] ?? '')) ?>"
                       placeholder="Например: идея для раздела">
            </div>
            <div class="form-group">
                <label for="body">Сообщение</label>
                <textarea id="body" name="body" rows="6" required maxlength="8000" placeholder="Текст от 10 символов"><?= e((string) ($_POST['body'] ?? '')) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Отправить</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
