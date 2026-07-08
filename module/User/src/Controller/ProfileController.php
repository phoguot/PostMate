<?php
declare(strict_types=1);

namespace User\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use User\Service\UserService;

/**
 * Controller Hồ sơ & Bảo mật (màn Cài đặt > Tài khoản — profile.png).
 * Route: /api/user/profile/update · /change-password · /two-factor
 */
class ProfileController extends AppController
{
    /** Cập nhật hồ sơ (họ tên, ảnh đại diện). */
    public function updateAction(): JsonResponse
    {
        /** @var UserService $service */
        $service = $this->getContainerEntry(UserService::class);
        return $service->updateProfile($this->getPostParamsApi());
    }

    /** Đổi mật khẩu. */
    public function changePasswordAction(): JsonResponse
    {
        /** @var UserService $service */
        $service = $this->getContainerEntry(UserService::class);
        return $service->changePassword($this->getPostParamsApi());
    }

    /** Bật/tắt xác thực 2 lớp (2FA). */
    public function twoFactorAction(): JsonResponse
    {
        /** @var UserService $service */
        $service = $this->getContainerEntry(UserService::class);
        return $service->toggleTwoFactor($this->getPostParamsApi());
    }
}
