<?php
declare(strict_types=1);

namespace Infra\Model\Proxy;

use Application\Model\AppMapper;

/**
 * Mapper bảng proxies — không có màn CRUD riêng, dùng cho join/hiển thị IP.
 */
class ProxyMapper extends AppMapper
{
    const TABLE_NAME = 'proxies';

    public function getById(int $id): ?ProxyModel
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['px' => ProxyMapper::TABLE_NAME]);
        $select->where(['px.id' => $id]);
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (!$row->count()) {
            return null;
        }
        $model = new ProxyModel();
        $model->exchangeArray((array)$row->current());
        return $model;
    }

    /** [id => ip] — dùng cho join nhẹ từ module khác. */
    public function getIpMapByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['px' => ProxyMapper::TABLE_NAME]);
        $select->columns(['id', 'ip']);
        $select->where(['px.id' => array_map('intval', $ids)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['id']] = $row['ip'];
        }
        return $map;
    }
}
