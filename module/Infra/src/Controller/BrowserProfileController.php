<?php
declare(strict_types=1);

namespace Infra\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Infra\Service\BrowserProfileService;

/**
 * Controller màn Trình duyệt (docs/Features/TrinhDuyet).
 */
class BrowserProfileController extends AppController
{
    /** DANH SÁCH trình duyệt. */
    public function indexAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->profileList($this->getPostParamsApi());
    }

    /** KPI: Tổng (trên N máy) / Đang chạy / Đang dừng / Ngoại tuyến. */
    public function statsAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->profileStats($this->getPostParamsApi());
    }

    /** Chi tiết 1 trình duyệt. */
    public function detailAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->profileDetail($this->getPostParamsApi());
    }

    /** Khởi động. */
    public function startAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->startProfile($this->getPostParamsApi());
    }

    /** Dừng. */
    public function stopAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->stopProfile($this->getPostParamsApi());
    }

    /** Khởi động lại. */
    public function restartAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->restartProfile($this->getPostParamsApi());
    }

    /** Mở (xem trực tiếp / debug). */
    public function openAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->openProfile($this->getPostParamsApi());
    }

    /** Xóa. */
    public function deleteAction(): JsonResponse
    {
        $service = $this->getContainerEntry(BrowserProfileService::class);
        return $service->deleteProfile($this->getPostParamsApi());
    }
}
