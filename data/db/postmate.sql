-- =============================================================================
-- PostMate — Khởi tạo cơ sở dữ liệu
--
-- DB khớp với config/autoload/local.php ('database' => 'postmate').
-- Tên cột dùng camelCase để khớp với query trong các Mapper (vd createdById,
-- scheduledAt, passwordHash...).
-- Nguồn: docs/PHAN_TICH_HE_THONG.md (mục 2 - CSDL, mục 6.5 - bổ sung Graph API).
--
-- Cách dùng (MySQL/MariaDB):
--   mysql -u root -p < data/db/postmate.sql
--
-- Tài khoản đăng nhập mẫu:  username = admin   password = admin123
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `postmate`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `postmate`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- users — Người dùng hệ thống (module User)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`     VARCHAR(100)    NOT NULL,
    `email`        VARCHAR(190)    NOT NULL,
    `passwordHash` VARCHAR(255)    NOT NULL,
    `fullName`     VARCHAR(190)    DEFAULT NULL,
    `avatarUrl`    VARCHAR(255)    DEFAULT NULL,
    `role`         VARCHAR(20)     NOT NULL DEFAULT 'member',   -- admin | member | viewer
    `plan`         VARCHAR(50)     DEFAULT NULL,
    `fbUserId`     VARCHAR(64)     DEFAULT NULL,
    `status`       TINYINT         NOT NULL DEFAULT 1,          -- 1 active, 2 inactive, 3 locked
    `createdAt`    BIGINT UNSIGNED DEFAULT NULL,
    `updatedAt`    BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- servers — Máy chủ chạy Chrome instance (module Infra)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `servers` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(190)    NOT NULL,           -- Server 01/02/03
    `ipAddress`     VARCHAR(45)     DEFAULT NULL,
    `status`        TINYINT         NOT NULL DEFAULT 1, -- 1 online, 2 offline
    `cpuUsage`      FLOAT           DEFAULT NULL,
    `ramUsage`      FLOAT           DEFAULT NULL,
    `maxInstances`  INT             NOT NULL DEFAULT 0,
    `createdById`   BIGINT UNSIGNED DEFAULT NULL,
    `createdAt`     BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`    BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_servers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- proxies — IP/Proxy gán cho browser_profiles (module Infra)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proxies` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip`          VARCHAR(45)     NOT NULL,
    `country`     VARCHAR(10)     DEFAULT NULL,         -- VN...
    `type`        TINYINT         NOT NULL DEFAULT 1,   -- 1 residential, 2 datacenter, 3 mobile
    `status`      TINYINT         NOT NULL DEFAULT 1,   -- 1 active, 2 dead
    `createdById` BIGINT UNSIGNED DEFAULT NULL,
    `createdAt`   BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`  BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_proxies_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ai_agents — AI Agent hỗ trợ soạn nội dung (module AI)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_agents` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(190)    NOT NULL,
    `status`      TINYINT         NOT NULL DEFAULT 1,   -- 1 active, 2 idle
    `model`       VARCHAR(190)    DEFAULT NULL,
    `config`      TEXT            DEFAULT NULL,         -- JSON
    `createdById` BIGINT UNSIGNED DEFAULT NULL,
    `createdAt`   BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`  BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ai_agents_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- browser_profiles — Chrome instance chống phát hiện (module Infra)
-- facebookAccountId gắn 1-1 với facebook_accounts; FK được thêm sau khi
-- facebook_accounts được tạo (tránh phụ thuộc vòng khi khởi tạo).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `browser_profiles` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`               VARCHAR(100)    DEFAULT NULL,        -- Chrome #01
    `profileName`        VARCHAR(190)    DEFAULT NULL,        -- khanh01
    `profileId`          INT             DEFAULT NULL,
    `serverId`           BIGINT UNSIGNED DEFAULT NULL,
    `proxyId`            BIGINT UNSIGNED DEFAULT NULL,
    `facebookAccountId`  BIGINT UNSIGNED DEFAULT NULL,
    `status`             TINYINT         NOT NULL DEFAULT 2,  -- 1 running, 2 stopped, 3 offline
    `mode`                TINYINT        NOT NULL DEFAULT 1,  -- 1 headless, 2 gui
    `chromeVersion`      VARCHAR(50)     DEFAULT NULL,
    `os`                 VARCHAR(100)    DEFAULT NULL,
    `userAgent`          TEXT            DEFAULT NULL,
    `fingerprintJson`    TEXT            DEFAULT NULL,        -- JSON: canvas/webgl/fonts/timezone/screen
    `cpuPercent`         FLOAT           DEFAULT NULL,
    `ramMb`              INT             DEFAULT NULL,
    `startedAt`          DATETIME        DEFAULT NULL,
    `lastActiveAt`       DATETIME        DEFAULT NULL,
    `uptimeMinutes`      INT             DEFAULT NULL,
    `createdById`        BIGINT UNSIGNED DEFAULT NULL,
    `createdAt`          BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`         BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_browser_profiles_server` (`serverId`),
    KEY `idx_browser_profiles_proxy` (`proxyId`),
    KEY `idx_browser_profiles_fb_account` (`facebookAccountId`),
    KEY `idx_browser_profiles_status` (`status`),
    CONSTRAINT `fk_browser_profiles_server` FOREIGN KEY (`serverId`)
        REFERENCES `servers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_browser_profiles_proxy` FOREIGN KEY (`proxyId`)
        REFERENCES `proxies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- facebook_accounts — Tài khoản Facebook dùng để đăng (module Facebook)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `facebook_accounts` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ownerUserId`       BIGINT UNSIGNED NOT NULL,
    `displayName`       VARCHAR(190)    DEFAULT NULL,
    `email`             VARCHAR(190)    DEFAULT NULL,
    `avatarUrl`         VARCHAR(255)    DEFAULT NULL,
    `fbUserId`          VARCHAR(64)     DEFAULT NULL,        -- id thật từ Graph API /me (OAuth Facebook Login)
    `userAccessToken`   TEXT            DEFAULT NULL,        -- long-lived user access token, mã hóa at-rest
    `browserProfileId`  BIGINT UNSIGNED DEFAULT NULL,
    `status`            TINYINT         NOT NULL DEFAULT 1,  -- 1 active, 2 inactive, 3 checkpoint
    `accountRole`       VARCHAR(50)     DEFAULT NULL,        -- Quản trị viên | Biên tập viên (màn Cài đặt > Facebook)
    `isPrimary`         TINYINT(1)      NOT NULL DEFAULT 0,  -- tài khoản Chính
    `expiresAt`         DATETIME        DEFAULT NULL,        -- hạn quyền/token kết nối
    `lastLoginAt`       DATETIME        DEFAULT NULL,
    `lastLoginIp`       VARCHAR(45)     DEFAULT NULL,
    `device`            VARCHAR(190)    DEFAULT NULL,
    `userAgent`         TEXT            DEFAULT NULL,
    `capabilities`      TEXT            DEFAULT NULL,        -- JSON: post/upload/comment/reply/inbox
    `createdAt`         BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`        BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fb_accounts_owner` (`ownerUserId`),
    KEY `idx_fb_accounts_browser_profile` (`browserProfileId`),
    KEY `idx_fb_accounts_status` (`status`),
    KEY `idx_fb_accounts_fbuserid` (`fbUserId`),
    CONSTRAINT `fk_fb_accounts_owner` FOREIGN KEY (`ownerUserId`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fb_accounts_browser_profile` FOREIGN KEY (`browserProfileId`)
        REFERENCES `browser_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đóng vòng quan hệ 1-1 browser_profiles <-> facebook_accounts
ALTER TABLE `browser_profiles`
    ADD CONSTRAINT `fk_browser_profiles_fb_account` FOREIGN KEY (`facebookAccountId`)
        REFERENCES `facebook_accounts` (`id`) ON DELETE SET NULL;

-- -----------------------------------------------------------------------------
-- cookies — Phiên đăng nhập Facebook (module Facebook)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cookies` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`               VARCHAR(100)    DEFAULT NULL,       -- ck_01a7f9c3...d9b2
    `facebookAccountId`  BIGINT UNSIGNED NOT NULL,
    `browserProfileId`   BIGINT UNSIGNED DEFAULT NULL,
    `sizeKb`             FLOAT           DEFAULT NULL,
    `status`             TINYINT         NOT NULL DEFAULT 1, -- 1 valid, 2 expiring, 3 invalid
    `expiresAt`          DATE            DEFAULT NULL,
    `lastLoginAt`        DATETIME        DEFAULT NULL,
    `lastLoginIp`        VARCHAR(45)     DEFAULT NULL,
    `device`             VARCHAR(190)    DEFAULT NULL,
    `userAgent`          TEXT            DEFAULT NULL,
    `cookieBlob`         TEXT            DEFAULT NULL,       -- nội dung cookie (mã hóa at-rest)
    `createdAt`          BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`         BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cookies_fb_account` (`facebookAccountId`),
    KEY `idx_cookies_browser_profile` (`browserProfileId`),
    KEY `idx_cookies_status` (`status`),
    CONSTRAINT `fk_cookies_fb_account` FOREIGN KEY (`facebookAccountId`)
        REFERENCES `facebook_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cookies_browser_profile` FOREIGN KEY (`browserProfileId`)
        REFERENCES `browser_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- meta_app_credentials — Cấu hình Meta App (mục 6.5, kênh Graph API)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meta_app_credentials` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `appId`              VARCHAR(190)    NOT NULL,
    `appSecret`          VARCHAR(255)    DEFAULT NULL,        -- mã hóa at-rest
    `systemUserToken`    TEXT            DEFAULT NULL,        -- mã hóa at-rest, token dài hạn
    `createdById`        BIGINT UNSIGNED DEFAULT NULL,
    `createdAt`          BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`         BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- fanpages — Fanpage do tài khoản Facebook quản lý (module Facebook)
-- Bổ sung pageAccessToken/tokenExpiresAt/apiEnabled theo mục 6.5 (Graph API).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fanpages` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fbPageId`           VARCHAR(64)     DEFAULT NULL,
    `name`               VARCHAR(190)    NOT NULL,
    `category`           VARCHAR(190)    DEFAULT NULL,
    `url`                VARCHAR(255)    DEFAULT NULL,
    `facebookAccountId`  BIGINT UNSIGNED NOT NULL,
    `browserProfileId`   BIGINT UNSIGNED DEFAULT NULL,
    `likesCount`         INT             DEFAULT NULL,
    `followersCount`     INT             DEFAULT NULL,
    `status`             TINYINT         NOT NULL DEFAULT 1,  -- 1 active, 2 need_relogin, 3 inactive
    `canPost`            TINYINT(1)      NOT NULL DEFAULT 1,
    `capabilities`       TEXT            DEFAULT NULL,        -- JSON: post/comment/upload/reply/inbox
    `lastPostAt`         DATETIME        DEFAULT NULL,
    `pageAccessToken`    TEXT            DEFAULT NULL,        -- mã hóa at-rest, token đăng qua Graph API
    `tokenExpiresAt`     DATETIME        DEFAULT NULL,
    `apiEnabled`         TINYINT(1)      NOT NULL DEFAULT 0,
    `createdAt`          BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`         BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fanpages_fb_account` (`facebookAccountId`),
    KEY `idx_fanpages_browser_profile` (`browserProfileId`),
    KEY `idx_fanpages_status` (`status`),
    CONSTRAINT `fk_fanpages_fb_account` FOREIGN KEY (`facebookAccountId`)
        REFERENCES `facebook_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fanpages_browser_profile` FOREIGN KEY (`browserProfileId`)
        REFERENCES `browser_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- posts — Bài viết / lịch đăng (module Posting)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            VARCHAR(500)    DEFAULT NULL,
    `content`          MEDIUMTEXT      DEFAULT NULL,
    `contentType`      TINYINT         NOT NULL DEFAULT 1,      -- 1 text,2 image,3 video,4 link,5 poll
    `targetType`       TINYINT         NOT NULL DEFAULT 1,      -- 1 fanpage, 2 profile (trang cá nhân)
    `fanpageId`        BIGINT UNSIGNED DEFAULT NULL,
    `facebookAccountId` BIGINT UNSIGNED DEFAULT NULL,           -- đích khi targetType = profile
    `browserProfileId` BIGINT UNSIGNED DEFAULT NULL,
    `aiAgentId`        BIGINT UNSIGNED DEFAULT NULL,
    `status`           TINYINT         NOT NULL DEFAULT 1,      -- 1 draft,2 scheduled,3 processing,4 published,5 failed,6 expired,7 deleted
    `channel`          TINYINT         NOT NULL DEFAULT 1,      -- 1 graph_api, 2 browser
    `scheduledAt`      DATETIME        DEFAULT NULL,
    `publishedAt`      DATETIME        DEFAULT NULL,
    `attemptCount`     INT             NOT NULL DEFAULT 0,
    `maxAttempts`      INT             NOT NULL DEFAULT 3,
    `repeatRule`       VARCHAR(255)    DEFAULT NULL,
    `fbPostId`         VARCHAR(128)    DEFAULT NULL,
    `note`             VARCHAR(255)    DEFAULT NULL,
    `options`          TEXT            DEFAULT NULL,            -- JSON: autoShortenLink/disableCommentNotif/autoShare
    `createdById`      BIGINT UNSIGNED DEFAULT NULL,
    `createdAt`        BIGINT UNSIGNED DEFAULT NULL,
    `modifiedById`     BIGINT UNSIGNED DEFAULT NULL,
    `modifiedAt`       BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_posts_owner` (`createdById`),
    KEY `idx_posts_status` (`status`),
    KEY `idx_posts_scheduledAt` (`scheduledAt`),
    KEY `idx_posts_fanpage` (`fanpageId`),
    KEY `idx_posts_fb_account` (`facebookAccountId`),
    KEY `idx_posts_browser_profile` (`browserProfileId`),
    KEY `idx_posts_ai_agent` (`aiAgentId`),
    KEY `idx_posts_owner_status` (`createdById`, `status`),
    CONSTRAINT `fk_posts_fanpage` FOREIGN KEY (`fanpageId`)
        REFERENCES `fanpages` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posts_fb_account` FOREIGN KEY (`facebookAccountId`)
        REFERENCES `facebook_accounts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posts_browser_profile` FOREIGN KEY (`browserProfileId`)
        REFERENCES `browser_profiles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posts_ai_agent` FOREIGN KEY (`aiAgentId`)
        REFERENCES `ai_agents` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_posts_owner` FOREIGN KEY (`createdById`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- post_media — Ảnh/video đính kèm bài viết (module Posting)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_media` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `postId`      BIGINT UNSIGNED NOT NULL,
    `type`        TINYINT         NOT NULL DEFAULT 1,           -- 1 image, 2 video
    `url`         VARCHAR(500)    NOT NULL,
    `storagePath` VARCHAR(500)    DEFAULT NULL,
    `orderIndex`  INT             NOT NULL DEFAULT 0,
    `createdAt`   BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_post_media_post` (`postId`),
    CONSTRAINT `fk_post_media_post` FOREIGN KEY (`postId`)
        REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- post_execution_logs — Timeline thực thi bài viết (màn Lịch đăng)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_execution_logs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `postId`       BIGINT UNSIGNED NOT NULL,
    `step`         VARCHAR(190)    NOT NULL,        -- Mở trình duyệt/Truy cập Facebook/Đi tới fanpage/...
    `status`       TINYINT         NOT NULL DEFAULT 1, -- 1 success, 2 failed
    `durationSec`  INT             DEFAULT NULL,
    `loggedAt`     DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_post_exec_logs_post` (`postId`),
    CONSTRAINT `fk_post_exec_logs_post` FOREIGN KEY (`postId`)
        REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- post_jobs — Hàng đợi job đăng bài (worker kéo theo runAt). Worker/agent Chrome
--             chạy ở máy riêng; đăng thật cắm vào BrowserAgentClient.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_jobs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `postId`     BIGINT UNSIGNED NOT NULL,
    `status`     TINYINT         NOT NULL DEFAULT 1,   -- 1 pending,2 processing,3 done,4 canceled,5 failed
    `runAt`      DATETIME        NOT NULL,             -- thời điểm được phép chạy
    `lockToken`  VARCHAR(64)     DEFAULT NULL,         -- token claim nguyên tử (chống 2 worker giành 1 job)
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

-- -----------------------------------------------------------------------------
-- post_metrics — Hiệu suất bài viết, đồng bộ định kỳ từ Facebook (màn Bài viết)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_metrics` (
    `postId`      BIGINT UNSIGNED NOT NULL,
    `likes`       INT             NOT NULL DEFAULT 0,
    `comments`    INT             NOT NULL DEFAULT 0,
    `shares`      INT             NOT NULL DEFAULT 0,
    `reach`       INT             NOT NULL DEFAULT 0,
    `engagement`  INT             NOT NULL DEFAULT 0,
    `saves`       INT             NOT NULL DEFAULT 0,
    `updatedAt`   BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`postId`),
    CONSTRAINT `fk_post_metrics_post` FOREIGN KEY (`postId`)
        REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- activity_logs — Nhật ký hoạt động toàn hệ thống (Dashboard)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId`      BIGINT UNSIGNED DEFAULT NULL,
    `entityRef`   VARCHAR(190)    DEFAULT NULL,        -- vd post:123, fanpage:45
    `type`        VARCHAR(100)    DEFAULT NULL,         -- Đăng bài/Khởi động Chrome/Refresh cookie/Tạo lịch...
    `message`     TEXT            DEFAULT NULL,
    `level`       TINYINT         NOT NULL DEFAULT 1,   -- 1 info, 2 success, 3 warning, 4 error
    -- Bổ sung cho màn Nhật ký hệ thống (diarysetting.png)
    `actorRole`   VARCHAR(20)     DEFAULT NULL,        -- vai trò người thao tác lúc ghi log (admin/member/viewer)
    `objectName`  VARCHAR(190)    DEFAULT NULL,        -- tên đối tượng (vd Shoes Store)
    `objectType`  VARCHAR(50)     DEFAULT NULL,        -- loại đối tượng (vd Trang Facebook, Nhóm...)
    `ipAddress`   VARCHAR(45)     DEFAULT NULL,        -- IP thao tác
    `device`      VARCHAR(190)    DEFAULT NULL,        -- Chrome / Windows...
    `createdAt`   BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_activity_logs_user` (`userId`),
    KEY `idx_activity_logs_level` (`level`),
    KEY `idx_activity_logs_created` (`createdAt`),
    CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`userId`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- settings — Cấu hình theo user (màn Cài đặt)
-- Bổ sung preferredChannel/allowBrowserFallback theo mục 6.5 (Graph API).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `userId`               BIGINT UNSIGNED NOT NULL,
    `language`             VARCHAR(20)     DEFAULT 'vi',       -- Tiếng Việt
    `timezone`             VARCHAR(50)     DEFAULT 'Asia/Ho_Chi_Minh',
    `dateFormat`           VARCHAR(20)     DEFAULT 'DD/MM/YYYY', -- Định dạng ngày hiển thị (màn Tài khoản)
    `themeMode`            VARCHAR(20)     NOT NULL DEFAULT 'light',    -- light | dark | system (màn Cài đặt chung)
    `displayDensity`       VARCHAR(20)     NOT NULL DEFAULT 'standard', -- compact | standard | comfortable
    `defaultFanpageId`     BIGINT UNSIGNED DEFAULT NULL,
    `defaultContentType`   TINYINT         DEFAULT 1,          -- xem PostConst::CONTENT_TYPE_*
    `defaultStatus`        TINYINT         DEFAULT 2,          -- xem PostConst::STATUS_*
    `defaultPostTime`      TIME            DEFAULT NULL,
    `autoShortenLink`      TINYINT(1)      NOT NULL DEFAULT 0,
    `autoSaveDraft`        TINYINT(1)      NOT NULL DEFAULT 0,
    `showAiSuggestions`    TINYINT(1)      NOT NULL DEFAULT 1,
    `confirmBeforePost`    TINYINT(1)      NOT NULL DEFAULT 1,
    -- Tùy chọn hệ thống chung (màn Cài đặt chung / allsetting.png)
    `confirmBeforeDelete`  TINYINT(1)      NOT NULL DEFAULT 1, -- Xác nhận trước khi xóa
    `autoSaveChanges`      TINYINT(1)      NOT NULL DEFAULT 1, -- Tự động lưu thay đổi
    `notificationSound`    TINYINT(1)      NOT NULL DEFAULT 0, -- Phát âm thanh thông báo
    `showQuickHints`       TINYINT(1)      NOT NULL DEFAULT 1, -- Hiển thị gợi ý nhanh
    `performanceTracking`  TINYINT(1)      NOT NULL DEFAULT 0, -- Theo dõi hiệu suất
    `preferredChannel`     TINYINT         NOT NULL DEFAULT 1, -- 1 graph_api, 2 browser
    `allowBrowserFallback` TINYINT(1)      NOT NULL DEFAULT 1,
    `storageUsed`          BIGINT          DEFAULT 0,
    `storageLimit`         BIGINT          DEFAULT 0,
    `appVersion`           VARCHAR(20)     DEFAULT NULL,
    `lastBackupAt`         DATETIME        DEFAULT NULL,
    `updatedAt`            BIGINT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`userId`),
    KEY `idx_settings_default_fanpage` (`defaultFanpageId`),
    CONSTRAINT `fk_settings_user` FOREIGN KEY (`userId`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_settings_default_fanpage` FOREIGN KEY (`defaultFanpageId`)
        REFERENCES `fanpages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Seed: tài khoản đăng nhập mẫu (admin / admin123)
-- passwordHash = password_hash('admin123', PASSWORD_BCRYPT)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `users`
    (`username`, `email`, `passwordHash`, `fullName`, `role`, `status`, `createdAt`)
VALUES
    ('admin', 'admin@postmate.local',
     '$2y$10$DHKY.UI9RrnezrIW3rgw/.MMz80MCTdIWoFMXdXMUtixJJA6MM.ja',
     'Administrator', 'admin', 1, UNIX_TIMESTAMP());

SET @adminId = (SELECT `id` FROM `users` WHERE `username` = 'admin' LIMIT 1);

-- -----------------------------------------------------------------------------
-- Seed: tài khoản Facebook mẫu (màn Cài đặt > Facebook — facebooksetting.png)
-- -----------------------------------------------------------------------------
INSERT INTO `facebook_accounts`
    (`ownerUserId`, `displayName`, `email`, `status`, `accountRole`, `isPrimary`,
     `expiresAt`, `lastLoginAt`, `createdAt`)
SELECT * FROM (
    -- Ngày hạn tính tương đối theo NOW() để luôn hiển thị "Còn N ngày" hợp lý bất kể chạy lúc nào.
    -- Alias tường minh cho mọi cột — tránh MySQL tự đặt tên trùng (vd 2 cột literal "1") gây lỗi 1060.
    SELECT @adminId AS ownerUserId, 'Khánh Nguyễn' AS displayName, '100012345678901' AS email, 1 AS status,
           'Quản trị viên' AS accountRole, 1 AS isPrimary,
           DATE_ADD(NOW(), INTERVAL 178 DAY) AS expiresAt, DATE_SUB(NOW(), INTERVAL 10 DAY) AS lastLoginAt, UNIX_TIMESTAMP() AS createdAt
    UNION ALL SELECT @adminId, 'Linh Mai', '100098765432109', 1, 'Biên tập viên', 0,
           DATE_ADD(NOW(), INTERVAL 176 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), UNIX_TIMESTAMP()
    UNION ALL SELECT @adminId, 'Hoàng Minh', '1000111222333344', 2, 'Biên tập viên', 0,
           DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), UNIX_TIMESTAMP()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `facebook_accounts` WHERE `ownerUserId` = @adminId);

-- -----------------------------------------------------------------------------
-- Seed: nhật ký hoạt động mẫu (màn Cài đặt > Nhật ký hệ thống — diarysetting.png)
-- -----------------------------------------------------------------------------
INSERT INTO `activity_logs`
    (`userId`, `entityRef`, `type`, `message`, `level`, `actorRole`,
     `objectName`, `objectType`, `ipAddress`, `device`, `createdAt`)
SELECT * FROM (
    -- Alias tường minh cho mọi cột — tránh MySQL tự đặt tên trùng gây lỗi 1060 (xem seed facebook_accounts ở trên).
    SELECT @adminId AS userId, 'post:1' AS entityRef, 'Đăng bài viết' AS type, 'BST giày thể thao mới 2025' AS message, 2 AS level, 'admin' AS actorRole,
           'Shoes Store' AS objectName, 'Trang Facebook' AS objectType, '123.45.67.89' AS ipAddress, 'Chrome / Windows' AS device, UNIX_TIMESTAMP('2025-05-29 09:35:21') AS createdAt
    UNION ALL SELECT @adminId, 'post:2', 'Cập nhật bài viết', 'Flash sale cuối tuần – Giảm 50%', 1, 'member',
           'Fashion Store', 'Trang Facebook', '113.160.22.15', 'Chrome / macOS', UNIX_TIMESTAMP('2025-05-29 09:22:10')
    UNION ALL SELECT @adminId, 'post:3', 'Lên lịch bài viết', 'Bí quyết chăm sóc da mùa hè – Thời gian: 30/05/2025 10:00', 1, 'member',
           'Cosmetics Store', 'Trang Facebook', '171.244.10.33', 'Firefox / Windows', UNIX_TIMESTAMP('2025-05-29 09:15:45')
    UNION ALL SELECT @adminId, 'post:4', 'Xóa bài viết', 'Không gian sống hiện đại (ID: 1234567890)', 3, 'admin',
           'Home & Living', 'Trang Facebook', '123.45.67.89', 'Chrome / Windows', UNIX_TIMESTAMP('2025-05-29 09:50:12')
    UNION ALL SELECT @adminId, 'member:5', 'Thêm thành viên', 'Thêm thành viên: Minh Anh — Vai trò: Editor', 2, 'member',
           'Nhóm Marketing', 'Nhóm', '171.244.10.33', 'Firefox / Windows', UNIX_TIMESTAMP('2025-05-29 18:05:07')
    UNION ALL SELECT @adminId, 'settings:1', 'Thay đổi cài đặt', 'Bật: Đăng ngẫu nhiên trong khung giờ', 1, 'admin',
           'Cài đặt chung', 'Hệ thống', '123.45.67.89', 'Chrome / Windows', UNIX_TIMESTAMP('2025-05-28 14:30:55')
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `activity_logs` WHERE `userId` = @adminId);
