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
use Facebook\Filter\FacebookAccount\FacebookAccountIdFilter;
use Facebook\Filter\FacebookAccount\FacebookAccountListFilter;
use Facebook\Model\Cookie\CookieConst;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Cookie\CookieModel;
use Facebook\Model\FacebookAccount\FacebookAccountConst;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\FacebookAccount\FacebookAccountModel;
use Facebook\Model\Fanpage\FanpageConst;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Infra\Model\BrowserProfile\BrowserProfileConst;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Laminas\Http\Response;
use Laminas\Session\Container as SessionContainer;
use Setting\Service\MetaAppService;
use User\Service\UserService;

/**
 * Service màn Tài khoản Facebook (docs/Features/TaiKhoanFacebook).
 * - Dữ liệu thuộc về user đăng nhập: mọi thao tác scope theo ownerUserId.
 * - reLogin/markCheckpoint điều khiển vòng đời qua profile trình duyệt (BrowserProfileService)
 *   — bước mở Chrome + đăng nhập thật vẫn để dạng hook (chưa có driver anti-detect thật).
 * - connectAccount/handleOAuthCallback: Facebook Login OAuth THẬT (Graph API), dùng App ID/Secret
 *   đã cấu hình ở Cài đặt > Facebook > Token & Quyền (Setting\Model\MetaAppCredential).
 */
class FacebookAccountService extends AppServiceFactory
{
    private const GRAPH_VERSION = 'v21.0';
    private const GRAPH_BASE = 'https://graph.facebook.com';
    private const OAUTH_DIALOG_URL = 'https://www.facebook.com/' . self::GRAPH_VERSION . '/dialog/oauth';
    private const OAUTH_SESSION_NAMESPACE = 'fbOauthConnect';
    private const OAUTH_SCOPES = [
        'public_profile',
        'email',
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'pages_manage_metadata',
        'pages_messaging',
        'read_insights',
    ];

    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    // =========================================================================
    // LIST + STATS

    public function accountList(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FacebookAccountListFilter($this->getContainer());
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

        $model = new FacebookAccountModel();
        $model->setUserId($userId);
        $model->setId(! empty($formData['id']) ? (int)$formData['id'] : null);
        $model->setStatus(isset($formData['status']) && $formData['status'] !== '' ? (int)$formData['status'] : null);
        $model->setOptions(['keyword' => $formData['keyword'] ?? null]);

        $mapper = $this->getContainerEntry(FacebookAccountMapper::class);
        $search = $mapper->searchFacebookAccount($model, $page, $pageSize);

        $result = array_map(fn(FacebookAccountModel $item) => $item->getRespFacebookAccount(), $search['items']);
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

    /** KPI: Tổng / Đang hoạt động / Cookie sắp hết hạn / Gặp vấn đề. */
    public function accountStats(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $model = new FacebookAccountModel();
        $model->setUserId($userId);

        $mapper = $this->getContainerEntry(FacebookAccountMapper::class);
        return $apiResult->successResponse($mapper->getStats($model));
    }

    // =========================================================================
    // DETAIL

    public function accountDetail(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FacebookAccountIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(FacebookAccountMapper::class);
        $model  = new FacebookAccountModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getFacebookAccount($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $data = $model->getRespFacebookAccount();
        $data['fanpages']     = $this->getLinkedFanpages($model->getId());
        $data['loginSessions'] = $this->getLoginSessions($model->getId());
        $data['activityLogs']  = $this->getActivityLogs($model->getId());

        return $apiResult->successResponse($data);
    }

    private function getLinkedFanpages(int $accountId): array
    {
        $fpModel = new FanpageModel();
        $fpModel->setFacebookAccountId($accountId);
        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        $search = $fanpageMapper->searchFanpage($fpModel, 1, 100);
        return array_map(fn(FanpageModel $fp) => $fp->getRespFanpage(), $search['items']);
    }

    private function getLoginSessions(int $accountId): array
    {
        $ckModel = new CookieModel();
        $ckModel->setFacebookAccountId($accountId);
        $cookieMapper = $this->getContainerEntry(CookieMapper::class);
        $search = $cookieMapper->searchCookie($ckModel, 1, 50);
        return array_map(fn(CookieModel $ck) => $ck->getRespCookie(), $search['items']);
    }

    private function getActivityLogs(int $accountId): array
    {
        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $logs = $activityLogMapper->listByEntity('facebookAccount:' . $accountId, 50);
        return array_map(fn($log) => $log->getRespActivityLog(), $logs);
    }

    // =========================================================================
    // CONNECT (kết nối tài khoản Facebook mới bằng OAuth Facebook Login thật —
    // popup xin quyền, docs/Design/popup_connect_facebook.png)

    /**
     * Bước 1: dựng URL cấp quyền OAuth thật của Facebook (facebook.com/dialog/oauth) để FE điều
     * hướng trình duyệt sang. Cần đã cấu hình App ID/Secret ở Cài đặt > Facebook > Token & Quyền.
     */
    public function connectAccount(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $metaAppService = $this->getContainerEntry(MetaAppService::class);
        $credential = $metaAppService->resolveCredential($userId);
        if (! $credential) {
            return $apiResult->errorResponse([
                'Chưa cấu hình Meta App (App ID/App Secret) — vào Cài đặt > Facebook > Token & Quyền để kết nối trước',
            ]);
        }

        $state = bin2hex(random_bytes(16));
        $session = new SessionContainer(self::OAUTH_SESSION_NAMESPACE);
        $session->offsetSet('state', $state);
        $session->offsetSet('userId', $userId);

        $authorizeUrl = self::OAUTH_DIALOG_URL . '?' . http_build_query([
            'client_id'     => $credential->getAppId(),
            'redirect_uri'  => $this->getRedirectUri(),
            'state'         => $state,
            'scope'         => implode(',', self::OAUTH_SCOPES),
            'response_type' => 'code',
        ]);

        return $apiResult->successResponse(['authorizeUrl' => $authorizeUrl]);
    }

    /**
     * Bước 2: Facebook redirect trình duyệt về đây sau khi người dùng cấp quyền (GET code/state).
     * Đổi code lấy access token thật, gọi Graph API /me + /me/accounts, lưu facebook_accounts +
     * fanpages với dữ liệu thật, rồi redirect người dùng về lại Settings > Facebook.
     */
    public function handleOAuthCallback(array $query): Response
    {
        if (! empty($query['error'])) {
            return $this->redirectToFrontend([
                'fbConnect' => 'error',
                'message'   => $query['error_description'] ?? $query['error'],
            ]);
        }

        $code  = $query['code'] ?? null;
        $state = $query['state'] ?? null;
        if (! $code || ! $state) {
            return $this->redirectToFrontend(['fbConnect' => 'error', 'message' => 'Thiếu code/state từ Facebook']);
        }

        $session = new SessionContainer(self::OAUTH_SESSION_NAMESPACE);
        $expectedState = $session->offsetExists('state') ? $session->offsetGet('state') : null;
        $userId = $session->offsetExists('userId') ? (int)$session->offsetGet('userId') : null;
        if (! $expectedState || ! $userId || ! hash_equals((string)$expectedState, (string)$state)) {
            return $this->redirectToFrontend([
                'fbConnect' => 'error',
                'message'   => 'Phiên xác thực không hợp lệ hoặc đã hết hạn — vui lòng thử kết nối lại',
            ]);
        }
        $session->offsetUnset('state');

        $metaAppService = $this->getContainerEntry(MetaAppService::class);
        $credential = $metaAppService->resolveCredential($userId);
        if (! $credential) {
            return $this->redirectToFrontend(['fbConnect' => 'error', 'message' => 'Chưa cấu hình Meta App']);
        }

        $redirectUri = $this->getRedirectUri();

        $shortToken = $this->graphGet('/' . self::GRAPH_VERSION . '/oauth/access_token', [
            'client_id'     => $credential->getAppId(),
            'client_secret' => $credential->getAppSecret(),
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);
        if (! $shortToken || empty($shortToken['access_token'])) {
            return $this->redirectToFrontend([
                'fbConnect' => 'error',
                'message'   => $shortToken['error']['message'] ?? 'Không đổi được mã xác thực lấy access token',
            ]);
        }

        $longToken = $this->graphGet('/' . self::GRAPH_VERSION . '/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $credential->getAppId(),
            'client_secret'     => $credential->getAppSecret(),
            'fb_exchange_token' => $shortToken['access_token'],
        ]);
        $userAccessToken = $longToken['access_token'] ?? $shortToken['access_token'];
        $expiresIn = $longToken['expires_in'] ?? $shortToken['expires_in'] ?? null;

        $profile = $this->graphGet('/' . self::GRAPH_VERSION . '/me', [
            'fields'       => 'id,name,email,picture.type(large)',
            'access_token' => $userAccessToken,
        ]);
        if (! $profile || empty($profile['id'])) {
            return $this->redirectToFrontend([
                'fbConnect' => 'error',
                'message'   => $profile['error']['message'] ?? 'Không lấy được thông tin tài khoản Facebook',
            ]);
        }

        $mapper   = $this->getContainerEntry(FacebookAccountMapper::class);
        $existing = $mapper->getByFbUserId($userId, (string)$profile['id']);

        $model = $existing ?: new FacebookAccountModel();
        $model->setOwnerUserId($userId);
        $model->setFbUserId((string)$profile['id']);
        $model->setDisplayName($profile['name'] ?? 'Tài khoản Facebook');
        $model->setEmail($profile['email'] ?? null);
        $model->setAvatarUrl($profile['picture']['data']['url'] ?? null);
        $model->setStatus(FacebookAccountConst::STATUS_ACTIVE);
        $model->setUserAccessToken($userAccessToken);
        $model->setExpiresAt($expiresIn ? date('Y-m-d H:i:s', time() + (int)$expiresIn) : null);
        $model->setLastLoginAt(DateModel::getCurrentDateTime());
        $model->setLastLoginIp($_SERVER['REMOTE_ADDR'] ?? null);
        if (! $existing) {
            $countModel = new FacebookAccountModel();
            $countModel->setUserId($userId);
            $existingTotal = (int)$mapper->searchFacebookAccount($countModel, 1, 1)['total'];
            $model->setIsPrimary($existingTotal === 0);
            $model->setCanPost(true);
            $model->setCanUpload(true);
            $model->setCanComment(true);
            $model->setCanReply(true);
            $model->setCanInbox(true);
        }
        $mapper->saveFacebookAccount($model);

        $pageCount = $this->syncManagedPages($model, $userAccessToken);

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'facebookAccount:' . $model->getId(),
            'Kết nối tài khoản',
            'Kết nối tài khoản Facebook qua OAuth — ' . $model->getDisplayName()
                . ($pageCount ? " ({$pageCount} fanpage)" : ''),
            ActivityLogConst::LEVEL_SUCCESS
        );

        return $this->redirectToFrontend(['fbConnect' => 'success', 'accountId' => $model->getId()]);
    }

    /** Gọi /me/accounts lấy các fanpage người dùng quản lý, upsert kèm page access token thật. */
    private function syncManagedPages(FacebookAccountModel $account, string $userAccessToken): int
    {
        $result = $this->graphGet('/' . self::GRAPH_VERSION . '/me/accounts', [
            'fields'       => 'id,name,category,link,access_token,fan_count,followers_count',
            'access_token' => $userAccessToken,
            'limit'        => 100,
        ]);
        if (empty($result['data']) || ! is_array($result['data'])) {
            return 0;
        }

        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        $count = 0;
        foreach ($result['data'] as $page) {
            if (empty($page['id'])) {
                continue;
            }
            $fpModel = $fanpageMapper->getByFbPageId((string)$page['id']) ?: new FanpageModel();
            $fpModel->setFbPageId((string)$page['id']);
            $fpModel->setName($page['name'] ?? '');
            $fpModel->setCategory($page['category'] ?? null);
            $fpModel->setUrl($page['link'] ?? null);
            $fpModel->setFacebookAccountId($account->getId());
            $fpModel->setStatus(FanpageConst::STATUS_ACTIVE);
            $fpModel->setLikesCount($page['fan_count'] ?? null);
            $fpModel->setFollowersCount($page['followers_count'] ?? null);
            $fpModel->setPageAccessToken($page['access_token'] ?? null);
            $fpModel->setApiEnabled(! empty($page['access_token']));
            if ($fpModel->getCanPost() === null) {
                $fpModel->setCanPost(true);
            }
            $fanpageMapper->saveFanpage($fpModel);
            $count++;
        }
        return $count;
    }

    /** redirect_uri phải khớp CHÍNH XÁC với Valid OAuth Redirect URIs khai báo trong Facebook App. */
    private function getRedirectUri(): string
    {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/api/facebook/account/oauthcallback';
    }

    /** Redirect trình duyệt về lại Settings > Facebook kèm kết quả kết nối qua query string. */
    private function redirectToFrontend(array $query): Response
    {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url    = $scheme . '://' . $host . '/settings?' . http_build_query(array_merge(['tab' => 'facebook'], $query));

        $response = new Response();
        $response->setStatusCode(302);
        $response->getHeaders()->addHeaderLine('Location', $url);
        return $response;
    }

    /** GET Graph API thật qua cURL. Trả về mảng decode JSON (kể cả khi Graph trả lỗi), null nếu request thất bại. */
    private function graphGet(string $path, array $query): ?array
    {
        $url = self::GRAPH_BASE . $path . '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    // =========================================================================
    // computeCanPost — dùng khi đăng lên trang cá nhân (Posting::validatePostability)

    /**
     * Khả năng đăng bài lên trang cá nhân (đăng qua browser automation):
     * - status != active            → false, "Tài khoản không hoạt động"
     * - capability canPost = false  → false, "Tài khoản không được phép đăng"
     * - chưa gắn trình duyệt        → false, "Chưa gắn trình duyệt"
     * - cookie valid && profile != offline ? true : false, "Cookie/profile lỗi"
     */
    public function computeCanPost(FacebookAccountModel $account): array
    {
        if ((int)$account->getStatus() !== FacebookAccountConst::STATUS_ACTIVE) {
            return ['canPost' => false, 'reason' => 'Tài khoản không hoạt động'];
        }
        if (! $account->getCanPost()) {
            return ['canPost' => false, 'reason' => 'Tài khoản không được phép đăng'];
        }
        if (! $account->getBrowserProfileId()) {
            return ['canPost' => false, 'reason' => 'Chưa gắn trình duyệt'];
        }

        $cookieMapper = $this->getContainerEntry(CookieMapper::class);
        $latestCookie = $cookieMapper->getLatestByAccountIds([$account->getId()]);
        $cookieStatus = $latestCookie[$account->getId()]['status'] ?? null;

        $browserProfileMapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $profileInfo = $browserProfileMapper->getInfoMapByIds([$account->getBrowserProfileId()]);
        $profileStatus = $profileInfo[$account->getBrowserProfileId()]['status'] ?? null;

        $ok = $cookieStatus === CookieConst::STATUS_VALID && $profileStatus !== BrowserProfileConst::STATUS_OFFLINE;
        return $ok
            ? ['canPost' => true, 'reason' => null]
            : ['canPost' => false, 'reason' => 'Cookie/profile lỗi'];
    }

    // =========================================================================
    // RE-LOGIN

    public function reLogin(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FacebookAccountIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(FacebookAccountMapper::class);
        $model  = new FacebookAccountModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getFacebookAccount($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        // Bước 1-2: mở profile (đúng UA/proxy/fingerprint) + thực hiện đăng nhập.
        $loginResult = $this->performLogin($model, $payload['credentials'] ?? null);

        if (! $loginResult['success']) {
            return $apiResult->errorResponse([$loginResult['message'] ?? AppMessage::SYSTEM_ERROR]);
        }

        $mapper->updateAttrs($model, [
            'status'      => FacebookAccountConst::STATUS_ACTIVE,
            'lastLoginAt' => DateModel::getCurrentDateTime(),
            'lastLoginIp' => $loginResult['ip'] ?? $model->getLastLoginIp(),
            'device'      => $loginResult['device'] ?? $model->getDevice(),
        ]);

        if (! empty($loginResult['cookie'])) {
            $cookieModel = new CookieModel();
            $cookieModel->setFacebookAccountId($model->getId());
            $cookieModel->setBrowserProfileId($model->getBrowserProfileId());
            $cookieModel->setStatus(CookieConst::STATUS_VALID);
            $cookieModel->exchangeArray($loginResult['cookie']);
            $cookieMapper = $this->getContainerEntry(CookieMapper::class);
            $cookieMapper->saveCookie($cookieModel);
        }

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'facebookAccount:' . $model->getId(),
            'Đăng nhập lại',
            'Đăng nhập lại — ' . $model->getDisplayName(),
            ActivityLogConst::LEVEL_SUCCESS
        );

        return $apiResult->successResponse([], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /**
     * Hook: mở profile trình duyệt (đúng UA/proxy/fingerprint đã lưu) và thực hiện đăng nhập
     * (cookie mới / user-pass + 2FA / chờ xác minh checkpoint). Chưa có driver anti-detect thật
     * nên trả về thành công mặc định để luồng nghiệp vụ phía trên hoạt động được ngay khi driver
     * thật được cắm vào (xem TrinhDuyet/HAM_XU_LY.md::startProfile / openProfile).
     */
    private function performLogin(FacebookAccountModel $account, ?array $credentials): array
    {
        return ['success' => true, 'ip' => null, 'device' => null, 'cookie' => null];
    }

    // =========================================================================
    // CHECKPOINT (gọi từ PostExecutor khi phát hiện FB yêu cầu xác minh giữa chừng)

    public function markCheckpoint(int $accountId, string $reason): void
    {
        $mapper = $this->getContainerEntry(FacebookAccountMapper::class);
        $model  = new FacebookAccountModel();
        $model->setId($accountId);
        if (! $mapper->getFacebookAccount($model)) {
            return;
        }

        $mapper->updateAttrs($model, ['status' => FacebookAccountConst::STATUS_CHECKPOINT]);
        $mapper->cancelJobsForAccount($accountId);

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $model->getOwnerUserId(),
            'facebookAccount:' . $accountId,
            'Checkpoint',
            'Tài khoản gặp checkpoint — ' . $reason,
            ActivityLogConst::LEVEL_WARNING
        );
    }

    // =========================================================================
    // DELETE

    public function deleteAccount(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new FacebookAccountIdFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(FacebookAccountMapper::class);
        $model  = new FacebookAccountModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getFacebookAccount($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $fanpageCount = (int)($model->getFanpageCount() ?? 0);
        $confirmCascade = ! empty($payload['confirmCascade']);
        if ($fanpageCount > 0 && ! $confirmCascade) {
            return $apiResult->errorResponse(['Tài khoản còn ' . $fanpageCount . ' fanpage liên kết — vui lòng gỡ liên kết trước hoặc xác nhận xóa cùng fanpage']);
        }

        // cookies (CASCADE) và fanpages (CASCADE khi confirmCascade) được DB tự dọn theo FK;
        // browser_profiles.facebookAccountId tự SET NULL theo FK.
        $mapper->deleteAccount($model);

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $userId,
            'facebookAccount:' . $model->getId(),
            'Xóa tài khoản',
            'Xóa tài khoản — ' . $model->getDisplayName(),
            ActivityLogConst::LEVEL_WARNING
        );

        return $apiResult->successResponse([], [AppMessage::DELETE_SUCCESSFULLY]);
    }

    // =========================================================================
    // CRON

    /** Quét cookie sắp hết hạn → cập nhật KPI; account có cookie invalid → chặn nhận job. */
    public function checkCookieHealthCron(): void
    {
        // Đọc trực tiếp qua CookieMapper theo từng user khi cron chạy theo lịch;
        // hiện để hook — cron scheduler (bin/cron) sẽ gọi getStats()/countExpiringByAccountIds() theo lô.
    }
}
