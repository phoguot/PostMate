<?php
declare(strict_types=1);

namespace Facebook\Model\FacebookAccount;

use Application\Model\Constant\AppConstModel;

/**
 * Const hỗ trợ bảng facebook_accounts.
 * - EXT_* là các field lưu trong cột capabilities (jsonb).
 */
class FacebookAccountConst extends AppConstModel
{
    // --- status (facebook_accounts.status) ---
    const STATUS_ACTIVE = 1;     // Đang hoạt động
    const STATUS_INACTIVE = 2;   // Không hoạt động (tắt chủ động)
    const STATUS_CHECKPOINT = 3; // Gặp vấn đề - FB yêu cầu xác minh

    // --- ràng buộc nghiệp vụ ---
    const COOKIE_EXPIRING_THRESHOLD_DAYS = 7; // KPI "cookie sắp hết hạn"

    // --- capabilities fields (lưu trong cột capabilities jsonb) ---
    const EXT_CAN_POST    = 'canPost';
    const EXT_CAN_UPLOAD  = 'canUpload';
    const EXT_CAN_COMMENT = 'canComment';
    const EXT_CAN_REPLY   = 'canReply';
    const EXT_CAN_INBOX   = 'canInbox';

    public static array $allowedExtraFields = [
        FacebookAccountConst::EXT_CAN_POST    => 'bool',
        FacebookAccountConst::EXT_CAN_UPLOAD  => 'bool',
        FacebookAccountConst::EXT_CAN_COMMENT => 'bool',
        FacebookAccountConst::EXT_CAN_REPLY   => 'bool',
        FacebookAccountConst::EXT_CAN_INBOX   => 'bool',
    ];

    public static function getAllowedStatuses(): array
    {
        return [
            FacebookAccountConst::STATUS_ACTIVE,
            FacebookAccountConst::STATUS_INACTIVE,
            FacebookAccountConst::STATUS_CHECKPOINT,
        ];
    }
}
