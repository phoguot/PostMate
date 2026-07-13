<?php
declare(strict_types=1);

namespace Infra\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Model\JsonResponse;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Service\CookieService;
use Infra\Filter\BrowserProfile\BrowserProfileIdFilter;
use Infra\Filter\BrowserProfile\BrowserProfileListFilter;
use Infra\Model\BrowserProfile\BrowserProfileConst;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Infra\Model\BrowserProfile\BrowserProfileModel;
use Infra\Model\Server\ServerMapper;
use User\Service\UserService;

/**
 * Service màn Trình duyệt (docs/Features/TrinhDuyet).
 * - Dữ liệu thuộc về user đăng nhập, scope theo createdById.
 * - startProfile/stopProfile/openProfile điều khiển vòng đời Chrome instance thật —
 *   bước khởi tạo/đóng process trình duyệt thật vẫn để dạng hook (chưa có driver anti-detect thật).
 */
class BrowserProfileService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    // =========================================================================
    // LIST + STATS

    public function profileList(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new BrowserProfileListFilter($this->getContainer());
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
        $pageSize = ! empty($formData['pageSize']) ? (int)$formData['pageSize'] : 30;

        $model = new BrowserProfileModel();
        $model->setUserId($userId);
        $model->setId(! empty($formData['id']) ? (int)$formData['id'] : null);
        $model->setStatus(isset($formData['status']) && $formData['status'] !== '' ? (int)$formData['status'] : null);
        $model->setServerId(! empty($formData['serverId']) ? (int)$formData['serverId'] : null);
        $model->setFacebookAccountId(! empty($formData['facebookAccountId']) ? (int)$formData['facebookAccountId'] : null);
        $model->setOptions(['keyword' => $formData['keyword'] ?? null]);

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $search = $mapper->searchBrowserProfile($model, $page, $pageSize);

        $result = array_map(fn(BrowserProfileModel $item) => $item->getRespBrowserProfile(), $search['items']);
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

    /** KPI: Tổng (trên N máy) / Đang chạy / Đang dừng / Ngoại tuyến. */
    public function profileStats(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $model = new BrowserProfileModel();
        $model->setUserId($userId);

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        return $apiResult->successResponse($mapper->getStats($model));
    }

    // =========================================================================
    // DETAIL

    public function profileDetail(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new BrowserProfileIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $model  = new BrowserProfileModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getBrowserProfile($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        return $apiResult->successResponse($model->getRespBrowserProfile());
    }

    // =========================================================================
    // Vòng đời: start / stop / restart / open / delete

    public function startProfile(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        [$model, $errorResponse] = $this->loadOwnedProfile($payload, $apiResult);
        if ($errorResponse) {
            return $errorResponse;
        }

        if ((int)$model->getStatus() === BrowserProfileConst::STATUS_RUNNING) {
            return $apiResult->successResponse([], ['Trình duyệt đang chạy']);
        }

        if ($model->getServerId()) {
            $serverMapper = $this->getContainerEntry(ServerMapper::class);
            $server = $serverMapper->getById((int)$model->getServerId());
            if ($server && (int)$server->getStatus() !== 1) { // ServerConst::STATUS_ONLINE
                return $apiResult->errorResponse(['Máy chủ đang ngoại tuyến']);
            }
            $running = $serverMapper->countRunningInstances((int)$model->getServerId());
            if ($server && $server->getMaxInstances() && $running >= $server->getMaxInstances()) {
                return $apiResult->errorResponse(['Máy chủ đã đạt giới hạn số instance (max_instances) — vui lòng chờ hoặc chọn máy chủ khác']);
            }
        }

        // Hook: khởi tạo Chrome (--headless, proxy, user-agent, inject fingerprint,
        // navigator.webdriver=false) — chưa có driver anti-detect thật.
        $this->performStart($model);

        // Nạp cookie tài khoản gắn kèm (nếu có).
        if ($model->getFacebookAccountId()) {
            $cookieMapper = $this->getContainerEntry(CookieMapper::class);
            $cookieId = $cookieMapper->getLatestIdByAccountId((int)$model->getFacebookAccountId());
            if ($cookieId) {
                $cookieService = $this->getContainerEntry(CookieService::class);
                $cookieService->loadCookieIntoProfile($cookieId);
            }
        }

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $mapper->updateAttrs($model, [
            'status'       => BrowserProfileConst::STATUS_RUNNING,
            'startedAt'    => DateModel::getCurrentDateTime(),
            'lastActiveAt' => DateModel::getCurrentDateTime(),
        ]);

        $this->logActivity($model, 'Khởi động trình duyệt', 'Khởi động — ' . ($model->getProfileName() ?? $model->getCode()), ActivityLogConst::LEVEL_SUCCESS);

        return $apiResult->successResponse([], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    public function stopProfile(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        [$model, $errorResponse] = $this->loadOwnedProfile($payload, $apiResult);
        if ($errorResponse) {
            return $errorResponse;
        }

        // Hook: đóng Chrome instance, giải phóng tài nguyên.
        $this->performStop($model);

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $mapper->updateAttrs($model, ['status' => BrowserProfileConst::STATUS_STOPPED]);

        $this->logActivity($model, 'Dừng trình duyệt', 'Dừng — ' . ($model->getProfileName() ?? $model->getCode()), ActivityLogConst::LEVEL_INFO);

        return $apiResult->successResponse([], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /** stopProfile → startProfile, giữ nguyên fingerprint/proxy/UA. */
    public function restartProfile(array $payload = []): JsonResponse
    {
        $stopResult = $this->stopProfile($payload);
        if ($stopResult->getVariable('code') !== ApiResultModel::RESPONSE_CODE_SUCCESS) {
            return $stopResult;
        }
        return $this->startProfile($payload);
    }

    /** Mở phiên xem trực tiếp (debug/thủ công) — hook trả về địa chỉ remote-debug. */
    public function openProfile(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        [$model, $errorResponse] = $this->loadOwnedProfile($payload, $apiResult);
        if ($errorResponse) {
            return $errorResponse;
        }

        if ((int)$model->getStatus() !== BrowserProfileConst::STATUS_RUNNING) {
            return $apiResult->errorResponse(['Trình duyệt chưa chạy — vui lòng khởi động trước']);
        }

        $this->logActivity($model, 'Mở trình duyệt', 'Mở phiên xem trực tiếp — ' . ($model->getProfileName() ?? $model->getCode()), ActivityLogConst::LEVEL_INFO);

        // Hook: trả về remote-debug URL / VNC khi driver anti-detect thật được tích hợp.
        return $apiResult->successResponse(['remoteUrl' => null]);
    }

    public function deleteProfile(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        [$model, $errorResponse] = $this->loadOwnedProfile($payload, $apiResult);
        if ($errorResponse) {
            return $errorResponse;
        }

        if ((int)$model->getStatus() === BrowserProfileConst::STATUS_RUNNING) {
            return $apiResult->errorResponse(['Trình duyệt đang chạy — vui lòng dừng trước khi xóa']);
        }
        if ($model->getFacebookAccountId()) {
            return $apiResult->errorResponse(['Còn tài khoản Facebook gắn kèm — vui lòng gỡ tài khoản trước']);
        }

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $mapper->deleteBrowserProfile($model);

        $this->logActivity($model, 'Xóa trình duyệt', 'Xóa profile — ' . ($model->getProfileName() ?? $model->getCode()), ActivityLogConst::LEVEL_WARNING);

        return $apiResult->successResponse([], [AppMessage::DELETE_SUCCESSFULLY]);
    }

    private function loadOwnedProfile(array $payload, ApiResultModel $apiResult): array
    {
        $filter = new BrowserProfileIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return [null, $apiResult->errorInvalidFormResponse($filter->getMessagesArr())];
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return [null, $apiResult->errorPage401Response([AppMessage::COMMON_401])];
        }

        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $model  = new BrowserProfileModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getBrowserProfile($model)) {
            return [null, $apiResult->errorData404Response([AppMessage::NO_DATA])];
        }

        return [$model, null];
    }

    private function logActivity(BrowserProfileModel $model, string $type, string $message, int $level): void
    {
        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $model->getUserId(),
            'browserProfile:' . $model->getId(),
            $type,
            $message,
            $level
        );
    }

    /** Hook: khởi tạo Chrome instance thật (headless, proxy, fingerprint, navigator.webdriver=false). */
    private function performStart(BrowserProfileModel $model): void
    {
    }

    /** Hook: đóng Chrome instance thật, giải phóng tài nguyên. */
    private function performStop(BrowserProfileModel $model): void
    {
    }

    // =========================================================================
    // ResourceMonitor (agent trên mỗi server) — cron mỗi 30s

    /** Cập nhật cpu_percent/ram_mb/uptime của 1 profile (đẩy từ agent trên server). */
    public function reportResource(int $profileId, float $cpuPercent, int $ramMb, int $uptimeMinutes): void
    {
        $model = new BrowserProfileModel();
        $model->setId($profileId);
        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        if (! $mapper->getBrowserProfile($model)) {
            return;
        }
        $mapper->updateAttrs($model, [
            'cpuPercent'   => $cpuPercent,
            'ramMb'        => $ramMb,
            'uptimeMinutes' => $uptimeMinutes,
            'lastActiveAt' => DateModel::getCurrentDateTime(),
        ]);
    }

    /** Profile khai `running` nhưng process chết → set lỗi (dùng offline), cho phép restart. */
    public function detectCrashedProfiles(): void
    {
        // Hook: agent giám sát process thật đẩy sự kiện; hiện chưa có driver anti-detect thật.
    }

    // =========================================================================
    // Orchestrator — chọn profile cho job browser (dùng bởi Posting\PostExecutor)

    /**
     * Chọn profile sẵn sàng cho fanpage cần đăng qua kênh browser.
     * 1. profile gắn với account của fanpage
     * 2. offline → null (chưa hỗ trợ chọn profile dự phòng khác cùng account)
     * 3. stopped → startProfile()
     * 4. trả về profile sẵn sàng (rate limit theo account — hook PostExecutor tự kiểm)
     */
    public function pickProfileForJob(int $browserProfileId): ?array
    {
        $model = new BrowserProfileModel();
        $model->setId($browserProfileId);
        $mapper = $this->getContainerEntry(BrowserProfileMapper::class);
        if (! $mapper->getBrowserProfile($model)) {
            return null;
        }

        if ((int)$model->getStatus() === BrowserProfileConst::STATUS_OFFLINE) {
            return null;
        }
        if ((int)$model->getStatus() === BrowserProfileConst::STATUS_STOPPED) {
            $this->performStart($model);
            $mapper->updateAttrs($model, [
                'status'    => BrowserProfileConst::STATUS_RUNNING,
                'startedAt' => DateModel::getCurrentDateTime(),
            ]);
        }

        return $model->getRespBrowserProfile();
    }
}
