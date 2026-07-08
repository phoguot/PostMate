<?php
declare(strict_types=1);

namespace Setting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\ActivityLog\ActivityLogModel;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Model\JsonResponse;
use Setting\Filter\Settings\ActivityLogListFilter;
use Setting\Filter\Settings\SettingToggleFilter;
use Setting\Filter\Settings\SettingUpdateFilter;
use Setting\Model\Settings\SettingsConst;
use Setting\Model\Settings\SettingsMapper;
use User\Service\UserService;

/**
 * Service màn Cài đặt (docs/Features/CaiDat) — phần cấu hình chung.
 * - Dữ liệu 1 dòng / user (settings.userId là PK).
 * - Token & Quyền (Meta App) tách riêng ở MetaAppService.
 */
class SettingsService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    /** Đọc cấu hình của user; nếu chưa có → tạo mặc định. */
    public function getSettings(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper   = $this->getContainerEntry(SettingsMapper::class);
        $settings = $mapper->ensureDefaults($userId);

        return $apiResult->successResponse($settings->getRespSettings());
    }

    /** Cập nhật một phần cấu hình (patch). */
    public function updateSettings(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new SettingUpdateFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(SettingsMapper::class);
        $mapper->ensureDefaults($userId);

        $data = [];
        $textKeys = ['language', 'timezone', 'dateFormat', 'themeMode', 'displayDensity', 'defaultFanpageId', 'defaultContentType', 'defaultStatus', 'defaultPostTime', 'preferredChannel'];
        foreach ($textKeys as $key) {
            if (array_key_exists($key, $formData) && $formData[$key] !== null && $formData[$key] !== '') {
                $data[$key] = $formData[$key];
            }
        }
        // Chuẩn hóa giá trị select giao diện.
        if (isset($data['themeMode']) && ! in_array($data['themeMode'], SettingsConst::THEME_MODES, true)) {
            unset($data['themeMode']);
        }
        if (isset($data['displayDensity']) && ! in_array($data['displayDensity'], SettingsConst::DISPLAY_DENSITIES, true)) {
            unset($data['displayDensity']);
        }
        if (array_key_exists('allowBrowserFallback', $payload)) {
            $data['allowBrowserFallback'] = filter_var($payload['allowBrowserFallback'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $mapper->updateSettings($userId, $data);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'settings:' . $userId,
            'Cập nhật cài đặt',
            'Cập nhật cấu hình hệ thống',
            ActivityLogConst::LEVEL_INFO
        );

        return $apiResult->successResponse($mapper->getByUserId($userId)->getRespSettings(), [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /** Bật/tắt 1 toggle hệ thống — lưu ngay (optimistic). */
    public function toggleOption(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new SettingToggleFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $key = (string)$formData['key'];
        if (! in_array($key, SettingsConst::getAllowedToggleKeys(), true)) {
            return $apiResult->errorInvalidFormResponse(['key' => AppMessage::INVALID_DATA]);
        }

        $mapper = $this->getContainerEntry(SettingsMapper::class);
        $mapper->ensureDefaults($userId);
        $mapper->updateSettings($userId, [$key => filter_var($formData['value'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0]);

        return $apiResult->successResponse($mapper->getByUserId($userId)->getRespSettings(), [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /** Thông tin hệ thống: app_version, last_backup_at, server, storage_used/limit. */
    public function getSystemInfo(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper   = $this->getContainerEntry(SettingsMapper::class);
        $settings = $mapper->ensureDefaults($userId);

        return $apiResult->successResponse([
            'appVersion'    => $settings->getAppVersion(),
            'lastBackupAt'  => $settings->getLastBackupAt(),
            'storageUsed'   => $settings->getStorageUsed() ?? 0,
            'storageLimit'  => $settings->getStorageLimit() ?? 0,
        ]);
    }

    /** Sao lưu ngay: đóng gói cấu hình + dữ liệu, cập nhật last_backup_at. */
    public function backupNow(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(SettingsMapper::class);
        $mapper->ensureDefaults($userId);

        // Hook: đóng gói + lưu vào storage backup thật (chưa tích hợp storage/backup service).
        $this->performBackup($userId);

        $mapper->updateSettings($userId, ['lastBackupAt' => DateModel::getCurrentDateTime()]);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'settings:' . $userId,
            'Sao lưu',
            'Sao lưu ngay',
            ActivityLogConst::LEVEL_SUCCESS
        );

        return $apiResult->successResponse(['lastBackupAt' => DateModel::getCurrentDateTime()], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /** Hook: đóng gói cấu hình + dữ liệu người dùng, lưu vào storage backup. */
    private function performBackup(int $userId): void
    {
    }

    /**
     * Xuất dữ liệu hệ thống + tùy chọn của user (màn Cài đặt chung / allsetting.png).
     * Đóng gói + tạo file export thật để hook — hiện trả metadata để FE tải/hiển thị.
     */
    public function exportData(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper   = $this->getContainerEntry(SettingsMapper::class);
        $settings = $mapper->ensureDefaults($userId);

        // Hook: đóng gói cấu hình + dữ liệu ra file (json/zip) và lưu vào storage export.
        $export = $this->performExport($userId, $settings);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'settings:' . $userId,
            'Xuất dữ liệu',
            'Xuất dữ liệu hệ thống và tùy chọn',
            ActivityLogConst::LEVEL_INFO
        );

        return $apiResult->successResponse($export, [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /** Đặt lại toàn bộ cài đặt về mặc định (giữ nguyên userId). */
    public function resetSettings(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(SettingsMapper::class);
        $mapper->resetToDefaults($userId);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'settings:' . $userId,
            'Đặt lại cài đặt',
            'Đặt lại tất cả cài đặt về mặc định',
            ActivityLogConst::LEVEL_WARNING
        );

        return $apiResult->successResponse($mapper->getByUserId($userId)->getRespSettings(), [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /**
     * Nhật ký hệ thống có lọc + phân trang (màn Nhật ký hệ thống — diarysetting.png).
     */
    public function listActivityLog(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new ActivityLogListFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $page     = ! empty($formData['page']) ? (int)$formData['page'] : 1;
        $pageSize = ! empty($formData['pageSize']) ? (int)$formData['pageSize'] : 10;

        $dateFrom = ! empty($formData['dateFrom']) ? DateModel::fromElasticDateToTimestamp($formData['dateFrom']) : null;
        $dateTo   = null;
        if (! empty($formData['dateTo'])) {
            $ts = DateModel::fromElasticDateToTimestamp($formData['dateTo']);
            $dateTo = $ts !== null ? $ts + 86399 : null; // hết ngày
        }

        $model = new ActivityLogModel();
        $model->setUserId($userId);
        $model->setOptions([
            'keyword'    => $formData['keyword'] ?? null,
            'type'       => $formData['type'] ?? null,
            'objectType' => $formData['objectType'] ?? null,
            'level'      => ! empty($formData['level']) ? (int)$formData['level'] : null,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
        ]);

        $mapper = $this->getContainerEntry(ActivityLogMapper::class);
        $search = $mapper->search($model, $page, $pageSize);

        $result = array_map(fn(ActivityLogModel $log) => $log->getRespActivityLog(), $search['items']);
        $total  = (int)$search['total'];

        return $apiResult->successResponse([
            'result'    => $result,
            'paginator' => [
                'page'       => $page,
                'pageSize'   => $pageSize,
                'totalItems' => $total,
                'totalPages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 0,
            ],
        ]);
    }

    /** Hook: đóng gói cấu hình + dữ liệu người dùng ra file export và trả metadata. */
    private function performExport(int $userId, $settings): array
    {
        return [
            'fileName'    => 'postmate-export-' . $userId . '-' . date('Ymd_His') . '.json',
            'generatedAt' => DateModel::getCurrentDateTime(),
            'downloadUrl' => null, // gắn URL storage thật khi tích hợp export service
        ];
    }
}
