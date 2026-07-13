<?php
declare(strict_types=1);

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Posting\Model\Job\JobMapper;
use Posting\Model\Job\JobModel;
use Posting\Model\Log\ExecutionLogMapper;
use Posting\Model\Post\PostConst;
use Posting\Model\Post\PostMapper;
use Posting\Model\Post\PostModel;

/**
 * Hàng đợi job đăng bài (LichDang/HAM_XU_LY.md::QueueService).
 * - enqueue/cancel gọi từ PostService khi lên lịch / sửa / xóa.
 * - claimNext + expireStaleJobs gọi từ worker (bin/worker.php).
 */
class QueueService extends AppServiceFactory
{
    /** Ngưỡng quá hạn (giây) coi job scheduled là expired nếu chưa chạy được. */
    private const STALE_THRESHOLD_SEC = 3600;

    public function enqueueJob(int $postId, string $runAt): int
    {
        $jobMapper = $this->getContainerEntry(JobMapper::class);
        return $jobMapper->enqueue($postId, $runAt);
    }

    public function cancelJob(int $postId): void
    {
        $jobMapper = $this->getContainerEntry(JobMapper::class);
        $jobMapper->cancelByPostId($postId);
    }

    public function cancelJobsForPosts(array $postIds): void
    {
        $jobMapper = $this->getContainerEntry(JobMapper::class);
        $jobMapper->cancelByPostIds($postIds);
    }

    public function getPendingCount(): int
    {
        $jobMapper = $this->getContainerEntry(JobMapper::class);
        return $jobMapper->countPending();
    }

    /** Claim job tới hạn kế tiếp cho worker (null nếu không có). */
    public function claimNext(): ?JobModel
    {
        $lockToken = bin2hex(random_bytes(16));
        $jobMapper = $this->getContainerEntry(JobMapper::class);
        return $jobMapper->claimDue($lockToken);
    }

    /**
     * Chạy một lượt ngắn cho HTTP cron: claim tối đa $limit job tới hạn rồi thoát.
     * Dùng thay cho worker loop trên shared host.
     */
    public function drainDueJobs(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $processed = 0;
        $errors = [];

        while ($processed < $limit) {
            $job = $this->claimNext();
            if ($job === null) {
                break;
            }

            try {
                $postExecutor = $this->getContainerEntry(PostExecutor::class);
                $postExecutor->executeJob($job);
            } catch (\Throwable $e) {
                $error = $this->safeError($e->getMessage() ?: get_class($e));
                $row = [
                    'jobId'  => $job->getId(),
                    'postId' => $job->getPostId(),
                    'error'  => $error,
                ];

                try {
                    $this->recordUnhandledFailure($job, $error);
                } catch (\Throwable $persistError) {
                    $row['persistError'] = $this->safeError($persistError->getMessage() ?: get_class($persistError));
                }

                $errors[] = $row;
            }
            $processed++;
        }

        return [
            'processed' => $processed,
            'expired'   => $this->expireStaleJobs(),
            'pending'   => $this->getPendingCount(),
            'errors'    => $errors,
        ];
    }

    private function recordUnhandledFailure(JobModel $job, string $error): void
    {
        $jobId = (int)$job->getId();
        $postId = (int)$job->getPostId();

        if ($jobId > 0) {
            $jobMapper = $this->getContainerEntry(JobMapper::class);
            $jobMapper->markFailed($jobId, $error);
        }

        if ($postId > 0) {
            $logMapper = $this->getContainerEntry(ExecutionLogMapper::class);
            $logMapper->log($postId, 'Lỗi cron: ' . $error, ExecutionLogMapper::STATUS_FAILED);

            $post = new PostModel();
            $post->setId($postId);
            $postMapper = $this->getContainerEntry(PostMapper::class);
            $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_FAILED]);
        }
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

    /**
     * Đánh dấu expired các bài scheduled quá hạn mà không có job pending còn sống
     * (job đã bị hủy/thất bại nhưng bài vẫn treo scheduled). Chạy mỗi phút từ worker.
     * Trả số bài bị đánh dấu expired.
     */
    public function expireStaleJobs(): int
    {
        $deadline  = date('Y-m-d H:i:s', time() - self::STALE_THRESHOLD_SEC);
        $postMapper = $this->getContainerEntry(PostMapper::class);
        $stalePosts = $postMapper->findStaleScheduled($deadline);

        $count = 0;
        foreach ($stalePosts as $post) {
            /** @var PostModel $post */
            $postMapper->updateAttrsPost($post, ['status' => PostConst::STATUS_EXPIRED]);
            $activityLogMapper = $this->getContainerEntry(ActivityLogMapper::class);
            $activityLogMapper->log(
                $post->getCreatedById(),
                'post:' . $post->getId(),
                'Quá hạn lịch',
                'Bài viết quá hạn lịch mà chưa đăng được — ' . ($post->getTitle() ?: ('#' . $post->getId())),
                ActivityLogConst::LEVEL_WARNING
            );
            $count++;
        }
        return $count;
    }
}
