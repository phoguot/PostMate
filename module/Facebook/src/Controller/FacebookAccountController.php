<?php
declare(strict_types=1);

namespace Facebook\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Facebook\Service\FacebookAccountService;
use Laminas\Http\Response;

/**
 * Controller màn Tài khoản Facebook (docs/Features/TaiKhoanFacebook).
 * - Mỏng: chỉ lấy service từ container và forward payload API.
 */
class FacebookAccountController extends AppController
{
    /** DANH SÁCH tài khoản Facebook (bảng chính). */
    public function indexAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->accountList($this->getPostParamsApi());
    }

    /** KPI: Tổng / Đang hoạt động / Cookie sắp hết hạn / Gặp vấn đề. */
    public function statsAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->accountStats($this->getPostParamsApi());
    }

    /** Chi tiết 1 tài khoản (kèm tab Fanpage / Phiên đăng nhập / Nhật ký). */
    public function detailAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->accountDetail($this->getPostParamsApi());
    }

    /** Bước 1: dựng URL cấp quyền OAuth Facebook thật (popup xin quyền — docs/Design/popup_connect_facebook.png). */
    public function connectAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->connectAccount($this->getPostParamsApi());
    }

    /** Bước 2: Facebook redirect về đây sau khi cấp quyền (GET code/state) — đổi token thật + lưu account/fanpage. */
    public function oauthcallbackAction(): Response
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->handleOAuthCallback($this->getAllQueryParams());
    }

    /** Đăng nhập lại. */
    public function reloginAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->reLogin($this->getPostParamsApi());
    }

    /** Xóa tài khoản. */
    public function deleteAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FacebookAccountService::class);
        return $service->deleteAccount($this->getPostParamsApi());
    }
}
