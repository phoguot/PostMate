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

    /** Lấy lỗi thất bại mới nhất theo từng post để FE hiển thị lý do đăng lỗi. */
    public function getLatestFailureMapByPostIds(array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds))));
        if (empty($postIds)) {
            return [];
        }

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['l' => ExecutionLogMapper::TABLE_NAME]);
        $select->columns(['postId', 'step', 'loggedAt']);
        $select->where(['l.status' => self::STATUS_FAILED]);
        $select->where(['l.postId' => $postIds]);
        $select->order(['l.id DESC']);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $result = [];
        foreach ($rows->toArray() as $row) {
            $postId = (int)($row['postId'] ?? 0);
            if ($postId <= 0 || isset($result[$postId])) {
                continue;
            }
            $result[$postId] = [
                'message'  => $this->normalizeFailureStep((string)($row['step'] ?? '')),
                'loggedAt' => $row['loggedAt'] ?? null,
            ];
            if (count($result) === count($postIds)) {
                break;
            }
        }
        return $result;
    }

    private function normalizeFailureStep(string $step): string
    {
        $step = trim($step);
        $prefix = 'Lỗi: ';
        if (str_starts_with($step, $prefix)) {
            return trim(substr($step, strlen($prefix)));
        }
        return $step;
    }
}
