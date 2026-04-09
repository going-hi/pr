-- Только колонка image_path для уже существующей таблицы posts (есть category).
-- Повторный запуск: ошибка Duplicate column — значит уже применено.
USE culinary_blog;

ALTER TABLE posts
  ADD COLUMN image_path VARCHAR(512) NULL DEFAULT NULL AFTER category;
