<?php
declare(strict_types=1);

namespace Infra\Model\Proxy;

use Application\Model\Constant\AppConstModel;

/**
 * Const bảng proxies — IP đăng nhập gán cho browser_profiles.
 */
class ProxyConst extends AppConstModel
{
    public const TYPE_RESIDENTIAL = 1;
    public const TYPE_DATACENTER = 2;
    public const TYPE_MOBILE = 3;

    public const STATUS_ACTIVE = 1;
    public const STATUS_DEAD = 2;
}
