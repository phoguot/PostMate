<?php

declare(strict_types=1);

namespace Facebook\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\JsonResponse;
use Facebook\Filter\Fanpage\FanpageIdFilter;
use Facebook\Filter\Fanpage\FanpageListFilter;
use Facebook\Model\Cookie\CookieConst;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Cookie\CookieModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\FacebookAccount\FacebookAccountModel;
use Facebook\Model\Fanpage\FanpageConst;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Infra\Model\BrowserProfile\BrowserProfileConst;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use User\Service\UserService;

/**
 * Service màn Fanpage (docs/Features/Fanpage).
 * - Dữ liệu thuộc về user đăng nhập, scope gián tiếp qua facebook_accounts.ownerUserId.
 * - computeCanPost() là logic dùng chung bởi enqueue (Posting), màn Tạo bài viết (validatePostability)
 *   và chính màn Fanpage.
 */
class FanpageService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    // =========================================================================
    // LIST + STATS

    public function fanpageList(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FanpageListFilter($this->getContainer());
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

        $model = new FanpageModel();
        $model->setUserId($userId);
        $model->setId(! empty($formData['id']) ? (int)$formData['id'] : null);
        $model->setStatus(isset($formData['status']) && $formData['status'] !== '' ? (int)$formData['status'] : null);
        if (! empty($formData['facebookAccountId'])) {
            $model->setFacebookAccountId((int)$formData['facebookAccountId']);
        }

        $model->setOptions(['keyword' => $formData['keyword'] ?? null]);

        $mapper = $this->getContainerEntry(FanpageMapper::class);
        $search = $mapper->searchFanpage($model, $page, $pageSize);

        $result = array_map(fn(FanpageModel $item) => $this->withCanPostReason($item)->getRespFanpage(), $search['items']);
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

    /** KPI: Tổng / Đang hoạt động / Cần đăng nhập lại / Không hoạt động. */
    public function fanpageStats(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $model = new FanpageModel();
        $model->setUserId($userId);

        $mapper = $this->getContainerEntry(FanpageMapper::class);
        return $apiResult->successResponse($mapper->getStats($model));
    }

    // =========================================================================
    // DETAIL

    public function fanpageDetail(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FanpageIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(FanpageMapper::class);
        $model  = new FanpageModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getFanpage($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        return $apiResult->successResponse($this->withCanPostReason($model)->getRespFanpage());
    }

    // =========================================================================
    // computeCanPost — dùng chung bởi enqueue / Tạo bài viết / màn Fanpage

    /**
     * Tính lại khả năng đăng bài của 1 fanpage theo mục 1.2 (Fanpage/NGHIEP_VU.md):
     * - status != active           → false, "Trang không hoạt động"
     * - channel graph_api          → token còn hạn ? true : false, "Token hết hạn"
     * - channel browser            → cookie valid && profile != offline ? true : false, "Cookie/profile lỗi"
     */
    public function computeCanPost(FanpageModel $fanpage): array
    {
        if ((int)$fanpage->getStatus() !== FanpageConst::STATUS_ACTIVE) {
            return ['canPost' => false, 'reason' => 'Trang không hoạt động'];
        }

        if ($fanpage->getApiEnabled()) {
            $tokenExpiresAt = $fanpage->getTokenExpiresAt();
            // Page access token sinh từ long-lived user token thường KHÔNG có hạn (tokenExpiresAt = null):
            // coi là còn hạn khi có token và (chưa set hạn HOẶC hạn còn ở tương lai);
            // chỉ báo hết hạn khi có mốc hạn và mốc đó đã qua.
            $tokenValid = $fanpage->getPageAccessToken()
                && (! $tokenExpiresAt || strtotime($tokenExpiresAt) > time());
            return $tokenValid
                ? ['canPost' => true, 'reason' => null]
                : ['canPost' => false, 'reason' => 'Token hết hạn'];
        }

        if (! $fanpage->getBrowserProfileId()) {
            return ['canPost' => false, 'reason' => 'Chưa gắn trình duyệt'];
        }

        $cookieModel = new CookieModel();
        $cookieModel->setFacebookAccountId($fanpage->getFacebookAccountId());
        $latestCookieMapper = $this->getContainerEntry(CookieMapper::class);
        $latestCookie = $latestCookieMapper->getLatestByAccountIds([$fanpage->getFacebookAccountId()]);
        $cookieStatus = $latestCookie[$fanpage->getFacebookAccountId()]['status'] ?? null;

        $browserProfileMapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $profileInfo = $browserProfileMapper->getInfoMapByIds([$fanpage->getBrowserProfileId()]);
        $profileStatus = $profileInfo[$fanpage->getBrowserProfileId()]['status'] ?? null;

        $ok = $cookieStatus === CookieConst::STATUS_VALID && $profileStatus !== BrowserProfileConst::STATUS_OFFLINE;
        return $ok
            ? ['canPost' => true, 'reason' => null]
            : ['canPost' => false, 'reason' => 'Cookie/profile lỗi'];
    }

    private function withCanPostReason(FanpageModel $model): FanpageModel
    {
        $check = $this->computeCanPost($model);
        $model->setCanPost($check['canPost']);
        $model->setCanPostReason($check['reason']);
        return $model;
    }

    /** Tính lại can_post cho toàn bộ fanpage thuộc 1 tài khoản (sau login lại / refresh token). */
    public function recomputeCanPostForAccount(int $accountId): void
    {
        $model = new FanpageModel();
        $model->setFacebookAccountId($accountId);
        $mapper = $this->getContainerEntry(FanpageMapper::class);
        $search = $mapper->searchFanpage($model, 1, 500);

        foreach ($search['items'] as $fanpage) {
            /** @var FanpageModel $fanpage */
            $check = $this->computeCanPost($fanpage);
            $mapper->updateCanPost((int)$fanpage->getId(), (bool)$check['canPost']);
        }
    }

    // =========================================================================
    // RE-LOGIN (ủy quyền FacebookAccountService::reLogin)

    public function reLoginFromFanpage(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FanpageIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(FanpageMapper::class);
        $model  = new FanpageModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getFanpage($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $accountService = $this->getContainerEntry(FacebookAccountService::class);
        $result = $accountService->reLogin(['id' => $model->getFacebookAccountId()]);

        $this->recomputeCanPostForAccount((int)$model->getFacebookAccountId());

        return $result;
    }

    // =========================================================================
    // UNLINK

    public function unlinkFanpage(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FanpageIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(FanpageMapper::class);
        $model  = new FanpageModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getFanpage($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        // 1. Hủy job scheduled của fanpage. 2. Thu hồi token (nếu API) — token bị xóa cùng row.
        $mapper->cancelJobsForFanpage((int)$model->getId());
        $mapper->unlinkFanpage($model);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'fanpage:' . $model->getId(),
            'Gỡ liên kết fanpage',
            'Gỡ liên kết fanpage — ' . $model->getName(),
            ActivityLogConst::LEVEL_WARNING
        );

        return $apiResult->successResponse([], [AppMessage::DELETE_SUCCESSFULLY]);
    }

    // =========================================================================
    // CRON

    /** Đồng bộ likes_count/followers_count từ FB (API insights hoặc scrape) — hook. */
    public function refreshPageStats(int $fanpageId): void
    {
        // Hook: gọi Graph API insights khi tích hợp thật (mục 6.5 PHAN_TICH_HE_THONG.md).
    }
}
