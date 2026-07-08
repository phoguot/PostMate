<?php
declare(strict_types=1);

namespace Application\Model\ActivityLog;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Laminas\Db\Sql\Select;

/**
 * Mapper bảng activity_logs — dùng chung bởi mọi module để ghi/đọc nhật ký hoạt động.
 */
class ActivityLogMapper extends AppMapper
{
    public const TABLE_NAME = 'activity_logs';

    /**
     * Ghi 1 dòng nhật ký hoạt động.
     * $meta (optional): actorRole, objectName, objectType, ipAddress, device — cho màn Nhật ký hệ thống.
     */
    public function log(?int $userId, ?string $entityRef, string $type, string $message, int $level = ActivityLogConst::LEVEL_INFO, array $meta = []): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $insert = $dbSql->insert(ActivityLogMapper::TABLE_NAME);
        $insert->values([
            'userId'     => $userId,
            'entityRef'  => $entityRef,
            'type'       => $type,
            'message'    => $message,
            'level'      => $level,
            'actorRole'  => $meta['actorRole'] ?? null,
            'objectName' => $meta['objectName'] ?? null,
            'objectType' => $meta['objectType'] ?? null,
            'ipAddress'  => $meta['ipAddress'] ?? null,
            'device'     => $meta['device'] ?? null,
            'createdAt'  => DateModel::getTimeStampsCurrent(),
        ]);
        $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    /** Nhật ký theo entityRef (vd "facebookAccount:12"), mới nhất trước. */
    public function listByEntity(string $entityRef, int $limit = 50): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['al' => ActivityLogMapper::TABLE_NAME]);
        $select->where(['al.entityRef' => $entityRef]);
        $select->order(['al.id DESC']);
        $select->limit($limit);

        return $this->fetchModels($select);
    }

    /** Nhật ký toàn hệ thống theo user, mới nhất trước. */
    public function listByUser(int $userId, int $limit = 50): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['al' => ActivityLogMapper::TABLE_NAME]);
        $select->where(['al.userId' => $userId]);
        $select->order(['al.id DESC']);
        $select->limit($limit);

        return $this->fetchModels($select);
    }

    /**
     * Nhật ký hệ thống có lọc + phân trang (màn Nhật ký hệ thống — diarysetting.png).
     * $item mang userId (scope) + options: keyword/type/level/dateFrom/dateTo (epoch giây).
     * Join nhẹ 'users' để lấy tên + ảnh người thao tác.
     */
    public function search(ActivityLogModel $item, int $page = 1, int $pageSize = 10): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $this->buildSearchSelect($item);
        $select->order(['al.id DESC']);
        $select->limit($pageSize);
        $select->offset(max(0, ($page - 1) * $pageSize));

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new ActivityLogModel();
            $model->exchangeArray($row);
            $items[] = $model;
        }

        $countSelect = $this->buildSearchSelect($item);
        $total = $this->countBySearchSelect($countSelect);

        return ['items' => $items, 'total' => $total];
    }

    private function buildSearchSelect(ActivityLogModel $item): Select
    {
        $dbSql  = $this->getDbSql();
        $select = $dbSql->select(['al' => ActivityLogMapper::TABLE_NAME]);
        $select->join(
            ['u' => 'users'],
            'u.id = al.userId',
            ['actorName' => 'fullName', 'actorAvatar' => 'avatarUrl'],
            Select::JOIN_LEFT
        );

        if ($item->getUserId()) {
            $select->where(['al.userId' => $item->getUserId()]);
        }
        if ($item->getOption('type')) {
            $select->where(['al.type' => $item->getOption('type')]);
        }
        if ($item->getOption('objectType')) {
            $select->where(['al.objectType' => $item->getOption('objectType')]);
        }
        if ($item->getOption('level')) {
            $select->where(['al.level' => (int)$item->getOption('level')]);
        }
        if ($item->getOption('dateFrom')) {
            $select->where->greaterThanOrEqualTo('al.createdAt', (int)$item->getOption('dateFrom'));
        }
        if ($item->getOption('dateTo')) {
            $select->where->lessThanOrEqualTo('al.createdAt', (int)$item->getOption('dateTo'));
        }
        if ($item->getOption('keyword')) {
            $kw = '%' . $item->getOption('keyword') . '%';
            $select->where->nest()
                ->like('al.message', $kw)
                ->or->like('al.type', $kw)
                ->or->like('al.objectName', $kw)
                ->unnest();
        }
        return $select;
    }

    private function countBySearchSelect(Select $select): int
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select->reset('order');
        $rawSql = $dbSql->buildSqlString($select);
        $sql    = "SELECT COUNT(*) AS total FROM ($rawSql) AS sub";
        $rows   = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);
        $row    = $rows->current();
        return (int)(((array)$row)['total'] ?? 0);
    }

    private function fetchModels($select): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);

        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new ActivityLogModel();
            $model->exchangeArray($row);
            $items[] = $model;
        }
        return $items;
    }
}
