<?php
declare(strict_types=1);

namespace Facebook\Model\Fanpage;

use Application\Model\Constant\AppConstModel;

/**
 * Const hỗ trợ bảng fanpages.
 * - EXT_* là các field lưu trong cột capabilities (jsonb).
 */
class FanpageConst extends AppConstModel
{
    // --- status (fanpages.status) ---
    const STATUS_ACTIVE = 1;       // Đang hoạt động
    const STATUS_NEED_RELOGIN = 2; // Cần đăng nhập lại
    const STATUS_INACTIVE = 3;     // Không hoạt động

    // --- channel (kênh đăng) ---
    const CHANNEL_GRAPH_API = 1; // Ưu tiên: Graph API
    const CHANNEL_BROWSER = 2;   // Fallback: browser anti-detect

    // --- capabilities fields (lưu trong cột capabilities jsonb) ---
    const EXT_CAN_POST    = 'canPost';
    const EXT_CAN_UPLOAD  = 'canUpload';
    const EXT_CAN_COMMENT = 'canComment';
    const EXT_CAN_REPLY   = 'canReply';
    const EXT_CAN_INBOX   = 'canInbox';

    public static array $allowedExtraFields = [
        FanpageConst::EXT_CAN_POST    => 'bool',
        FanpageConst::EXT_CAN_UPLOAD  => 'bool',
        FanpageConst::EXT_CAN_COMMENT => 'bool',
        FanpageConst::EXT_CAN_REPLY   => 'bool',
        FanpageConst::EXT_CAN_INBOX   => 'bool',
    ];

    public static function getAllowedStatuses(): array
    {
        return [
            FanpageConst::STATUS_ACTIVE,
            FanpageConst::STATUS_NEED_RELOGIN,
            FanpageConst::STATUS_INACTIVE,
        ];
    }
}
