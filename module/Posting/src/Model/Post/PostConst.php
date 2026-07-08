<?php

declare(strict_types=1);

namespace Posting\Model\Post;

use Application\Model\Constant\AppConstModel;

/**
 * Khai báo các const hỗ trợ đối với bảng posts (và post_media).
 * - EXT_* là các field lưu trong cột options (jsonb) của posts.
 * - Trạng thái/loại nội dung/kênh đăng suy ra từ tài liệu PHAN_TICH_HE_THONG.
 */
class PostConst extends AppConstModel
{
    // --- status (posts.status) ---
    public const STATUS_DRAFT = 1;      // Nháp
    public const STATUS_SCHEDULED = 2;  // Đã lên lịch, chờ đăng
    public const STATUS_PROCESSING = 3; // Đang xử lý (worker đang chạy)
    public const STATUS_PUBLISHED = 4;  // Đã đăng thành công
    public const STATUS_FAILED = 5;     // Đăng thất bại
    public const STATUS_EXPIRED = 6;    // Quá hạn lịch mà chưa chạy được
    public const STATUS_DELETED = 7;    // Đã xóa (soft-delete)

    // --- content_type (posts.content_type) ---
    public const CONTENT_TYPE_TEXT = 1;
    public const CONTENT_TYPE_IMAGE = 2;
    public const CONTENT_TYPE_VIDEO = 3;
    public const CONTENT_TYPE_LINK = 4;
    public const CONTENT_TYPE_POLL = 5;

    // --- channel (posts.channel) ---
    public const CHANNEL_GRAPH_API = 1; // mặc định - đăng qua Meta Graph API
    public const CHANNEL_BROWSER = 2;   // fallback - đăng qua Chrome anti-detect

    // --- post_media.type ---
    public const MEDIA_TYPE_IMAGE = 1;
    public const MEDIA_TYPE_VIDEO = 2;

    // --- ràng buộc nghiệp vụ ---
    public const MAX_IMAGES = 10;        // tối đa 10 ảnh
    public const MAX_VIDEOS = 1;         // hoặc 1 video (không trộn ảnh + video)
    public const DEFAULT_MAX_ATTEMPTS = 3;
    public const NOTE_MAX_LENGTH = 200;  // ghi chú ≤ 200 ký tự

    // --- options fields (lưu trong cột options jsonb) ---
    public const EXT_AUTO_SHORTEN_LINK = 'autoShortenLink';         // tự rút gọn link
    public const EXT_DISABLE_COMMENT_NOTIF = 'disableCommentNotif'; // tắt thông báo bình luận
    public const EXT_AUTO_SHARE = 'autoShare';                      // tự chia sẻ

    public static array $allowedExtraFields = [
        PostConst::EXT_AUTO_SHORTEN_LINK     => 'bool',
        PostConst::EXT_DISABLE_COMMENT_NOTIF => 'bool',
        PostConst::EXT_AUTO_SHARE            => 'bool',
    ];

    /**
     * Các trạng thái người dùng được phép ghi trực tiếp khi tạo/sửa bài viết.
     * (processing/published/failed/expired là do worker set)
     */
    public static function getWritableStatuses(): array
    {
        return [
            PostConst::STATUS_DRAFT,
            PostConst::STATUS_SCHEDULED,
        ];
    }

    public static function getAllowedStatuses(): array
    {
        return [
            PostConst::STATUS_DRAFT,
            PostConst::STATUS_SCHEDULED,
            PostConst::STATUS_PROCESSING,
            PostConst::STATUS_PUBLISHED,
            PostConst::STATUS_FAILED,
            PostConst::STATUS_EXPIRED,
            PostConst::STATUS_DELETED,
        ];
    }

    public static function getAllowedContentTypes(): array
    {
        return [
            PostConst::CONTENT_TYPE_TEXT,
            PostConst::CONTENT_TYPE_IMAGE,
            PostConst::CONTENT_TYPE_VIDEO,
            PostConst::CONTENT_TYPE_LINK,
            PostConst::CONTENT_TYPE_POLL,
        ];
    }

    public static function getAllowedChannels(): array
    {
        return [
            PostConst::CHANNEL_GRAPH_API,
            PostConst::CHANNEL_BROWSER,
        ];
    }

    public static function getAllowedMediaTypes(): array
    {
        return [
            PostConst::MEDIA_TYPE_IMAGE,
            PostConst::MEDIA_TYPE_VIDEO,
        ];
    }
}
