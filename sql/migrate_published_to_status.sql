-- Замена published (0/1) на status (published / hidden / needs_edit).
-- Запуск:
--   docker compose exec -T db mysql -uroot -prootsecret culinary_blog < sql/migrate_published_to_status.sql
--
-- Если колонка status уже есть — не выполняйте повторно.

USE culinary_blog;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE posts
  ADD COLUMN status ENUM('published', 'hidden', 'needs_edit') NOT NULL DEFAULT 'published' AFTER body;

UPDATE posts SET status = IF(published = 1, 'published', 'hidden');

ALTER TABLE posts DROP INDEX idx_posts_published_created;
ALTER TABLE posts DROP COLUMN published;

ALTER TABLE posts ADD INDEX idx_posts_status_created (status, created_at DESC);
