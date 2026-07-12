-- Hàng đợi job đăng bài (worker layer). Chạy trên DB đã tồn tại.
CREATE TABLE IF NOT EXISTS `post_jobs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `postId`     BIGINT UNSIGNED NOT NULL,
    `status`     TINYINT         NOT NULL DEFAULT 1,   -- 1 pending,2 processing,3 done,4 canceled,5 failed
    `runAt`      DATETIME        NOT NULL,
    `lockToken`  VARCHAR(64)     DEFAULT NULL,
    `lockedAt`   DATETIME        DEFAULT NULL,
    `lastError`  VARCHAR(255)    DEFAULT NULL,
    `createdAt`  BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt` BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_post_jobs_post` (`postId`),
    KEY `idx_post_jobs_due` (`status`, `runAt`),
    CONSTRAINT `fk_post_jobs_post` FOREIGN KEY (`postId`)
        REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
