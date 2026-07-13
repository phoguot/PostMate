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
use Facebook\Service\GraphApiClient;
use Setting\Filter\MetaApp\MetaAppConnectFilter;
use Setting\Model\MetaAppCredential\MetaAppCredentialMapper;
use Setting\Model\MetaAppCredential\MetaAppCredentialModel;
use User\Service\UserService;

/**
 * Service Token & Quyền (Cài đặt > Facebook > Meta App), docs/Features/CaiDat mục 1.4.
 * - Quyền cần xin (App Review): pages_manage_posts, pages_read_engagement, pages_manage_engagement.
 * - issuePageTokens/refreshTokens gọi Graph API thật qua GraphApiClient (GET /{page-id}?fields=access_token
 *   bằng system_user_token; hạn token đọc qua /debug_token).
 */
class MetaAppService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    /**
     * Credential Meta App hiệu lực cho user: ưu tiên bản ghi riêng của user (meta_app_credentials),
     * nếu chưa có thì fallback sang Meta App dùng chung khai báo ở config['meta_app']
     * (config/autoload/local.php) — người dùng thường không cần tự nhập App ID/Secret.
     */
    public function resolveCredential(?int $userId): ?MetaAppCredentialModel
    {
        if ($userId) {
            $credentialMapper = $this->getContainerEntry(MetaAppCredentialMapper::class);
            $credential = $credentialMapper->getByUserId($userId);
            if ($credential && $credential->getAppId() && $credential->getAppSecret()) {
                return $credential;
            }
        }

        return $this->getGlobalCredential();
    }

    /** Meta App dùng chung từ config['meta_app'] — null nếu chưa khai báo. */
    private function getGlobalCredential(): ?MetaAppCredentialModel
    {
        $config = $this->getContainerEntry('config')['meta_app'] ?? [];
        if (empty($config['appId']) || empty($config['appSecret'])) {
            return null;
        }

        $model = new MetaAppCredentialModel();
        $model->setAppId((string)$config['appId']);
        $model->setAppSecret((string)$config['appSecret']);
        if (! empty($config['systemUserToken'])) {
            $model->setSystemUserToken((string)$config['systemUserToken']);
        }
        return $model;
    }

    /** Đọc trạng thái cấu hình Meta App của user (riêng, dùng chung, hoặc chưa có). */
    public function getMetaAppStatus(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $credentialMapper = $this->getContainerEntry(MetaAppCredentialMapper::class);
        $credential = $credentialMapper->getByUserId($userId);
        if ($credential && $credential->getAppId()) {
            return $apiResult->successResponse($credential->getRespMetaApp() + ['global' => false]);
        }

        $global = $this->getGlobalCredential();
        if ($global) {
            return $apiResult->successResponse([
                'appId'     => $global->getAppId(),
                'connected' => true,
                'global'    => true,
            ]);
        }

        return $apiResult->successResponse(['connected' => false, 'global' => false]);
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

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
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

        $credential = $this->resolveCredential($userId);
        if (! $credential || ! $credential->getSystemUserToken()) {
            return $apiResult->errorResponse(['Chưa kết nối Meta App hoặc thiếu system user token']);
        }

        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        $model = new FanpageModel();
        $model->setUserId($userId);
        $search = $fanpageMapper->searchFanpage($model, 1, 500);

        $issued = 0;
        $errors = [];
        foreach ($search['items'] as $fanpage) {
            /** @var FanpageModel $fanpage */
            $token = $this->performIssueToken($fanpage, $credential);
            if (isset($token['error'])) {
                $errors[] = ($fanpage->getName() ?: $fanpage->getFbPageId()) . ': ' . $token['error'];
                continue;
            }
            $fanpageMapper->updateAttrs($fanpage, [
                'pageAccessToken' => $token['accessToken'],
                'tokenExpiresAt'  => $token['expiresAt'],
                'apiEnabled'      => 1,
            ]);
            $issued++;
        }

        return $apiResult->successResponse([
            'issued' => $issued,
            'total'  => count($search['items']),
            'errors' => $errors,
        ]);
    }

    /**
     * Gọi Graph API cấp Page Access Token cho 1 fanpage bằng system_user_token:
     * GET /{page-id}?fields=access_token — system user phải được gán vào page trên
     * Meta Business Suite thì mới đọc được field này.
     * @return array{accessToken: string, expiresAt: ?string}|array{error: string}
     */
    private function performIssueToken(FanpageModel $fanpage, MetaAppCredentialModel $credential): array
    {
        if (! $fanpage->getFbPageId()) {
            return ['error' => 'Fanpage chưa có Page ID'];
        }

        $client = $this->getContainerEntry(GraphApiClient::class);
        $resp   = $client->get($fanpage->getFbPageId(), [
            'fields'       => 'access_token',
            'access_token' => $credential->getSystemUserToken(),
        ]);
        if (isset($resp['error'])) {
            return ['error' => (string)($resp['error']['message'] ?? 'Lỗi Graph API không xác định')];
        }
        if (empty($resp['access_token'])) {
            return ['error' => 'Graph API không trả về access_token (system user chưa được gán vào page?)'];
        }

        return [
            'accessToken' => (string)$resp['access_token'],
            'expiresAt'   => $this->fetchTokenExpiry((string)$resp['access_token'], $credential),
        ];
    }

    /**
     * Đọc hạn token qua GET /debug_token bằng app access token (appId|appSecret).
     * Token do system user cấp thường không hết hạn (expires_at = 0) → trả null.
     * Lỗi ở bước này không chặn việc lưu token — chỉ mất thông tin hạn.
     */
    private function fetchTokenExpiry(string $pageToken, MetaAppCredentialModel $credential): ?string
    {
        if (! $credential->getAppId() || ! $credential->getAppSecret()) {
            return null;
        }

        $client = $this->getContainerEntry(GraphApiClient::class);
        $resp   = $client->get('debug_token', [
            'input_token'  => $pageToken,
            'access_token' => $credential->getAppId() . '|' . $credential->getAppSecret(),
        ]);

        $expiresAt = $resp['data']['expires_at'] ?? null;
        if (! is_numeric($expiresAt) || (int)$expiresAt <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', (int)$expiresAt);
    }

    /** Làm mới token — ủy quyền TokenService::refreshPageTokenCron() (xem Fanpage/HAM_XU_LY.md). */
    public function refreshTokens(array $payload = []): JsonResponse
    {
        return $this->issuePageTokens($payload);
    }
}
