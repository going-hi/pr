<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'not_authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$postId = (int) ($_POST['post_id'] ?? 0);
if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

$pdo = db();
$stf = $pdo->prepare('SELECT id FROM favorites WHERE post_id = ? AND user_id = ?');
$stf->execute([$postId, (int)$user['id']]);
$saved = (bool) $stf->fetch();

if ($saved) {
    $pdo->prepare('DELETE FROM favorites WHERE post_id = ? AND user_id = ?')
        ->execute([$postId, (int)$user['id']]);
    $saved = false;
} else {
    $pub = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
    $pub->execute([$postId]);
    if (!$pub->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }
    $pdo->prepare('INSERT IGNORE INTO favorites (post_id, user_id) VALUES (?,?)')
        ->execute([$postId, (int)$user['id']]);
    $saved = true;
}

echo json_encode(['ok' => true, 'saved' => $saved]);
