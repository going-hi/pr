<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$admin = require_admin();

$pageTitle = 'Редактор рецепта — ' . SITE_NAME;
$activeNav = 'admin-posts';

$cats = categories();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = null;
if ($id > 0) {
    $st = db()->prepare('SELECT id, title, slug, category, image_path, excerpt, body, status FROM posts WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $post = $st->fetch();
    if (!$post) {
        render_not_found_page('Запись не найдена.');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete' && $post) {
        recipe_delete_stored_image(isset($post['image_path']) ? (string) $post['image_path'] : null);
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute([(int) $post['id']]);
        redirect('/admin/posts.php');
    }

    $title     = trim((string) ($_POST['title'] ?? ''));
    $category  = (string) ($_POST['category'] ?? '');
    $excerpt   = trim((string) ($_POST['excerpt'] ?? ''));
    $body      = trim((string) ($_POST['body'] ?? ''));
    $statusPost = normalize_post_status((string) ($_POST['status'] ?? 'published')) ?? 'published';
    $removeImg = isset($_POST['remove_image']);

    if ($category !== '' && !array_key_exists($category, $cats)) {
        $errors[] = 'Неверная категория.';
    }
    if ($title === '') {
        $errors[] = 'Укажите заголовок.';
    }
    if ($body === '') {
        $errors[] = 'Введите текст рецепта.';
    }

    if (!$errors) {
        $pdo = db();
        if ($post) {
            $imagePath = isset($post['image_path']) ? (string) $post['image_path'] : null;
            try {
                if ($removeImg) {
                    recipe_delete_stored_image($imagePath);
                    $imagePath = null;
                }
                $uploaded = recipe_save_uploaded_image('recipe_image', (int) $post['id']);
                if ($uploaded !== null) {
                    recipe_delete_stored_image($imagePath);
                    $imagePath = $uploaded;
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }

            if (!$errors) {
                $slug = slugify($title, $pdo, (int) $post['id']);
                $up = $pdo->prepare(
                    'UPDATE posts SET title = ?, slug = ?, category = ?, image_path = ?, excerpt = ?, body = ?, status = ? WHERE id = ?'
                );
                $up->execute([
                    $title,
                    $slug,
                    $category !== '' ? $category : null,
                    $imagePath,
                    $excerpt ?: null,
                    $body,
                    $statusPost,
                    $post['id'],
                ]);
                redirect('/admin/post_edit.php?id=' . (int) $post['id'] . '&saved=1');
            }
        } else {
            $slug = slugify($title, $pdo);
            $ins = $pdo->prepare(
                'INSERT INTO posts (user_id, title, slug, category, image_path, excerpt, body, status) VALUES (?, ?, ?, ?, NULL, ?, ?, ?)'
            );
            $ins->execute([
                (int) $admin['id'],
                $title,
                $slug,
                $category !== '' ? $category : null,
                $excerpt ?: null,
                $body,
                $statusPost,
            ]);
            $newId = (int) $pdo->lastInsertId();
            try {
                $path = recipe_save_uploaded_image('recipe_image', $newId);
                if ($path !== null) {
                    $pdo->prepare('UPDATE posts SET image_path = ? WHERE id = ?')->execute([$path, $newId]);
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
            if (!$errors) {
                redirect('/admin/post_edit.php?id=' . $newId . '&saved=1');
            }
        }
    }
}

$saved = isset($_GET['saved']);

$curStatus = (string) ($post['status'] ?? 'published');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curStatus = normalize_post_status((string) ($_POST['status'] ?? 'published')) ?? 'published';
}

$previewUrl = $post ? recipe_image_url(isset($post['image_path']) ? (string) $post['image_path'] : null) : '';

require dirname(__DIR__) . '/includes/header.php';
?>
<div class="admin-layout">
    <?php require dirname(__DIR__) . '/includes/admin_nav.php'; ?>
    <div>
        <h1><?= $post ? 'Редактирование рецепта' : 'Новый рецепт' ?></h1>

        <?php if ($saved): ?>
            <p class="alert alert-success">Сохранено.</p>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <p class="alert alert-error"><?= e($err) ?></p>
        <?php endforeach; ?>

        <?php if ($post && $previewUrl !== ''): ?>
            <p style="margin-bottom:1rem"><img src="<?= e($previewUrl) ?>" alt="" style="max-height:180px;border-radius:8px;object-fit:cover"></p>
        <?php endif; ?>

        <form class="form" method="post" action="" style="max-width:720px" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <div class="form-group">
                <label for="recipe_image"><?= $post ? 'Фото блюда' : 'Фото блюда (по желанию)' ?></label>
                <input id="recipe_image" name="recipe_image" type="file" accept="image/jpeg,image/png,image/webp">
                <span class="hint">До 4 МБ, JPEG / PNG / WebP</span>
            </div>
            <?php if ($post && !empty($post['image_path'])): ?>
                <div class="form-group">
                    <label style="font-weight:500;cursor:pointer">
                        <input type="checkbox" name="remove_image" value="1"> Удалить фото
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Заголовок</label>
                <input id="title" name="title" required value="<?= e((string) ($post['title'] ?? $_POST['title'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label for="category">Категория</label>
                <select id="category" name="category">
                    <option value="">— нет —</option>
                    <?php foreach ($cats as $slug => $cat): ?>
                        <option value="<?= e($slug) ?>" <?= (($post['category'] ?? ($_POST['category'] ?? '')) === $slug) ? 'selected' : '' ?>>
                            <?= $cat['emoji'] ?> <?= e($cat['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="excerpt">Краткое описание (для списка)</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?= e((string) ($post['excerpt'] ?? $_POST['excerpt'] ?? '')) ?></textarea>
            </div>
            <div class="form-group">
                <label for="body">Текст рецепта</label>
                <textarea id="body" name="body" rows="14" required><?= e((string) ($post['body'] ?? $_POST['body'] ?? '')) ?></textarea>
            </div>
            <div class="form-group">
                <label for="post_status">Статус на сайте</label>
                <select id="post_status" name="status">
                    <?php foreach (post_status_values() as $sv): ?>
                        <option value="<?= e($sv) ?>" <?= $curStatus === $sv ? 'selected' : '' ?>>
                            <?= e(post_status_label($sv)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="hint"><?= e(post_status_hint($curStatus)) ?></span>
            </div>
            <div class="row-actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
                <a class="btn btn-secondary" href="/admin/posts.php">К списку</a>
                <?php if ($post): ?>
                    <a class="btn btn-secondary" href="/post.php?slug=<?= e(urlencode((string) $post['slug'])) ?>">На сайте</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($post): ?>
            <form method="post" action="" onsubmit="return confirm('Удалить этот рецепт?');" style="margin-top:2rem">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-secondary" type="submit" style="background:#fde8e4;color:#8c2f23">Удалить рецепт</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
