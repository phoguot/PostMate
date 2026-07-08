<?php

declare(strict_types=1);

namespace Facebook\Model\Cookie;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Mapper bảng cookies.
 * - Dữ liệu thuộc về user đăng nhập: scope gián tiếp qua facebook_accounts.ownerUserId.
 */
class CookieMapper extends AppMapper
{
    public const TABLE_NAME = 'cookies';

    // -------------------------------------------------------------------------
    // Read

    private function buildSelect(CookieModel $item): Select
    {
        $select = $this->getDbSql()->select(['ck' => CookieMapper::TABLE_NAME]);
        $select->join(
            ['fa' => FacebookAccountMapper::TABLE_NAME],
            'fa.id = ck.facebookAccountId',
            ['facebookAccountName' => 'displayName', 'ownerUserId'],
            Select::JOIN_INNER
        );
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        if ($item->getId()) {
            $select->where(['ck.id' => $item->getId()]);
        }
        if ($item->getFacebookAccountId()) {
            $select->where(['ck.facebookAccountId' => $item->getFacebookAccountId()]);
        }
        if ($item->getStatus() !== null) {
            $select->where(['ck.status' => $item->getStatus()]);
        }
        $select->order(['ck.id DESC']);
        return $select;
    }

    public function searchCookie(CookieModel $item, int $page = 1, int $pageSize = 30): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $this->buildSelect($item);
        $select->limit($pageSize);
        $select->offset(max(0, ($page - 1) * $pageSize));

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);

        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new CookieModel();
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
        $profileIds = [];
        $accountIds = [];
        foreach ($items as $ck) {
            /** @var CookieModel $ck */
            if ($ck->getBrowserProfileId()) {
                $profileIds[] = $ck->getBrowserProfileId();
            }
            $accountIds[] = $ck->getFacebookAccountId();
        }
        $profileInfoMap = $profileIds ? $this->getContainerEntry(BrowserProfileMapper::class)->getInfoMapByIds($profileIds) : [];
        $fanpageNamesMap = $this->getFanpageNamesByAccountIds($accountIds);

        foreach ($items as $ck) {
            /** @var CookieModel $ck */
            $info = $profileInfoMap[$ck->getBrowserProfileId()] ?? null;
            if ($info) {
                $ck->setBrowserProfileName($info['name'] ?? null);
            }
            $ck->setFanpageNames($fanpageNamesMap[$ck->getFacebookAccountId()] ?? []);
        }
        return $items;
    }

    /** [facebookAccountId => [fanpageName, ...]] — dùng \Facebook\Model\Fanpage\FanpageMapper cùng module. */
    private function getFanpageNamesByAccountIds(array $accountIds): array
    {
        $accountIds = array_values(array_unique(array_filter($accountIds)));
        if (empty($accountIds)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['fp' => 'fanpages']);
        $select->columns(['facebookAccountId', 'name']);
        $select->where(['fp.facebookAccountId' => array_map('intval', $accountIds)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['facebookAccountId']][] = $row['name'];
        }
        return $map;
    }

    public function getCookie(CookieModel $item)
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

    /** [facebookAccountId => ['status' => int, 'expiresAt' => string]] — cookie mới nhất mỗi tài khoản. */
    public function getLatestByAccountIds(array $accountIds): array
    {
        $accountIds = array_values(array_unique(array_filter($accountIds)));
        if (empty($accountIds)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['ck' => CookieMapper::TABLE_NAME]);
        $select->columns(['id', 'facebookAccountId', 'status', 'expiresAt']);
        $select->where(['ck.facebookAccountId' => array_map('intval', $accountIds)]);
        $select->order(['ck.facebookAccountId ASC', 'ck.id DESC']);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $accId = (int)$row['facebookAccountId'];
            if (! isset($map[$accId])) {
                $map[$accId] = ['id' => (int)$row['id'], 'status' => (int)$row['status'], 'expiresAt' => $row['expiresAt']];
            }
        }
        return $map;
    }

    /** Id cookie mới nhất của 1 tài khoản, hoặc null nếu chưa có. */
    public function getLatestIdByAccountId(int $accountId): ?int
    {
        $latest = $this->getLatestByAccountIds([$accountId]);
        return $latest[$accountId]['id'] ?? null;
    }

    /** Đếm tài khoản có cookie sắp hết hạn (status=expiring hoặc expiresAt <= now + ngưỡng). */
    public function countExpiringByAccountIds(array $accountIds): int
    {
        $latest = $this->getLatestByAccountIds($accountIds);
        $threshold = strtotime('+' . CookieConst::EXPIRING_THRESHOLD_DAYS . ' days');
        $count = 0;
        foreach ($latest as $row) {
            if ($row['status'] === CookieConst::STATUS_EXPIRING) {
                $count++;
                continue;
            }
            if (! empty($row['expiresAt']) && strtotime($row['expiresAt']) <= $threshold) {
                $count++;
            }
        }
        return $count;
    }

    // -------------------------------------------------------------------------
    // Thống kê (KPI)

    public function getStats(CookieModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $dbSql->select(['ck' => CookieMapper::TABLE_NAME]);
        $select->join(
            ['fa' => FacebookAccountMapper::TABLE_NAME],
            'fa.id = ck.facebookAccountId',
            [],
            Select::JOIN_INNER
        );
        $select->columns(['status', 'total' => new Expression('COUNT(ck.id)')]);
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        $select->group('ck.status');

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $byStatus = [];
        foreach ($rows->toArray() as $row) {
            $byStatus[(int)$row['status']] = (int)$row['total'];
        }

        return [
            'total'    => array_sum($byStatus),
            'valid'    => (int)($byStatus[CookieConst::STATUS_VALID] ?? 0),
            'expiring' => (int)($byStatus[CookieConst::STATUS_EXPIRING] ?? 0),
            'invalid'  => (int)($byStatus[CookieConst::STATUS_INVALID] ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Write

    public function saveCookie(CookieModel $item): CookieModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $data = [
            'code'              => $item->getCode(),
            'facebookAccountId' => $item->getFacebookAccountId(),
            'browserProfileId'  => $item->getBrowserProfileId(),
            'sizeKb'            => $item->getSizeKb(),
            'status'            => $item->getStatus() ?? CookieConst::STATUS_VALID,
            'expiresAt'         => $item->getExpiresAt(),
            'lastLoginAt'       => $item->getLastLoginAt(),
            'lastLoginIp'       => $item->getLastLoginIp(),
            'device'            => $item->getDevice(),
            'userAgent'         => $item->getUserAgent(),
            'cookieBlob'        => $item->getCookieBlob(),
        ];

        if (! $item->getId()) {
            $data['createdAt'] = DateModel::getTimeStampsCurrent();
            $insert = $dbSql->insert(CookieMapper::TABLE_NAME);
            $insert->values($data);
            $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
            $item->setId($this->getLastInsertId(CookieMapper::TABLE_NAME));
        } else {
            $data['modifiedAt'] = DateModel::getTimeStampsCurrent();
            $update = $dbSql->update(CookieMapper::TABLE_NAME);
            $update->set($data);
            $update->where(['id' => $item->getId()]);
            $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        }

        return $item;
    }

    public function updateAttrs(CookieModel $item, array $data)
    {
        if (! $data || ! $item->getId()) {
            return null;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $update    = $dbSql->update(CookieMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => (int)$item->getId()]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    public function deleteCookie(CookieModel $item): bool
    {
        if (! $item->getId()) {
            return false;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $delete    = $dbSql->delete(CookieMapper::TABLE_NAME);
        $delete->where(['id' => $item->getId()]);
        $dbAdapter->query($dbSql->buildSqlString($delete), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    /** Tất cả cookie đang expiring của user (dùng cho refreshAllExpiring). */
    public function listExpiring(int $userId): array
    {
        $model = new CookieModel();
        $model->setUserId($userId);
        $model->setStatus(CookieConst::STATUS_EXPIRING);
        $result = $this->searchCookie($model, 1, 500);
        return $result['items'];
    }
}
