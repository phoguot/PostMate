<?php

declare(strict_types=1);

namespace Infra\Model\Server;

use Application\Model\AppMapper;
use Laminas\Db\Sql\Expression;

/**
 * Mapper bảng servers.
 * - Không có màn CRUD riêng (docs chỉ có màn Trình duyệt) — dùng cho join/hiển thị
 *   và kiểm tra tải (max_instances) khi BrowserProfileService::startProfile().
 */
class ServerMapper extends AppMapper
{
    public const TABLE_NAME = 'servers';

    public function getById(int $id): ?ServerModel
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['s' => ServerMapper::TABLE_NAME]);
        $select->where(['s.id' => $id]);
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (! $row->count()) {
            return null;
        }
        $model = new ServerModel();
        $model->exchangeArray((array)$row->current());
        return $model;
    }

    /** [id => name] — dùng cho join nhẹ từ module khác. */
    public function getNameMapByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['s' => ServerMapper::TABLE_NAME]);
        $select->columns(['id', 'name']);
        $select->where(['s.id' => array_map('intval', $ids)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['id']] = $row['name'];
        }
        return $map;
    }

    /** Toàn bộ server (kèm số instance đang chạy) — dùng cho KPI/màn Trình duyệt. */
    public function listAll(): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $dbSql->select(['s' => ServerMapper::TABLE_NAME]);
        $select->join(
            ['bp' => 'browser_profiles'],
            new Expression('bp.serverId = s.id AND bp.status = 1'), // 1 = BrowserProfileConst::STATUS_RUNNING
            ['runningInstances' => new Expression('COUNT(bp.id)')],
            \Laminas\Db\Sql\Select::JOIN_LEFT
        );
        $select->group('s.id');
        $select->order(['s.id ASC']);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new ServerModel();
            $model->exchangeArray($row);
            $items[] = $model;
        }
        return $items;
    }

    /** Số instance đang chạy trên 1 server (dùng kiểm tra max_instances trước khi start profile). */
    public function countRunningInstances(int $serverId): int
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['bp' => 'browser_profiles']);
        $select->columns(['total' => new Expression('COUNT(bp.id)')]);
        $select->where(['bp.serverId' => $serverId, 'bp.status' => 1]); // 1 = STATUS_RUNNING

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $row  = $rows->current();
        return (int)(((array)$row)['total'] ?? 0);
    }
}
