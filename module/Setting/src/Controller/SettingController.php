<?php
declare(strict_types=1);

namespace Setting\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Setting\Service\SettingsService;

/**
 * Controller màn Cài đặt (docs/Features/CaiDat) — phần cấu hình chung.
 */
class SettingController extends AppController
{
    /** Đọc cấu hình hiện tại. */
    public function indexAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->getSettings($this->getPostParamsApi());
    }

    /** Cập nhật một phần cấu hình. */
    public function updateAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->updateSettings($this->getPostParamsApi());
    }

    /** Bật/tắt 1 toggle hệ thống. */
    public function toggleAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->toggleOption($this->getPostParamsApi());
    }

    /** Thông tin hệ thống (phiên bản, dung lượng, sao lưu gần nhất). */
    public function systeminfoAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->getSystemInfo($this->getPostParamsApi());
    }

    /** Sao lưu ngay. */
    public function backupAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->backupNow($this->getPostParamsApi());
    }

    /** Xuất dữ liệu hệ thống + tùy chọn (màn Cài đặt chung). */
    public function exportAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->exportData($this->getPostParamsApi());
    }

    /** Đặt lại tất cả cài đặt về mặc định. */
    public function resetAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->resetSettings($this->getPostParamsApi());
    }

    /** Nhật ký hệ thống (có lọc + phân trang) — màn Nhật ký hệ thống. */
    public function activitylogAction(): JsonResponse
    {
        $service = $this->getContainerEntry(SettingsService::class);
        return $service->listActivityLog($this->getPostParamsApi());
    }
}
