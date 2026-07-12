<?php
declare(strict_types=1);

namespace Posting\Model\Job;

use Application\Model\Constant\AppConstModel;

/**
 * Const bảng post_jobs (hàng đợi job đăng bài).
 */
class JobConst extends AppConstModel
{
    // --- status (post_jobs.status) ---
    public const STATUS_PENDING = 1;    // chờ tới giờ chạy
    public const STATUS_PROCESSING = 2; // worker đã claim, đang chạy
    public const STATUS_DONE = 3;       // đã xử lý xong
    public const STATUS_CANCELED = 4;   // bị hủy (sửa lịch / xóa bài / checkpoint)
    public const STATUS_FAILED = 5;     // thất bại vĩnh viễn

    public static function getAllowedStatuses(): array
    {
        return [
            JobConst::STATUS_PENDING,
            JobConst::STATUS_PROCESSING,
            JobConst::STATUS_DONE,
            JobConst::STATUS_CANCELED,
            JobConst::STATUS_FAILED,
        ];
    }
}
