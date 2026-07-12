-- Thêm đích đăng "trang cá nhân" cho bảng posts (targetType + facebookAccountId).
-- Chạy trên DB đã tồn tại (postmate.sql dùng CREATE TABLE IF NOT EXISTS nên không tự alter).
ALTER TABLE `posts`
    ADD COLUMN `targetType` TINYINT NOT NULL DEFAULT 1 AFTER `contentType`,
    ADD COLUMN `facebookAccountId` BIGINT UNSIGNED DEFAULT NULL AFTER `fanpageId`,
    ADD KEY `idx_posts_fb_account` (`facebookAccountId`),
    ADD CONSTRAINT `fk_posts_fb_account` FOREIGN KEY (`facebookAccountId`)
        REFERENCES `facebook_accounts` (`id`) ON DELETE SET NULL;
