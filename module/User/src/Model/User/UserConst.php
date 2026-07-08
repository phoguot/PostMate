<?php
declare(strict_types=1);

namespace User\Model\User;

use Application\Model\Constant\AppConstModel;

/**
 * Const cho bảng users.
 */
class UserConst extends AppConstModel
{
    // --- status ---
    const STATUS_ACTIVE = 1;   // Đang hoạt động
    const STATUS_INACTIVE = 2; // Chưa kích hoạt
    const STATUS_LOCKED = 3;   // Bị khóa

    // --- role (màn Cài đặt: Admin/Member/Viewer) ---
    const ROLE_ADMIN = 'admin';
    const ROLE_MEMBER = 'member';
    const ROLE_VIEWER = 'viewer';

    public static function getAllowedStatuses(): array
    {
        return [
            UserConst::STATUS_ACTIVE,
            UserConst::STATUS_INACTIVE,
            UserConst::STATUS_LOCKED,
        ];
    }

    public static function getAllowedRoles(): array
    {
        return [
            UserConst::ROLE_ADMIN,
            UserConst::ROLE_MEMBER,
            UserConst::ROLE_VIEWER,
        ];
    }
}
