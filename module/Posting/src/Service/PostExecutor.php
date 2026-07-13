<?php
declare(strict_types=1);

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\DateModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\FacebookAccount\FacebookAccountModel;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Cookie\CookieModel;
use Facebook\Service\FacebookAccountService;
use Facebook\Service\FanpageService;
use Infra\Model\BrowserProfile\BrowserProfileModel;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Infra\Model\Proxy\ProxyMapper;
use Posting\Model\Job\JobMapper;
use Posting\Model\Job\JobModel;
use Posting\Model\Log\ExecutionLogMapper;
use Posting\Model\Post\PostConst;
use Posting\Model\Post\PostMapper;
use Posting\Model\Post\PostMediaMapper;
use Posting\Model\Post\PostMediaModel;
use Posting\Model\Post\PostModel;

/**
 * Worker thực thi 1 job đăng bài (LichDang/HAM_XU_LY.md::PostExecutor).
 * - Claim post nguyên tử (scheduled → processing) để nhiều worker không đăng trùng.
 * - Đăng qua kênh: browser → BrowserAgentClient; graph_api → (hook, bổ sung sau).
 * - Thất bại: phân loại + retry/backoff (re-enqueue) hoặc đánh dấu failed.
 */
class PostExecutor extends AppServiceFactory
{
    private const BACKOFF_BASE_SEC = 60;

    /** Entry point worker cho 1 job. */
    public function executeJob(JobModel $job): void
    {
        $postId    = (int)$job->getPostId();
        $postMapper = $this->getContainerEntry(PostMapper::class);
        $jobMapper  = $this->getContainerEntry(JobMapper::class);

        // 1. Claim nguyên tử: chỉ 1 worker flip được scheduled → processing.
        if (! $postMapper->claimForProcessing($postId)) {
            // Bài không còn scheduled (đã đăng/đã hủy/worker khác giữ) → coi job xong.
            $jobMapper->markDone((int)$job->getId());
            return;
        }

        $post = new PostModel();
        $post->setId($postId);
        if (! $postMapper->getPost($post)) {
            $jobMapper->markFailed((int)$job->getId(), 'Không tìm thấy bài viết');
            return;
        }

        // 2. Idempotency: lịch native không phải là bài đã publish ngay.
        // Guard này xử lý job cũ còn sót mà không làm sai trạng thái native schedule.
        if ($post->getFbPostId()) {
            if ($this->isNativeScheduledPost($post)) {
                $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_SCHEDULED]);
                $jobMapper->markDone((int)$job->getId());
                return;
            }
            $this->finishPublished($post, $post->getFbPostId(), $job);
            return;
        }

        // 3. Precheck khả năng đăng.
        $check = $this->precheck($post);
        if (! $check['canPost']) {
            $this->handleFailure($post, $job, BrowserAgentClient::ERROR_PERMANENT, $check['reason'] ?? 'Không đủ điều kiện đăng');
            return;
        }

        // 4. Đăng theo kênh.
        $logMapper = $this->getContainerEntry(ExecutionLogMapper::class);
        $logMapper->log($postId, 'Bắt đầu xử lý');

        try {
            $result = $this->publishByChannel($post);
        } catch (\Throwable $e) {
            $this->handleFailure($post, $job, BrowserAgentClient::ERROR_TRANSIENT, $e->getMessage());
            return;
        }

        if (empty($result['success']) || empty($result['fbPostId'])) {
            $logMapper->log($postId, 'Đăng bài', ExecutionLogMapper::STATUS_FAILED);
            $this->handleFailure($post, $job, $result['errorType'] ?? BrowserAgentClient::ERROR_TRANSIENT, $result['error'] ?? 'Đăng thất bại');
            return;
        }

        $logMapper->log($postId, 'Đăng bài', ExecutionLogMapper::STATUS_SUCCESS);
        $this->finishPublished($post, (string)$result['fbPostId'], $job);
    }

    // -------------------------------------------------------------------------

    /** Precheck ủy quyền computeCanPost theo đích đăng. */
    private function precheck(PostModel $post): array
    {
        if ((int)$post->getTargetType() === PostConst::TARGET_PROFILE) {
            $account = new FacebookAccountModel();
            $account->setId((int)$post->getFacebookAccountId());
            $accountMapper = $this->getContainerEntry(FacebookAccountMapper::class);
            if (! $accountMapper->getFacebookAccount($account)) {
                return ['canPost' => false, 'reason' => 'Không tìm thấy tài khoản'];
            }
            $accountService = $this->getContainerEntry(FacebookAccountService::class);
            return $accountService->computeCanPost($account);
        }

        $fanpage = new FanpageModel();
        $fanpage->setId((int)$post->getFanpageId());
        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        if (! $fanpageMapper->getFanpage($fanpage)) {
            return ['canPost' => false, 'reason' => 'Không tìm thấy fanpage'];
        }
        $fanpageService = $this->getContainerEntry(FanpageService::class);
        return $fanpageService->computeCanPost($fanpage);
    }

    /** Đăng theo channel. Graph API → GraphPublisher (fanpage); browser → agent Chrome. */
    private function publishByChannel(PostModel $post): array
    {
        $media = $this->loadMedia((int)$post->getId());

        if ((int)$post->getChannel() === PostConst::CHANNEL_GRAPH_API) {
            // Chỉ fanpage có apiEnabled đi kênh này (trang cá nhân luôn browser — resolveChannel).
            $graphPublisher = $this->getContainerEntry(GraphPublisher::class);
            return $graphPublisher->publish($post, $media);
        }

        $context = array_merge([
            'content' => (string)$post->getContent(),
            'media'   => $media,
            'idemKey' => $this->idempotencyKey($post),
        ], $this->buildBrowserContext($post));

        $browserAgentClient = $this->getContainerEntry(BrowserAgentClient::class);
        return $browserAgentClient->publish($post, $context);
    }

    /** Media của bài (bảng post_media) — dùng cho cả 2 kênh. */
    private function loadMedia(int $postId): array
    {
        $mediaModel = new PostMediaModel();
        $mediaModel->setPostId($postId);
        $mediaMapper = $this->getContainerEntry(PostMediaMapper::class);
        $rows = $mediaMapper->getByPostId($mediaModel);

        $media = [];
        foreach ($rows as $m) {
            /** @var PostMediaModel $m */
            $media[] = ['type' => $m->getType(), 'url' => $m->getUrl(), 'storagePath' => $m->getStoragePath()];
        }
        return $media;
    }

    /**
     * Ngữ cảnh cho agent Chrome: tài khoản + cookie mới nhất + browser profile + proxy.
     * (TrinhDuyet/HAM_XU_LY.md — PHP chỉ gửi ngữ cảnh, agent tự điều khiển Chrome.)
     * cookieBlob là bí mật: chỉ đi tới agent qua BrowserAgentClient, tuyệt đối không log.
     */
    private function buildBrowserContext(PostModel $post): array
    {
        // Xác định tài khoản Facebook thực thi: trang cá nhân → trực tiếp; fanpage → chủ trang.
        $accountId = (int)$post->getFacebookAccountId();
        $fbPageId  = null;
        if ((int)$post->getTargetType() === PostConst::TARGET_FANPAGE) {
            $fanpage = new FanpageModel();
            $fanpage->setId((int)$post->getFanpageId());
            $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
            if ($fanpageMapper->getFanpage($fanpage)) {
                $accountId = (int)$fanpage->getFacebookAccountId();
                $fbPageId  = $fanpage->getFbPageId();
            }
        }

        $account = new FacebookAccountModel();
        $account->setId($accountId);
        if (! $accountId) {
            return ['account' => [], 'cookie' => null, 'proxy' => null, 'fbPageId' => $fbPageId];
        }
        $accountMapper = $this->getContainerEntry(FacebookAccountMapper::class);
        if (! $accountMapper->getFacebookAccount($account)) {
            return ['account' => [], 'cookie' => null, 'proxy' => null, 'fbPageId' => $fbPageId];
        }

        // Cookie mới nhất của tài khoản (blob + user-agent lúc login).
        $cookie = null;
        $cookieMapper = $this->getContainerEntry(CookieMapper::class);
        $latestCookieId = $cookieMapper->getLatestIdByAccountId($accountId);
        if ($latestCookieId) {
            $cookieModel = new CookieModel();
            $cookieModel->setId($latestCookieId);
            if ($cookieMapper->getCookie($cookieModel)) {
                $cookie = [
                    'blob'      => $cookieModel->getCookieBlob(),
                    'userAgent' => $cookieModel->getUserAgent(),
                    'device'    => $cookieModel->getDevice(),
                ];
            }
        }

        // Browser profile + proxy gắn với profile.
        $profileData = null;
        $proxyData   = null;
        if ($account->getBrowserProfileId()) {
            $profile = new BrowserProfileModel();
            $profile->setId((int)$account->getBrowserProfileId());
            $browserProfileMapper = $this->getContainerEntry(BrowserProfileMapper::class);
            if ($browserProfileMapper->getBrowserProfile($profile)) {
                $profileData = [
                    'id'            => $profile->getId(),
                    'profileId'     => $profile->getProfileId(),
                    'profileName'   => $profile->getProfileName(),
                    'chromeVersion' => $profile->getChromeVersion(),
                    'os'            => $profile->getOs(),
                    'userAgent'     => $profile->getUserAgent(),
                ];
                if ($profile->getProxyId()) {
                    $proxyMapper = $this->getContainerEntry(ProxyMapper::class);
                    $proxy = $proxyMapper->getById((int)$profile->getProxyId());
                    if ($proxy) {
                        $proxyData = ['ip' => $proxy->getIp(), 'type' => $proxy->getType(), 'country' => $proxy->getCountry()];
                    }
                }
            }
        }

        return [
            'account' => [
                'id'          => $account->getId(),
                'fbUserId'    => $account->getFbUserId(),
                'displayName' => $account->getDisplayName(),
                'email'       => $account->getEmail(),
            ],
            'cookie'   => $cookie,
            'proxy'    => $proxyData,
            'profile'  => $profileData,
            'fbPageId' => $fbPageId,
        ];
    }

    private function finishPublished(PostModel $post, string $fbPostId, JobModel $job): void
    {
        $postMapper = $this->getContainerEntry(PostMapper::class);
        $postMapper->updateAttrsPost($post, [
            'status'      => PostConst::STATUS_PUBLISHED,
            'fbPostId'    => $fbPostId,
            'publishedAt' => DateModel::getCurrentDateTime(),
        ]);
        $jobMapper = $this->getContainerEntry(JobMapper::class);
        $jobMapper->markDone((int)$job->getId());

        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $post->getCreatedById(),
            'post:' . $post->getId(),
            'Đăng bài',
            'Đăng bài thành công — ' . ($post->getTitle() ?: ('#' . $post->getId())),
            ActivityLogConst::LEVEL_SUCCESS
        );
    }

    /**
     * Xử lý thất bại theo loại lỗi:
     * - permanent  → status=failed
     * - checkpoint → account checkpoint + hủy job của account, status=failed
     * - rate_limit → re-enqueue backoff dài, KHÔNG tăng attempt
     * - transient  → attempt++; còn lượt → re-enqueue backoff; hết lượt → failed
     */
    private function handleFailure(PostModel $post, JobModel $job, string $errorType, string $error): void
    {
        $error = $this->safeError($error);
        $postMapper = $this->getContainerEntry(PostMapper::class);
        $jobMapper  = $this->getContainerEntry(JobMapper::class);
        $jobId      = (int)$job->getId();

        $logMapper = $this->getContainerEntry(ExecutionLogMapper::class);
        $logMapper->log((int)$post->getId(), 'Lỗi: ' . $error, ExecutionLogMapper::STATUS_FAILED);

        if ($errorType === BrowserAgentClient::ERROR_CHECKPOINT && (int)$post->getTargetType() === PostConst::TARGET_PROFILE) {
            $accountService = $this->getContainerEntry(FacebookAccountService::class);
            $accountService->markCheckpoint((int)$post->getFacebookAccountId(), $error);
            $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_FAILED]);
            $jobMapper->markFailed($jobId, $error);
            $this->logActivityError($post, $error);
            return;
        }

        if ($errorType === BrowserAgentClient::ERROR_PERMANENT) {
            $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_FAILED]);
            $jobMapper->markFailed($jobId, $error);
            $this->logActivityError($post, $error);
            return;
        }

        if ($errorType === BrowserAgentClient::ERROR_RATE_LIMIT) {
            // Hoãn lâu, KHÔNG tốn attempt, đưa bài về scheduled để job pending nuốt lại.
            $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_SCHEDULED]);
            $jobMapper->reschedule($jobId, $this->backoffAt(4), $error);
            return;
        }

        // transient: tăng attempt.
        $attempt = (int)$post->getAttemptCount() + 1;
        $maxAttempts = (int)($post->getMaxAttempts() ?? PostConst::DEFAULT_MAX_ATTEMPTS);
        $postMapper->updateAttrsPost($post, ['attemptCount' => $attempt]);

        if ($attempt < $maxAttempts) {
            $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_SCHEDULED]);
            $jobMapper->reschedule($jobId, $this->backoffAt($attempt), $error);
            return;
        }

        $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_FAILED]);
        $jobMapper->markFailed($jobId, $error);
        $this->logActivityError($post, $error);
    }

    private function safeError(string $error): string
    {
        $error = trim($error);
        if ($error === '') {
            $error = 'Lỗi không xác định';
        }

        $error = preg_replace('/\s+/', ' ', $error) ?? $error;
        $error = preg_replace('/(access_token=)[^&\s]+/i', '$1[redacted]', $error) ?? $error;
        $error = preg_replace('/(Authorization:\s*Bearer\s+)[^\s]+/i', '$1[redacted]', $error) ?? $error;
        $error = preg_replace('/(cookieBlob|cookie|token)(["\']?\s*[:=]\s*["\']?)[^"\',\s}]+/i', '$1$2[redacted]', $error) ?? $error;

        return mb_substr($error, 0, 240);
    }

    private function logActivityError(PostModel $post, string $error): void
    {
        $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
        $activityLogMapper->log(
            $post->getCreatedById(),
            'post:' . $post->getId(),
            'Đăng bài lỗi',
            'Đăng bài thất bại — ' . $error,
            ActivityLogConst::LEVEL_ERROR
        );
    }

    /** backoff = base * 2^attempt (+ jitter), trả về mốc DATETIME tương lai. */
    private function backoffAt(int $attempt): string
    {
        $delay = self::BACKOFF_BASE_SEC * (2 ** max(0, $attempt)) + random_int(0, 30);
        return date('Y-m-d H:i:s', time() + $delay);
    }

    private function idempotencyKey(PostModel $post): string
    {
        return $post->getId() . ':' . (int)$post->getAttemptCount();
    }

    private function isNativeScheduledPost(PostModel $post): bool
    {
        return (int)$post->getTargetType() === PostConst::TARGET_FANPAGE
            && (int)$post->getChannel() === PostConst::CHANNEL_GRAPH_API
            && (string)$post->getFbPostId() !== ''
            && ! empty($post->getScheduledAt())
            && strtotime((string)$post->getScheduledAt()) > time();
    }
}
