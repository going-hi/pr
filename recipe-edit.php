<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    render_not_found_page('Рецепт не найден.');
}

$st = db()->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
$st->execute([$id]);
$post = $st->fetch();

if (!$post) {
    render_not_found_page('Рецепт не найден.');
}

if ((int) $user['id'] !== (int) $post['user_id'] && $user['role'] !== 'admin') {
    http_response_code(403);
    echo 'Доступ запрещён.';
    exit;
}

$pageTitle = 'Редактировать: ' . (string) $post['title'] . ' — ' . SITE_NAME;
$activeNav = '';
$cats = categories();
$errors = [];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        recipe_delete_stored_image(isset($post['image_path']) ? (string) $post['image_path'] : null);
        db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        redirect('/cabinet.php');
    }

    $title     = trim((string) ($_POST['title'] ?? ''));
    $category  = (string) ($_POST['category'] ?? '');
    $excerpt   = trim((string) ($_POST['excerpt'] ?? ''));
    $body      = trim((string) ($_POST['body'] ?? ''));
    $removeImg = isset($_POST['remove_image']);

    if ($title === '') {
        $errors[] = 'Укажите заголовок.';
    }
    if ($body === '') {
        $errors[] = 'Введите текст рецепта.';
    }
    if ($category !== '' && !array_key_exists($category, $cats)) {
        $errors[] = 'Неверная категория.';
    }

    $imagePath = isset($post['image_path']) ? (string) $post['image_path'] : null;

    if (!$errors) {
        try {
            if ($removeImg) {
                recipe_delete_stored_image($imagePath);
                $imagePath = null;
            }
            $uploaded = recipe_save_uploaded_image('recipe_image', $id);
            if ($uploaded !== null) {
                recipe_delete_stored_image($imagePath);
                $imagePath = $uploaded;
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $pdo  = db();
        $slug = slugify($title, $pdo, $id);
        $pdo->prepare(
            'UPDATE posts SET title=?, slug=?, category=?, image_path=?, excerpt=?, body=? WHERE id=?'
        )->execute([
            $title,
            $slug,
            $category ?: null,
            $imagePath,
            $excerpt ?: null,
            $body,
            $id,
        ]);
        $st->execute([$id]);
        $post = $st->fetch();
        $saved = true;
    }
}

$coverPreview = recipe_image_url(isset($post['image_path']) ? (string) $post['image_path'] : null);

require __DIR__ . '/includes/header.php';
?>
<div style="max-width:720px">
    <h1>Редактировать рецепт</h1>

    <?php if ($saved): ?>
        <p class="alert alert-success">Изменения сохранены.</p>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
        <p class="alert alert-error"><?= e($err) ?></p>
    <?php endforeach; ?>

    <p style="margin-bottom:1rem">
        <strong>Сейчас в карточке:</strong><br>
        <img src="<?= e($coverPreview) ?>" alt="" style="max-width:100%;max-height:200px;border-radius:8px;margin-top:.5rem;object-fit:cover">
    </p>

    <form method="post" action="" class="form-card" style="max-width:100%" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <div class="form-group">
            <label for="recipe_image">Новое фото</label>
            <input id="recipe_image" name="recipe_image" type="file" accept="image/jpeg,image/png,image/webp">
            <span class="hint">Загрузите файл, чтобы заменить текущее. До 4 МБ.</span>
        </div>
        <?php if (!empty($post['image_path'])): ?>
            <div class="form-group">
                <label style="font-weight:500;cursor:pointer">
                    <input type="checkbox" name="remove_image" value="1">
                    Убрать фото (будет заглушка)
                </label>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="title">Название рецепта *</label>
            <input id="title" name="title" required maxlength="255" value="<?= e((string) $post['title']) ?>">
        </div>

        <div class="form-group">
            <label for="category">Категория</label>
            <select id="category" name="category">
                <option value="">— без категории —</option>
                <?php foreach ($cats as $slug => $cat): ?>
                    <option value="<?= e($slug) ?>" <?= ($post['category'] ?? '') === $slug ? 'selected' : '' ?>>
                        <?= $cat['emoji'] ?> <?= e($cat['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="excerpt">Краткое описание</label>
            <input id="excerpt" name="excerpt" maxlength="400" value="<?= e((string) ($post['excerpt'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="body">Рецепт *</label>
            <textarea id="body" name="body" rows="16" required><?= e((string) $post['body']) ?></textarea>
        </div>

        <?php if ((string)($post['status'] ?? 'published') !== 'published'): ?>
            <p class="alert alert-warn" style="margin-bottom:1rem">
                Статус в каталоге: <strong><?= e(post_status_label((string)($post['status'] ?? 'published'))) ?></strong>.
                Изменить может только администратор.
            </p>
        <?php endif; ?>

        <div class="row-actions">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a href="/post.php?slug=<?= e(urlencode((string) $post['slug'])) ?>" class="btn btn-secondary">Посмотреть</a>
        </div>
    </form>

    <form method="post" action="" style="margin-top:1.5rem"
          onsubmit="return confirm('Удалить этот рецепт? Действие нельзя отменить.')">
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-sm" type="submit"
                style="background:#fde8e4;color:#8c2f23;border:1px solid #f0c4bc">
            🗑️ Удалить рецепт
        </button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
