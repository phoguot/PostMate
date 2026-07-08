<?php
declare(strict_types=1);

namespace Facebook\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Facebook\Service\FanpageService;

/**
 * Controller màn Fanpage (docs/Features/Fanpage).
 */
class FanpageController extends AppController
{
    /** DANH SÁCH fanpage liên kết. */
    public function indexAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FanpageService::class);
        return $service->fanpageList($this->getPostParamsApi());
    }

    /** KPI: Tổng / Đang hoạt động / Cần đăng nhập lại / Không hoạt động. */
    public function statsAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FanpageService::class);
        return $service->fanpageStats($this->getPostParamsApi());
    }

    /** Chi tiết 1 fanpage. */
    public function detailAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FanpageService::class);
        return $service->fanpageDetail($this->getPostParamsApi());
    }

    /** Đăng nhập lại (ủy quyền FacebookAccountService::reLogin). */
    public function reloginAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FanpageService::class);
        return $service->reLoginFromFanpage($this->getPostParamsApi());
    }

    /** Gỡ liên kết. */
    public function unlinkAction(): JsonResponse
    {
        $service = $this->getContainerEntry(FanpageService::class);
        return $service->unlinkFanpage($this->getPostParamsApi());
    }
}
