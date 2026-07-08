<?php
declare(strict_types=1);

namespace Facebook\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Facebook\Service\CookieService;

/**
 * Controller màn Cookie (docs/Features/Cookie).
 */
class CookieController extends AppController
{
    /** DANH SÁCH cookie. */
    public function indexAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->cookieList($this->getPostParamsApi());
    }

    /** KPI: Tổng / Hợp lệ / Sắp hết hạn / Không hợp lệ. */
    public function statsAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->cookieStats($this->getPostParamsApi());
    }

    /** Chi tiết 1 cookie. */
    public function detailAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->cookieDetail($this->getPostParamsApi());
    }

    /** Đăng nhập — tạo cookie mới. */
    public function loginAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->loginCreateCookie($this->getPostParamsApi());
    }

    /** Làm mới cookie. */
    public function refreshAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->refreshCookie($this->getPostParamsApi());
    }

    /** Làm mới toàn bảng (mọi cookie đang expiring). */
    public function refreshallAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->refreshAllExpiring($this->getPostParamsApi());
    }

    /** Xuất cookie (hành động nhạy cảm). */
    public function exportAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->exportCookie($this->getPostParamsApi());
    }

    /** Xóa cookie. */
    public function deleteAction(): JsonResponse
    {
        $service = $this->getContainerEntry(CookieService::class);
        return $service->deleteCookie($this->getPostParamsApi());
    }
}
