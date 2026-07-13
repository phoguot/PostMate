<?php
declare(strict_types=1);

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Posting\Model\Job\JobMapper;
use Posting\Model\Job\JobModel;
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
        return $this->getContainerEntry(JobMapper::class)->enqueue($postId, $runAt);
    }

    public function cancelJob(int $postId): void
    {
        $this->getContainerEntry(JobMapper::class)->cancelByPostId($postId);
    }

    public function cancelJobsForPosts(array $postIds): void
    {
        $this->getContainerEntry(JobMapper::class)->cancelByPostIds($postIds);
    }

    public function getPendingCount(): int
    {
        return $this->getContainerEntry(JobMapper::class)->countPending();
    }

    /** Claim job tới hạn kế tiếp cho worker (null nếu không có). */
    public function claimNext(): ?JobModel
    {
        $lockToken = bin2hex(random_bytes(16));
        return $this->getContainerEntry(JobMapper::class)->claimDue($lockToken);
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
                $this->getContainerEntry(PostExecutor::class)->executeJob($job);
            } catch (\Throwable $e) {
                $errors[] = [
                    'jobId' => $job->getId(),
                    'error' => $e->getMessage(),
                ];
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
            $this->getContainerEntry(ActivityLogMapper::class)->log(
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
