<?php
declare(strict_types=1);

namespace Posting\Model\Log;

use Application\Model\AppMapper;
use Application\Model\DateModel;

/**
 * Mapper bảng post_execution_logs — timeline các bước đăng bài của worker.
 */
class ExecutionLogMapper extends AppMapper
{
    public const TABLE_NAME = 'post_execution_logs';

    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILED = 2;

    /** Ghi 1 bước thực thi (Mở trình duyệt / Nạp cookie / Điền nội dung / Đăng...). */
    public function log(int $postId, string $step, int $status = self::STATUS_SUCCESS, ?int $durationSec = null): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $insert = $dbSql->insert(ExecutionLogMapper::TABLE_NAME);
        $insert->values([
            'postId'      => $postId,
            'step'        => mb_substr($step, 0, 190),
            'status'      => $status,
            'durationSec' => $durationSec,
            'loggedAt'    => DateModel::getCurrentDateTime(),
        ]);
        $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    /** Timeline của 1 post, cũ → mới (dùng cho màn Lịch đăng / Bài viết). */
    public function getTimeline(int $postId): array
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['l' => ExecutionLogMapper::TABLE_NAME]);
        $select->where(['l.postId' => $postId]);
        $select->order(['l.id ASC']);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $result = [];
        foreach ($rows->toArray() as $row) {
            $result[] = [
                'step'        => $row['step'],
                'status'      => (int)$row['status'],
                'durationSec' => $row['durationSec'] !== null ? (int)$row['durationSec'] : null,
                'loggedAt'    => $row['loggedAt'],
            ];
        }
        return $result;
    }
}
