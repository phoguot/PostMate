<?php
declare(strict_types=1);

namespace Facebook\Model\Cookie;

use Application\Model\Constant\AppConstModel;

/**
 * Const hỗ trợ bảng cookies.
 */
class CookieConst extends AppConstModel
{
    // --- status (cookies.status) ---
    public const STATUS_VALID = 1;    // Hợp lệ
    public const STATUS_EXPIRING = 2; // Sắp hết hạn
    public const STATUS_INVALID = 3;  // Không hợp lệ

    // --- ràng buộc nghiệp vụ ---
    public const EXPIRING_THRESHOLD_DAYS = 7;      // KPI "sắp hết hạn" / cảnh báo
    public const PROACTIVE_REFRESH_THRESHOLD_DAYS = 3; // cron làm mới chủ động trước hạn

    public static function getAllowedStatuses(): array
    {
        return [
            CookieConst::STATUS_VALID,
            CookieConst::STATUS_EXPIRING,
            CookieConst::STATUS_INVALID,
        ];
    }
}
