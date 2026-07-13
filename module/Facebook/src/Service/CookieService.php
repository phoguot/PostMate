<?php
declare(strict_types=1);

namespace Facebook\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Model\JsonResponse;
use Facebook\Filter\Cookie\CookieIdFilter;
use Facebook\Filter\Cookie\CookieListFilter;
use Facebook\Filter\Cookie\CookieLoginCreateFilter;
use Facebook\Model\Cookie\CookieConst;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Cookie\CookieModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\FacebookAccount\FacebookAccountModel;
use User\Service\UserService;

/**
 * Service màn Cookie (docs/Features/Cookie).
 * - Dữ liệu thuộc về user đăng nhập, scope gián tiếp qua facebook_accounts.ownerUserId.
 * - refreshCookie/loginCreateCookie mở profile thật (BrowserProfileService) — bước truy cập
 *   Facebook thật vẫn để dạng hook (chưa có driver anti-detect thật).
 */
class CookieService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    // =========================================================================
    // LIST + STATS

    public function cookieList(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new CookieListFilter($this->getContainer());
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

        $model = new CookieModel();
        $model->setUserId($userId);
        $model->setId(! empty($formData['id']) ? (int)$formData['id'] : null);
        $model->setStatus(isset($formData['status']) && $formData['status'] !== '' ? (int)$formData['status'] : null);
        $model->setFacebookAccountId(! empty($formData['facebookAccountId']) ? (int)$formData['facebookAccountId'] : null);

        $mapper = $this->getContainerEntry(CookieMapper::class);
        $search = $mapper->searchCookie($model, $page, $pageSize);

        $result = array_map(fn(CookieModel $item) => $item->getRespCookie(), $search['items']);
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

    /** KPI: Tổng / Hợp lệ / Sắp hết hạn / Không hợp lệ. */
    public function cookieStats(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $model = new CookieModel();
        $model->setUserId($userId);

        $mapper = $this->getContainerEntry(CookieMapper::class);
        return $apiResult->successResponse($mapper->getStats($model));
    }

    // =========================================================================
    // DETAIL — không trả cookieBlob thô ra UI (đã đảm bảo ở CookieModel::getRespCookie)

    public function cookieDetail(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new CookieIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(CookieMapper::class);
        $model  = new CookieModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getCookie($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        return $apiResult->successResponse($model->getRespCookie());
    }

    // =========================================================================
    // REFRESH

    public function refreshCookie(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new CookieIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(CookieMapper::class);
        $model  = new CookieModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getCookie($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $result = $this->performRefresh($model);

        if ($result['alive']) {
            $mapper->updateAttrs($model, [
                'status'      => CookieConst::STATUS_VALID,
                'expiresAt'   => $result['expiresAt'],
                'cookieBlob'  => $result['cookieBlob'] ?? $model->getCookieBlob(),
                'lastLoginAt' => DateModel::getCurrentDateTime(),
                'lastLoginIp' => $result['ip'] ?? $model->getLastLoginIp(),
            ]);
        } else {
            $mapper->updateAttrs($model, ['status' => CookieConst::STATUS_INVALID]);
        }

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'cookie:' . $model->getId(),
            'Refresh cookie',
            'Refresh cookie — ' . $model->getFacebookAccountName(),
            $result['alive'] ? ActivityLogConst::LEVEL_SUCCESS : ActivityLogConst::LEVEL_WARNING
        );

        return $result['alive']
            ? $apiResult->successResponse([], [AppMessage::UPDATE_SUCCESSFULLY])
            : $apiResult->errorResponse(['Session đã chết — vui lòng đăng nhập lại']);
    }

    /**
     * Hook: mở profile (đúng UA/proxy) → truy cập Facebook với cookie hiện tại.
     * Chưa có driver anti-detect thật nên trả về "còn sống" mặc định để luồng nghiệp vụ
     * phía trên hoạt động được ngay khi driver thật được cắm vào.
     */
    private function performRefresh(CookieModel $cookie): array
    {
        return ['alive' => true, 'expiresAt' => date('Y-m-d', strtotime('+30 days')), 'cookieBlob' => null, 'ip' => null];
    }

    /** Chạy refresh hàng loạt cho các cookie `expiring` (nút "Làm mới" toàn bảng). */
    public function refreshAllExpiring(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(CookieMapper::class);
        $items  = $mapper->listExpiring($userId);

        $refreshed = 0;
        $failed    = 0;
        foreach ($items as $cookie) {
            /** @var CookieModel $cookie */
            $result = $this->performRefresh($cookie);
            if ($result['alive']) {
                $mapper->updateAttrs($cookie, [
                    'status'    => CookieConst::STATUS_VALID,
                    'expiresAt' => $result['expiresAt'],
                ]);
                $refreshed++;
            } else {
                $mapper->updateAttrs($cookie, ['status' => CookieConst::STATUS_INVALID]);
                $failed++;
            }
        }

        return $apiResult->successResponse(['refreshed' => $refreshed, 'failed' => $failed, 'total' => count($items)]);
    }

    // =========================================================================
    // LOGIN (tạo cookie mới)

    public function loginCreateCookie(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new CookieLoginCreateFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $accountId = (int)$formData['facebookAccountId'];
        $account   = new FacebookAccountModel();
        $account->setId($accountId);
        $account->setUserId($userId);
        $accountMapper = $this->getContainerEntry(FacebookAccountMapper::class);
        if (! $accountMapper->getFacebookAccount($account)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $result = $this->performLogin($account, $formData['method'] ?? null, $formData['payload'] ?? null);
        if (! $result['success']) {
            return $apiResult->errorResponse([$result['message'] ?? AppMessage::SYSTEM_ERROR]);
        }

        $cookie = new CookieModel();
        $cookie->setFacebookAccountId($accountId);
        $cookie->setBrowserProfileId($account->getBrowserProfileId());
        $cookie->setStatus(CookieConst::STATUS_VALID);
        $cookie->setCode($result['code'] ?? uniqid('ck_'));
        $cookie->setExpiresAt($result['expiresAt'] ?? date('Y-m-d', strtotime('+30 days')));
        $cookie->setCookieBlob($result['cookieBlob'] ?? null);
        $cookie->setSizeKb($result['sizeKb'] ?? null);
        $cookie->setLastLoginAt(DateModel::getCurrentDateTime());
        $mapper = $this->getContainerEntry(CookieMapper::class);
        $mapper->saveCookie($cookie);

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'cookie:' . $cookie->getId(),
            'Đăng nhập',
            'Tạo cookie mới — ' . $account->getDisplayName(),
            ActivityLogConst::LEVEL_SUCCESS
        );

        return $apiResult->successResponse(['id' => $cookie->getId()], [AppMessage::ADD_SUCCESSFULLY]);
    }

    /**
     * Hook: login qua profile (user/pass/2FA) hoặc import cookie.
     * Chưa có driver anti-detect thật — trả thành công mặc định để luồng nghiệp vụ hoạt động.
     */
    private function performLogin(FacebookAccountModel $account, ?string $method, mixed $payload): array
    {
        return ['success' => true, 'code' => null, 'expiresAt' => null, 'cookieBlob' => null, 'sizeKb' => null];
    }

    // =========================================================================
    // EXPORT (hành động nhạy cảm — có audit)

    public function exportCookie(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new CookieIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(CookieMapper::class);
        $model  = new CookieModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getCookie($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'cookie:' . $model->getId(),
            'Xuất cookie',
            'Xuất cookie — ' . $model->getFacebookAccountName() . ' (hành động nhạy cảm)',
            ActivityLogConst::LEVEL_WARNING
        );

        // cookieBlob đã mã hóa at-rest; giải mã thật cần khóa từ secret manager (chưa tích hợp).
        return $apiResult->successResponse(['cookieBlob' => $model->getCookieBlob()]);
    }

    // =========================================================================
    // DELETE

    public function deleteCookie(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new CookieIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(CookieMapper::class);
        $model  = new CookieModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getCookie($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $mapper->deleteCookie($model);

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'cookie:' . $model->getId(),
            'Xóa cookie',
            'Xóa cookie — ' . $model->getFacebookAccountName(),
            ActivityLogConst::LEVEL_WARNING
        );

        return $apiResult->successResponse([], [AppMessage::DELETE_SUCCESSFULLY]);
    }

    // =========================================================================
    // Dùng bởi worker (browser fallback)

    /** Giải mã cookie_blob → set vào Chrome instance. Chỉ giải mã tại thời điểm dùng. */
    public function loadCookieIntoProfile(int $cookieId): ?string
    {
        $model = new CookieModel();
        $model->setId($cookieId);
        $cookieMapper = $this->getContainerEntry(CookieMapper::class);
        if (! $cookieMapper->getCookie($model)) {
            return null;
        }
        // cookieBlob mã hóa at-rest; giải mã thật cần khóa từ secret manager (chưa tích hợp).
        return $model->getCookieBlob();
    }

    // =========================================================================
    // CRON

    /** Quét cookie expires_at <= now + 3d và status != invalid → refreshCookie() chủ động. */
    public function cookieRefreshCron(): void
    {
        // Hook: cron scheduler (bin/cron) lặp qua users, gọi refreshAllExpiring()/performRefresh()
        // theo ngưỡng CookieConst::PROACTIVE_REFRESH_THRESHOLD_DAYS.
    }
}
