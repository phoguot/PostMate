<?php
declare(strict_types=1);

namespace Infra\Model\Server;

use Application\Model\Constant\AppConstModel;

/**
 * Const bảng servers — máy chủ chạy Chrome instance.
 */
class ServerConst extends AppConstModel
{
    public const STATUS_ONLINE = 1;
    public const STATUS_OFFLINE = 2;

    public static function getAllowedStatuses(): array
    {
        return [ServerConst::STATUS_ONLINE, ServerConst::STATUS_OFFLINE];
    }
}
