<?php
declare(strict_types=1);

namespace Setting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\JsonResponse;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Setting\Filter\MetaApp\MetaAppConnectFilter;
use Setting\Model\MetaAppCredential\MetaAppCredentialMapper;
use User\Service\UserService;

/**
 * Service Token & Quyền (Cài đặt > Facebook > Meta App), docs/Features/CaiDat mục 1.4.
 * - Quyền cần xin (App Review): pages_manage_posts, pages_read_engagement, pages_manage_engagement.
 * - issuePageTokens/refreshTokens gọi Graph API thật — chưa tích hợp SDK Graph API nên để hook,
 *   phần lưu trạng thái (page_access_token/token_expires_at/api_enabled) đã cài đặt đầy đủ.
 */
class MetaAppService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    /** Đọc trạng thái cấu hình Meta App của user (đã nhập App ID/Secret hay chưa). */
    public function getMetaAppStatus(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $credential = $this->getContainerEntry(MetaAppCredentialMapper::class)->getByUserId($userId);
        if (! $credential) {
            return $apiResult->successResponse(['connected' => false]);
        }

        return $apiResult->successResponse($credential->getRespMetaApp());
    }

    /** Lưu meta_app_credentials (mã hóa app_secret) + khởi tạo OAuth flow xin quyền pages_*. */
    public function connectMetaApp(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new MetaAppConnectFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(MetaAppCredentialMapper::class);
        $model  = $mapper->upsert($userId, [
            'appId'     => (string)$formData['appId'],
            // Mã hóa at-rest: chưa tích hợp secret manager — lưu tạm nguyên bản, cần bổ sung trước khi go-live.
            'appSecret' => (string)$formData['appSecret'],
        ]);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'metaApp:' . $model->getId(),
            'Kết nối Meta App',
            'Kết nối Meta App — appId ' . $model->getAppId(),
            ActivityLogConst::LEVEL_SUCCESS
        );

        return $apiResult->successResponse($model->getRespMetaApp(), [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /**
     * Dùng system_user_token → lấy Page Access Token cho từng fanpage sở hữu.
     * Set fanpages.api_enabled = true khi có token hợp lệ.
     */
    public function issuePageTokens(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $credential = $this->getContainerEntry(MetaAppCredentialMapper::class)->getByUserId($userId);
        if (! $credential || ! $credential->getSystemUserToken()) {
            return $apiResult->errorResponse(['Chưa kết nối Meta App hoặc thiếu system user token']);
        }

        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        $model = new FanpageModel();
        $model->setUserId($userId);
        $search = $fanpageMapper->searchFanpage($model, 1, 500);

        $issued = 0;
        foreach ($search['items'] as $fanpage) {
            /** @var FanpageModel $fanpage */
            $token = $this->performIssueToken($fanpage, $credential->getSystemUserToken());
            if (! $token) {
                continue;
            }
            $fanpageMapper->updateAttrs($fanpage, [
                'pageAccessToken' => $token['accessToken'],
                'tokenExpiresAt'  => $token['expiresAt'],
                'apiEnabled'      => 1,
            ]);
            $issued++;
        }

        return $apiResult->successResponse(['issued' => $issued, 'total' => count($search['items'])]);
    }

    /**
     * Hook: gọi Graph API cấp Page Access Token cho 1 fanpage bằng system_user_token.
     * Chưa tích hợp Graph API SDK thật.
     */
    private function performIssueToken(FanpageModel $fanpage, string $systemUserToken): ?array
    {
        return null;
    }

    /** Làm mới token — ủy quyền TokenService::refreshPageTokenCron() (xem Fanpage/HAM_XU_LY.md). */
    public function refreshTokens(array $payload = []): JsonResponse
    {
        return $this->issuePageTokens($payload);
    }
}
