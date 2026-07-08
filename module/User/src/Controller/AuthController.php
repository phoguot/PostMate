<?php

namespace User\Controller;

use Application\Controller\AppController;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\JsonResponse;
use User\Filter\Auth\LoginFilter;
use User\Service\UserService;

/**
 * Controller xác thực: đăng nhập / đăng xuất / kiểm tra user đang đăng nhập.
 * Route: /user/auth/login · /user/auth/logout · /user/auth/me
 */
class AuthController extends AppController
{
    /**
     * Đăng nhập bằng username (hoặc email) + password.
     */
    public function loginAction(): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new LoginFilter($this->getContainer());
        $filter->setData($this->getPostParamsApi());
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $formData = $filter->getData();
        /** @var UserService $userService */
        $userService = $this->getContainerEntry(UserService::class);
        $user = $userService->login((string)$formData['username'], (string)$formData['password']);

        if (! $user) {
            return $apiResult->errorPage401Response(['Sai tài khoản hoặc mật khẩu']);
        }

        return $apiResult->successResponse($user->getRespUser(), ['Đăng nhập thành công']);
    }

    /**
     * Đăng xuất — xóa danh tính khỏi phiên.
     */
    public function logoutAction(): JsonResponse
    {
        $apiResult = new ApiResultModel();
        /** @var UserService $userService */
        $userService = $this->getContainerEntry(UserService::class);
        $userService->logout();

        return $apiResult->successResponse([], ['Đã đăng xuất']);
    }

    /**
     * Kiểm tra user đang đăng nhập — trả thông tin user hoặc 401.
     */
    public function meAction(): JsonResponse
    {
        $apiResult = new ApiResultModel();
        /** @var UserService $userService */
        $userService = $this->getContainerEntry(UserService::class);

        $user = $userService->getUser();
        if (! $user) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        return $apiResult->successResponse($user->getRespUser());
    }
}
