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

$chk = db()->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published'");
$chk->execute([$postId]);
if (!$chk->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$pdo = db();
$stl = $pdo->prepare('SELECT id FROM likes WHERE post_id = ? AND user_id = ?');
$stl->execute([$postId, (int)$user['id']]);
$liked = (bool) $stl->fetch();

if ($liked) {
    $pdo->prepare('DELETE FROM likes WHERE post_id = ? AND user_id = ?')
        ->execute([$postId, (int)$user['id']]);
    $liked = false;
} else {
    $pdo->prepare('INSERT IGNORE INTO likes (post_id, user_id) VALUES (?,?)')
        ->execute([$postId, (int)$user['id']]);
    $liked = true;
}

$stc = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$stc->execute([$postId]);
$count = (int) $stc->fetchColumn();

echo json_encode(['ok' => true, 'liked' => $liked, 'count' => $count]);
