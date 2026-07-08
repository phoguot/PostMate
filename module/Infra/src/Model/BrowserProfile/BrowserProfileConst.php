<?php
declare(strict_types=1);

namespace Infra\Model\BrowserProfile;

use Application\Model\Constant\AppConstModel;

/**
 * Const bảng browser_profiles — Chrome profile chống phát hiện.
 */
class BrowserProfileConst extends AppConstModel
{
    // --- status ---
    public const STATUS_RUNNING = 1;  // Đang chạy
    public const STATUS_STOPPED = 2;  // Đang dừng
    public const STATUS_OFFLINE = 3;  // Ngoại tuyến (server host offline)

    // --- mode ---
    public const MODE_HEADLESS = 1;
    public const MODE_GUI = 2;

    public static function getAllowedStatuses(): array
    {
        return [
            BrowserProfileConst::STATUS_RUNNING,
            BrowserProfileConst::STATUS_STOPPED,
            BrowserProfileConst::STATUS_OFFLINE,
        ];
    }

    public static function getAllowedModes(): array
    {
        return [BrowserProfileConst::MODE_HEADLESS, BrowserProfileConst::MODE_GUI];
    }
}
