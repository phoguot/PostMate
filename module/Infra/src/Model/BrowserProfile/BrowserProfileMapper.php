<?php

declare(strict_types=1);

namespace Infra\Model\BrowserProfile;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Infra\Model\Proxy\ProxyMapper;
use Infra\Model\Server\ServerMapper;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Mapper bảng browser_profiles.
 * - Dữ liệu thuộc về user đăng nhập: scope theo createdById.
 * - Thông tin facebookAccountEmail lấy qua FacebookAccountMapper::getInfoMapByIds()
 *   (module Facebook) — join nhẹ bằng map theo id, không JOIN SQL trực tiếp xuyên module.
 */
class BrowserProfileMapper extends AppMapper
{
    const TABLE_NAME = 'browser_profiles';

    // -------------------------------------------------------------------------
    // Read

    private function buildSelect(BrowserProfileModel $item): Select
    {
        $select = $this->getDbSql()->select(['bp' => BrowserProfileMapper::TABLE_NAME]);
        if ($item->getUserId()) {
            $select->where(['bp.createdById' => $item->getUserId()]);
        }
        if ($item->getId()) {
            $select->where(['bp.id' => $item->getId()]);
        }
        if ($item->getStatus() !== null) {
            $select->where(['bp.status' => $item->getStatus()]);
        }
        if ($item->getServerId()) {
            $select->where(['bp.serverId' => $item->getServerId()]);
        }
        if ($item->getFacebookAccountId()) {
            $select->where(['bp.facebookAccountId' => $item->getFacebookAccountId()]);
        }
        if ($item->getOption('keyword')) {
            $select->where(['bp.profileName LIKE ?' => '%' . $item->getOption('keyword') . '%']);
        }
        $select->order(['bp.id DESC']);
        return $select;
    }

    public function searchBrowserProfile(BrowserProfileModel $item, int $page = 1, int $pageSize = 30): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $this->buildSelect($item);
        $select->limit($pageSize);
        $select->offset(max(0, ($page - 1) * $pageSize));

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);

        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new BrowserProfileModel();
            $model->exchangeArray($row);
            $items[] = $model;
        }
        $items = $this->attachRelated($items);

        $countSelect = $this->buildSelect($item);
        $countSelect->reset('order');
        $total = $this->countBySelect($countSelect);

        return ['items' => $items, 'total' => $total];
    }

    private function countBySelect(Select $select): int
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $rawSql    = $dbSql->buildSqlString($select);
        $sql       = "SELECT COUNT(*) AS total FROM ($rawSql) AS sub";
        $rows      = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);
        $row       = $rows->current();
        return (int)(((array)$row)['total'] ?? 0);
    }

    private function attachRelated(array $items): array
    {
        if (! $items) {
            return $items;
        }
        $serverIds  = [];
        $proxyIds   = [];
        $accountIds = [];
        foreach ($items as $bp) {
            /** @var BrowserProfileModel $bp */
            if ($bp->getServerId()) {
                $serverIds[] = $bp->getServerId();
            }
            if ($bp->getProxyId()) {
                $proxyIds[] = $bp->getProxyId();
            }
            if ($bp->getFacebookAccountId()) {
                $accountIds[] = $bp->getFacebookAccountId();
            }
        }

        $serverNameMap = $serverIds ? $this->getContainerEntry(ServerMapper::class)->getNameMapByIds($serverIds) : [];
        $serverIpMap   = $this->getServerIpMap($serverIds);
        $proxyIpMap    = $proxyIds ? $this->getContainerEntry(ProxyMapper::class)->getIpMapByIds($proxyIds) : [];
        $accountInfoMap = $accountIds ? $this->getFacebookAccountInfoMap($accountIds) : [];

        foreach ($items as $bp) {
            /** @var BrowserProfileModel $bp */
            if ($bp->getServerId()) {
                $bp->setServerName($serverNameMap[$bp->getServerId()] ?? null);
                $bp->setServerIp($serverIpMap[$bp->getServerId()] ?? null);
            }
            if ($bp->getProxyId()) {
                $bp->setProxyIp($proxyIpMap[$bp->getProxyId()] ?? null);
            }
            if ($bp->getFacebookAccountId()) {
                $bp->setFacebookAccountEmail($accountInfoMap[$bp->getFacebookAccountId()]['email'] ?? null);
            }
        }
        return $items;
    }

    private function getServerIpMap(array $serverIds): array
    {
        if (empty($serverIds)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['s' => 'servers']);
        $select->columns(['id', 'ipAddress']);
        $select->where(['s.id' => array_map('intval', $serverIds)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['id']] = $row['ipAddress'];
        }
        return $map;
    }

    /** [id => ['displayName'=>, 'email'=>]] — dùng \Facebook\Model\FacebookAccount\FacebookAccountMapper cùng cơ chế. */
    private function getFacebookAccountInfoMap(array $accountIds): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['fa' => 'facebook_accounts']);
        $select->columns(['id', 'displayName', 'email']);
        $select->where(['fa.id' => array_map('intval', $accountIds)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['id']] = ['displayName' => $row['displayName'], 'email' => $row['email']];
        }
        return $map;
    }

    public function getBrowserProfile(BrowserProfileModel $item)
    {
        $select = $this->buildSelect($item);
        $select->limit(1);

        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (! $row->count()) {
            return false;
        }

        $item->exchangeArray((array)$row->current());
        $this->attachRelated([$item]);
        return $item;
    }

    /** [id => ['name'=>, 'serverName'=>, 'proxyIp'=>, 'status'=>]] — dùng cho join nhẹ từ module khác. */
    public function getInfoMapByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['bp' => BrowserProfileMapper::TABLE_NAME]);
        $select->columns(['id', 'profileName', 'code', 'serverId', 'proxyId', 'status']);
        $select->where(['bp.id' => array_map('intval', $ids)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $rowsArr = $rows->toArray();

        $serverIds = array_filter(array_column($rowsArr, 'serverId'));
        $proxyIds  = array_filter(array_column($rowsArr, 'proxyId'));
        $serverNameMap = $serverIds ? $this->getContainerEntry(ServerMapper::class)->getNameMapByIds($serverIds) : [];
        $proxyIpMap    = $proxyIds ? $this->getContainerEntry(ProxyMapper::class)->getIpMapByIds($proxyIds) : [];

        $map = [];
        foreach ($rowsArr as $row) {
            $map[(int)$row['id']] = [
                'name'       => $row['profileName'] ?: $row['code'],
                'serverName' => $row['serverId'] ? ($serverNameMap[(int)$row['serverId']] ?? null) : null,
                'proxyIp'    => $row['proxyId'] ? ($proxyIpMap[(int)$row['proxyId']] ?? null) : null,
                'status'     => (int)$row['status'],
            ];
        }
        return $map;
    }

    // -------------------------------------------------------------------------
    // Thống kê (KPI)

    public function getStats(BrowserProfileModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $dbSql->select(['bp' => BrowserProfileMapper::TABLE_NAME]);
        $select->columns(['status', 'total' => new Expression('COUNT(bp.id)')]);
        if ($item->getUserId()) {
            $select->where(['bp.createdById' => $item->getUserId()]);
        }
        $select->group('bp.status');

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $byStatus = [];
        foreach ($rows->toArray() as $row) {
            $byStatus[(int)$row['status']] = (int)$row['total'];
        }

        $serverCountSelect = $dbSql->select(['bp' => BrowserProfileMapper::TABLE_NAME]);
        $serverCountSelect->columns(['total' => new Expression('COUNT(DISTINCT bp.serverId)')]);
        if ($item->getUserId()) {
            $serverCountSelect->where(['bp.createdById' => $item->getUserId()]);
        }
        $serverRows = $dbAdapter->query($dbSql->buildSqlString($serverCountSelect), $dbAdapter::QUERY_MODE_EXECUTE);
        $serverCount = (int)(((array)$serverRows->current())['total'] ?? 0);

        return [
            'total'       => array_sum($byStatus),
            'serverCount' => $serverCount,
            'running'     => (int)($byStatus[BrowserProfileConst::STATUS_RUNNING] ?? 0),
            'stopped'     => (int)($byStatus[BrowserProfileConst::STATUS_STOPPED] ?? 0),
            'offline'     => (int)($byStatus[BrowserProfileConst::STATUS_OFFLINE] ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Write

    public function saveBrowserProfile(BrowserProfileModel $item): BrowserProfileModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $data = [
            'code'              => $item->getCode(),
            'profileName'       => $item->getProfileName(),
            'profileId'         => $item->getProfileId(),
            'serverId'          => $item->getServerId(),
            'proxyId'           => $item->getProxyId(),
            'facebookAccountId' => $item->getFacebookAccountId(),
            'status'            => $item->getStatus() ?? BrowserProfileConst::STATUS_STOPPED,
            'mode'              => $item->getMode() ?? BrowserProfileConst::MODE_HEADLESS,
            'chromeVersion'     => $item->getChromeVersion(),
            'os'                => $item->getOs(),
            'userAgent'         => $item->getUserAgent(),
            'fingerprintJson'   => $item->getExtraContent(),
            'cpuPercent'        => $item->getCpuPercent(),
            'ramMb'             => $item->getRamMb(),
            'startedAt'         => $item->getStartedAt(),
            'lastActiveAt'      => $item->getLastActiveAt(),
            'uptimeMinutes'     => $item->getUptimeMinutes(),
        ];

        if (! $item->getId()) {
            $data['createdById'] = $item->getCreatedById() ?? $item->getUserId();
            $data['createdAt']   = DateModel::getTimeStampsCurrent();
            $insert = $dbSql->insert(BrowserProfileMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
            $item->setId((int)$result->getGeneratedValue());
        } else {
            $data['modifiedAt'] = DateModel::getTimeStampsCurrent();
            $update = $dbSql->update(BrowserProfileMapper::TABLE_NAME);
            $update->set($data);
            $update->where(['id' => $item->getId()]);
            $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        }

        return $item;
    }

    public function updateAttrs(BrowserProfileModel $item, array $data)
    {
        if (! $data || ! $item->getId()) {
            return null;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $update    = $dbSql->update(BrowserProfileMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => (int)$item->getId()]);
        if ($item->getUserId()) {
            $update->where(['createdById = ?' => (int)$item->getUserId()]);
        }
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    public function deleteBrowserProfile(BrowserProfileModel $item): bool
    {
        if (! $item->getId()) {
            return false;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $delete    = $dbSql->delete(BrowserProfileMapper::TABLE_NAME);
        $delete->where(['id' => $item->getId()]);
        if ($item->getUserId()) {
            $delete->where(['createdById' => $item->getUserId()]);
        }
        $dbAdapter->query($dbSql->buildSqlString($delete), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    /** Đánh dấu toàn bộ profile của 1 server thành offline (khi server mất kết nối). */
    public function markServerOffline(int $serverId): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();
        $update    = $dbSql->update(BrowserProfileMapper::TABLE_NAME);
        $update->set(['status' => BrowserProfileConst::STATUS_OFFLINE]);
        $update->where(['serverId' => $serverId]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }
}
