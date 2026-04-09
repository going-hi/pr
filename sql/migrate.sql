-- Миграция для УЖЕ существующей БД (без колонки category и без likes/favorites/comments).
-- НЕ подключайте этот файл в docker-entrypoint-initdb.d вместе с полным schema.sql —
-- для новых установок достаточно только sql/schema.sql.
--
-- Запуск вручную (пример):
--   docker compose exec -i db mysql -uroot -prootsecret culinary_blog < sql/migrate.sql
--
-- Если колонка category уже есть — закомментируйте блок ALTER ниже.

USE culinary_blog;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Без IF NOT EXISTS: совместимо с MySQL 8.0 до 8.0.28. При повторном запуске выдаст ошибку «Duplicate column» — это нормально.
ALTER TABLE posts
  ADD COLUMN category VARCHAR(60) NULL DEFAULT NULL AFTER slug;

CREATE TABLE IF NOT EXISTS likes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_likes (post_id, user_id),
  CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_favorites (post_id, user_id),
  CONSTRAINT fk_favorites_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_comments_post (post_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Обложка рецепта (если колонка уже есть — закомментируйте)
ALTER TABLE posts
  ADD COLUMN image_path VARCHAR(512) NULL DEFAULT NULL AFTER category;
