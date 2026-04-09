<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$pageTitle = 'Личный кабинет — ' . SITE_NAME;
$activeNav = 'cabinet';

$tab = in_array($_GET['tab'] ?? '', ['recipes', 'favorites', 'settings']) ? $_GET['tab'] : 'stats';

$messages = [];
$errors   = [];

// ── Обработка форм ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile') {
        $name = trim((string) ($_POST['full_name'] ?? ''));
        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'Имя должно быть не короче 2 символов.';
        } else {
            db()->prepare('UPDATE users SET full_name=? WHERE id=?')->execute([$name, $user['id']]);
            $messages[] = 'Профиль обновлён.';
            $user = current_user() ?? $user;
        }
        $tab = 'settings';
    }

    if ($action === 'password') {
        $cur  = (string) ($_POST['current_password'] ?? '');
        $new  = (string) ($_POST['new_password'] ?? '');
        $new2 = (string) ($_POST['new_password_confirm'] ?? '');

        $row = db()->prepare('SELECT password_hash FROM users WHERE id=?');
        $row->execute([$user['id']]);
        $hash = (string) ($row->fetch()['password_hash'] ?? '');

        if (!password_verify($cur, $hash)) $errors[] = 'Текущий пароль неверен.';
        if (strlen($new) < 6) $errors[] = 'Новый пароль не короче 6 символов.';
        if ($new !== $new2)   $errors[] = 'Пароли не совпадают.';

        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash=? WHERE id=?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            $messages[] = 'Пароль изменён.';
        }
        $tab = 'settings';
    }
}

// ── Статистика ─────────────────────────────────────────────────────────────────
$pdo = db();

$myPostsCount = (int) $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id=?')
    ->execute([$user['id']]) ?: 0;
$st = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id=?');
$st->execute([$user['id']]);
$myPostsCount = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM likes l JOIN posts p ON p.id=l.post_id WHERE p.user_id=?');
$st->execute([$user['id']]);
$likesReceived = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM comments c JOIN posts p ON p.id=c.post_id WHERE p.user_id=?');
$st->execute([$user['id']]);
$commentsReceived = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id=?');
$st->execute([$user['id']]);
$favoritesCount = (int) $st->fetchColumn();

// ── Мои рецепты ────────────────────────────────────────────────────────────────
$myPosts = [];
if ($tab === 'recipes') {
    $st = $pdo->prepare(
        'SELECT p.id, p.title, p.slug, p.category, p.image_path, p.status, p.created_at,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS likes_count,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id=p.id) AS comments_count
         FROM posts p WHERE p.user_id=? ORDER BY p.created_at DESC'
    );
    $st->execute([$user['id']]);
    $myPosts = $st->fetchAll();
}

// ── Избранное ──────────────────────────────────────────────────────────────────
$favorites = [];
if ($tab === 'favorites') {
    $st = $pdo->prepare(
        "SELECT p.id, p.title, p.slug, p.category, p.image_path, p.excerpt, p.created_at,
                u.full_name AS author,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS likes_count
         FROM favorites f
         JOIN posts p ON p.id=f.post_id
         JOIN users u ON u.id=p.user_id
         WHERE f.user_id=? AND p.status = 'published'
         ORDER BY f.created_at DESC"
    );
    $st->execute([$user['id']]);
    $favorites = $st->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-center gap-2 mb-2" style="flex-wrap:wrap;justify-content:space-between">
    <div>
        <h1 style="margin-bottom:.25rem">Личный кабинет</h1>
        <p style="color:var(--muted)">Привет, <strong><?= e((string)$user['full_name']) ?></strong>!</p>
    </div>
    <a href="/recipe-create.php" class="btn btn-primary">+ Новый рецепт</a>
</div>

<?php foreach ($messages as $m): ?><p class="alert alert-success"><?= e($m) ?></p><?php endforeach; ?>
<?php foreach ($errors as $er): ?><p class="alert alert-error"><?= e($er) ?></p><?php endforeach; ?>

<!-- Статистика всегда видна -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-icon">📝</div>
        <div class="stat-card-num"><?= $myPostsCount ?></div>
        <div class="stat-card-lbl">Моих рецептов</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">❤️</div>
        <div class="stat-card-num"><?= $likesReceived ?></div>
        <div class="stat-card-lbl">Лайков получено</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">💬</div>
        <div class="stat-card-num"><?= $commentsReceived ?></div>
        <div class="stat-card-lbl">Комментариев</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">🔖</div>
        <div class="stat-card-num"><?= $favoritesCount ?></div>
        <div class="stat-card-lbl">В избранном</div>
    </div>
</div>

<!-- Вкладки -->
<div class="page-tabs">
    <a href="?tab=stats"     class="page-tab <?= $tab === 'stats'     ? 'active' : '' ?>">📊 Обзор</a>
    <a href="?tab=recipes"   class="page-tab <?= $tab === 'recipes'   ? 'active' : '' ?>">📝 Мои рецепты</a>
    <a href="?tab=favorites" class="page-tab <?= $tab === 'favorites' ? 'active' : '' ?>">🔖 Избранное</a>
    <a href="?tab=settings"  class="page-tab <?= $tab === 'settings'  ? 'active' : '' ?>">⚙️ Настройки</a>
</div>

<?php if ($tab === 'stats'): ?>
    <p style="color:var(--muted);margin-bottom:1.5rem">Посмотрите статистику или перейдите к вкладкам выше.</p>
    <?php if ($myPostsCount === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🍳</div>
            <h3>Вы ещё не добавляли рецептов</h3>
            <p>Поделитесь своим любимым рецептом с сообществом!</p>
            <a href="/recipe-create.php" class="btn btn-primary mt-2">Добавить первый рецепт</a>
        </div>
    <?php else: ?>
        <p>У вас <strong><?= $myPostsCount ?></strong> рецептов, которые получили <strong><?= $likesReceived ?></strong> лайков и <strong><?= $commentsReceived ?></strong> комментариев.</p>
    <?php endif; ?>

<?php elseif ($tab === 'recipes'): ?>
    <?php if (empty($myPosts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <h3>Рецептов нет</h3>
            <a href="/recipe-create.php" class="btn btn-primary mt-2">Добавить рецепт</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th></th><th>Название</th><th>Категория</th><th>❤️</th><th>💬</th><th>Статус</th><th>Дата</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($myPosts as $p): ?>
                    <tr>
                        <td style="width:56px">
                            <img src="<?= e(recipe_image_url(isset($p['image_path']) ? (string)$p['image_path'] : null)) ?>" alt="" width="48" height="32" style="object-fit:cover;border-radius:6px;vertical-align:middle">
                        </td>
                        <td><?= e((string)$p['title']) ?></td>
                        <td><?= category_emoji((string)($p['category'] ?? 'other')) ?> <?= e(category_label((string)($p['category'] ?? 'other'))) ?></td>
                        <td><?= (int)$p['likes_count'] ?></td>
                        <td><?= (int)$p['comments_count'] ?></td>
                        <td><span class="badge <?= e(post_status_badge_class((string)($p['status'] ?? 'published'))) ?>"><?= e(post_status_label((string)($p['status'] ?? 'published'))) ?></span></td>
                        <td><?= e(date('d.m.Y', strtotime((string)$p['created_at']))) ?></td>
                        <td class="row-actions">
                            <a href="/post.php?slug=<?= e(urlencode((string)$p['slug'])) ?>">Читать</a>
                            <a href="/recipe-edit.php?id=<?= (int)$p['id'] ?>">Изменить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'favorites'): ?>
    <?php if (empty($favorites)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔖</div>
            <h3>Нет сохранённых рецептов</h3>
            <p>Нажимайте 📄 на понравившихся рецептах, чтобы сохранить их здесь.</p>
            <a href="/recipes.php" class="btn btn-ghost mt-2">Смотреть рецепты</a>
        </div>
    <?php else: ?>
        <div class="recipe-grid">
            <?php foreach ($favorites as $p) {
                render_recipe_card($p, true);
            } ?>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'settings'): ?>
    <div class="recipe-grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
        <section class="form-card">
            <h2 style="margin-bottom:1rem;font-size:1.15rem">Профиль</h2>
            <p style="color:var(--muted);font-size:.85rem;margin-bottom:1rem">Email: <?= e((string)$user['email']) ?></p>
            <form method="post" action="">
                <input type="hidden" name="action" value="profile">
                <div class="form-group">
                    <label for="full_name">Имя</label>
                    <input id="full_name" name="full_name" required value="<?= e((string)$user['full_name']) ?>">
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Сохранить</button>
            </form>
        </section>

        <section class="form-card">
            <h2 style="margin-bottom:1rem;font-size:1.15rem">Смена пароля</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label for="cur_pass">Текущий пароль</label>
                    <input id="cur_pass" name="current_password" type="password" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="new_pass">Новый пароль</label>
                    <input id="new_pass" name="new_password" type="password" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="new_pass2">Повтор</label>
                    <input id="new_pass2" name="new_password_confirm" type="password" required minlength="6" autocomplete="new-password">
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Сменить пароль</button>
            </form>
        </section>
    </div>

    <?php if ($user['role'] === 'admin'): ?>
        <p style="margin-top:1.5rem"><a href="/admin/index.php" class="btn btn-secondary">Перейти в админ-панель</a></p>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
