<?php
declare(strict_types=1);

namespace Application\Model\ActivityLog;

use Application\Model\Constant\AppConstModel;

/**
 * Const cho bảng activity_logs — nhật ký hoạt động dùng chung toàn hệ thống.
 */
class ActivityLogConst extends AppConstModel
{
    public const LEVEL_INFO = 1;
    public const LEVEL_SUCCESS = 2;
    public const LEVEL_WARNING = 3;
    public const LEVEL_ERROR = 4;
}
