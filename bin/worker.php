<?php
declare(strict_types=1);

/**
 * Worker đăng bài — kéo job tới hạn từ post_jobs và thực thi (PostExecutor).
 *
 * KHÔNG chạy được trên InfinityFree (shared host, không có tiến trình nền). Chạy trên
 * máy worker riêng (VPS/PC) — cùng nơi đặt agent Chrome (BrowserAgentClient::endpoint).
 *
 * Dùng:
 *   php bin/worker.php            # chạy 1 lượt (drain hết job tới hạn rồi thoát) — hợp cho cron mỗi phút
 *   php bin/worker.php --loop     # vòng lặp thường trú, poll mỗi vài giây
 *   php bin/worker.php --once     # xử lý đúng 1 job rồi thoát (debug)
 *
 * Cron ví dụ (máy worker):  * * * * * php /path/bin/worker.php >> /var/log/postmate-worker.log 2>&1
 */

chdir(__DIR__ . '/../');
require 'vendor/autoload.php';

use Posting\Service\PostExecutor;
use Posting\Service\QueueService;

$container = require 'config/container.php';

$loop = in_array('--loop', $argv, true);
$once = in_array('--once', $argv, true);
$pollSec = 5;

/** @var QueueService $queue */
$queue = $container->get(QueueService::class);
/** @var PostExecutor $executor */
$executor = $container->get(PostExecutor::class);

$stdout = static function (string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
};

$drainOnce = static function () use ($queue, $executor, $stdout, $once): int {
    $processed = 0;
    while (($job = $queue->claimNext()) !== null) {
        $stdout('Xử lý job #' . $job->getId() . ' (post ' . $job->getPostId() . ')');
        try {
            $executor->executeJob($job);
        } catch (\Throwable $e) {
            $stdout('Lỗi executeJob: ' . $e->getMessage());
        }
        $processed++;
        if ($once) {
            break;
        }
    }
    return $processed;
};

if ($loop) {
    $stdout('Worker khởi động (loop). Ctrl+C để dừng.');
    while (true) {
        $n = $drainOnce();
        $queue->expireStaleJobs();
        if ($n === 0) {
            sleep($pollSec);
        }
    }
}

$n = $drainOnce();
$expired = $queue->expireStaleJobs();
$stdout("Hoàn tất: xử lý {$n} job, đánh dấu {$expired} bài quá hạn. Còn chờ: " . $queue->getPendingCount());
exit(0);
