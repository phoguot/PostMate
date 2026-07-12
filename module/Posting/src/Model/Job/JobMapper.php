<?php
declare(strict_types=1);

namespace Posting\Model\Job;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Laminas\Db\Sql\Expression;

/**
 * Mapper bảng post_jobs.
 * - claim nguyên tử qua UPDATE ... ORDER BY runAt LIMIT 1 để nhiều worker không giành cùng 1 job.
 * - Đây là worker layer (không scope theo user — chạy dưới tiến trình hệ thống).
 */
class JobMapper extends AppMapper
{
    public const TABLE_NAME = 'post_jobs';

    /** Đưa job vào hàng đợi (hủy job pending cũ của cùng post trước để tránh trùng). */
    public function enqueue(int $postId, string $runAt): int
    {
        $this->cancelByPostId($postId);

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $insert = $dbSql->insert(JobMapper::TABLE_NAME);
        $insert->values([
            'postId'    => $postId,
            'status'    => JobConst::STATUS_PENDING,
            'runAt'     => $runAt,
            'createdAt' => DateModel::getTimeStampsCurrent(),
        ]);
        $result = $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
        return (int)$result->getGeneratedValue();
    }

    /** Hủy các job đang pending của 1 post (khi sửa lịch / xóa bài). */
    public function cancelByPostId(int $postId): void
    {
        $this->cancelByPostIds([$postId]);
    }

    /** Hủy hàng loạt job pending theo danh sách postId (checkpoint / gỡ liên kết). */
    public function cancelByPostIds(array $postIds): void
    {
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));
        if (empty($postIds)) {
            return;
        }
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $update = $dbSql->update(JobMapper::TABLE_NAME);
        $update->set(['status' => JobConst::STATUS_CANCELED, 'modifiedAt' => DateModel::getTimeStampsCurrent()]);
        $update->where(['status' => JobConst::STATUS_PENDING]);
        $update->where->in('postId', $postIds);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Claim 1 job tới hạn theo cơ chế nguyên tử:
     * 1. UPDATE gán lockToken cho đúng 1 dòng pending & runAt <= now (ORDER BY runAt LIMIT 1)
     * 2. SELECT lại dòng vừa gán để trả về.
     * Trả null nếu không có job tới hạn.
     */
    public function claimDue(string $lockToken, ?string $now = null): ?JobModel
    {
        $now       = $now ?? DateModel::getCurrentDateTime();
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $update = $dbSql->update(JobMapper::TABLE_NAME);
        $update->set([
            'status'    => JobConst::STATUS_PROCESSING,
            'lockToken' => $lockToken,
            'lockedAt'  => $now,
        ]);
        $update->where(['status' => JobConst::STATUS_PENDING]);
        $update->where(['runAt <= ?' => $now]);
        // Laminas Update không hỗ trợ order/limit trực tiếp → build thủ công.
        $sql = $dbSql->buildSqlString($update) . ' ORDER BY `runAt` ASC LIMIT 1';
        $result = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);
        if ($result->getAffectedRows() < 1) {
            return null;
        }

        $select = $dbSql->select(['j' => JobMapper::TABLE_NAME]);
        $select->where(['j.lockToken' => $lockToken, 'j.status' => JobConst::STATUS_PROCESSING]);
        $select->limit(1);
        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (! $rows->count()) {
            return null;
        }

        $model = new JobModel();
        $model->exchangeArray((array)$rows->current());
        return $model;
    }

    public function markDone(int $jobId): void
    {
        $this->updateStatus($jobId, JobConst::STATUS_DONE);
    }

    public function markFailed(int $jobId, ?string $error = null): void
    {
        $this->updateStatus($jobId, JobConst::STATUS_FAILED, $error);
    }

    /** Đưa job về pending với runAt mới (retry/backoff). */
    public function reschedule(int $jobId, string $runAt, ?string $error = null): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $update = $dbSql->update(JobMapper::TABLE_NAME);
        $update->set([
            'status'     => JobConst::STATUS_PENDING,
            'runAt'      => $runAt,
            'lockToken'  => null,
            'lockedAt'   => null,
            'lastError'  => $error,
            'modifiedAt' => DateModel::getTimeStampsCurrent(),
        ]);
        $update->where(['id' => $jobId]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    private function updateStatus(int $jobId, int $status, ?string $error = null): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $data = ['status' => $status, 'modifiedAt' => DateModel::getTimeStampsCurrent()];
        if ($error !== null) {
            $data['lastError'] = $error;
        }
        $update = $dbSql->update(JobMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id' => $jobId]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    public function countPending(): int
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['j' => JobMapper::TABLE_NAME]);
        $select->columns(['total' => new Expression('COUNT(j.id)')]);
        $select->where(['j.status' => JobConst::STATUS_PENDING]);
        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $row  = $rows->current();
        return (int)(((array)$row)['total'] ?? 0);
    }
}
