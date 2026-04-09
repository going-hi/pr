<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$pageTitle = 'Новый рецепт — ' . SITE_NAME;
$activeNav = 'create';

$errors = [];
$cats = categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim((string) ($_POST['title'] ?? ''));
    $category = (string) ($_POST['category'] ?? '');
    $excerpt  = trim((string) ($_POST['excerpt'] ?? ''));
    $body     = trim((string) ($_POST['body'] ?? ''));

    if ($title === '') {
        $errors[] = 'Укажите заголовок.';
    }
    if (mb_strlen($title) > 255) {
        $errors[] = 'Заголовок слишком длинный.';
    }
    if ($body === '') {
        $errors[] = 'Введите текст рецепта.';
    }
    if ($category !== '' && !array_key_exists($category, $cats)) {
        $errors[] = 'Выберите правильную категорию.';
    }

    if (!$errors) {
        $pdo = db();
        $slug = slugify($title, $pdo);
        $ins = $pdo->prepare(
            "INSERT INTO posts (user_id, title, slug, category, excerpt, body, status) VALUES (?,?,?,?,?,?,'published')"
        );
        $ins->execute([
            (int) $user['id'],
            $title,
            $slug,
            $category !== '' ? $category : null,
            $excerpt ?: null,
            $body,
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
            redirect('/post.php?slug=' . urlencode($slug));
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div style="max-width:720px">
    <h1>Новый рецепт</h1>
    <p class="lead" style="margin-bottom:1.5rem">Текст и по желанию фото — без фото покажем красивую заглушку.</p>

    <?php foreach ($errors as $err): ?>
        <p class="alert alert-error"><?= e($err) ?></p>
    <?php endforeach; ?>

    <form method="post" action="" class="form-card" style="max-width:100%" enctype="multipart/form-data">
        <div class="form-group">
            <label for="recipe_image">Фото блюда</label>
            <input id="recipe_image" name="recipe_image" type="file" accept="image/jpeg,image/png,image/webp">
            <span class="hint">JPEG, PNG или WebP, до 4 МБ. Необязательно.</span>
        </div>

        <div class="form-group">
            <label for="title">Название рецепта *</label>
            <input id="title" name="title" required maxlength="255"
                   placeholder="Например: Домашняя пицца с грибами"
                   value="<?= e((string) ($_POST['title'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="category">Категория</label>
            <select id="category" name="category">
                <option value="">— выберите категорию —</option>
                <?php foreach ($cats as $slug => $cat): ?>
                    <option value="<?= e($slug) ?>" <?= (($_POST['category'] ?? '') === $slug ? 'selected' : '') ?>>
                        <?= $cat['emoji'] ?> <?= e($cat['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="excerpt">Краткое описание</label>
            <input id="excerpt" name="excerpt" maxlength="400"
                   placeholder="Пара предложений о рецепте"
                   value="<?= e((string) ($_POST['excerpt'] ?? '')) ?>">
            <span class="hint">Показывается в карточке в ленте</span>
        </div>

        <div class="form-group">
            <label for="body">Рецепт *</label>
            <textarea id="body" name="body" rows="16" required
                      placeholder="Ингредиенты:&#10;- ...&#10;&#10;Приготовление:&#10;1. ..."><?= e((string) ($_POST['body'] ?? '')) ?></textarea>
        </div>

        <div class="row-actions">
            <button class="btn btn-primary" type="submit">Опубликовать рецепт</button>
            <a href="/recipes.php" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
