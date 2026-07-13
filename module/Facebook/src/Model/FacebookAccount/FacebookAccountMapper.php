<?php
declare(strict_types=1);

namespace Facebook\Model\FacebookAccount;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Fanpage\FanpageMapper;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Mapper bảng facebook_accounts.
 * - Dữ liệu thuộc về user đăng nhập: mọi truy vấn scope theo ownerUserId.
 * - Thông tin browser profile / cookie / fanpage lấy qua các mapper liên quan
 *   (join nhẹ bằng map theo id, không JOIN SQL trực tiếp xuyên module).
 */
class FacebookAccountMapper extends AppMapper
{
    const TABLE_NAME = 'facebook_accounts';

    // -------------------------------------------------------------------------
    // Read

    private function buildSelect(FacebookAccountModel $item): Select
    {
        $select = $this->getDbSql()->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        if ($item->getId()) {
            $select->where(['fa.id' => $item->getId()]);
        }
        if ($item->getStatus() !== null) {
            $select->where(['fa.status' => $item->getStatus()]);
        }
        if ($item->getOption('keyword')) {
            $select->where(['fa.displayName LIKE ?' => '%' . $item->getOption('keyword') . '%']);
        }
        $select->order(['fa.id DESC']);
        return $select;
    }

    public function searchFacebookAccount(FacebookAccountModel $item, int $page = 1, int $pageSize = 30): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $this->buildSelect($item);
        $select->limit($pageSize);
        $select->offset(max(0, ($page - 1) * $pageSize));

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);

        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new FacebookAccountModel();
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

    /** Gắn browserProfile/server/proxy + fanpageCount + cookie hiện hành. */
    private function attachRelated(array $items): array
    {
        if (!$items) {
            return $items;
        }

        $accountIds = [];
        $profileIds = [];
        foreach ($items as $a) {
            /** @var FacebookAccountModel $a */
            $accountIds[] = $a->getId();
            if ($a->getBrowserProfileId()) {
                $profileIds[] = $a->getBrowserProfileId();
            }
        }

        $profileInfoMap = [];
        if (!empty($profileIds)) {
            $browserProfileMapper = $this->getContainerEntry(BrowserProfileMapper::class);
            $profileInfoMap = $browserProfileMapper->getInfoMapByIds($profileIds);
        }
        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        $fanpageCountMap = $fanpageMapper->countByAccountIds($accountIds);
        $cookieMapper = $this->getContainerEntry(CookieMapper::class);
        $latestCookieMap = $cookieMapper->getLatestByAccountIds($accountIds);

        foreach ($items as $a) {
            /** @var FacebookAccountModel $a */
            $info = $profileInfoMap[$a->getBrowserProfileId()] ?? null;
            if ($info) {
                $a->setBrowserProfileName($info['name'] ?? null);
                $a->setServerName($info['serverName'] ?? null);
                $a->setProxyIp($info['proxyIp'] ?? null);
            }
            $a->setFanpageCount((int)($fanpageCountMap[$a->getId()] ?? 0));
            $cookie = $latestCookieMap[$a->getId()] ?? null;
            if ($cookie) {
                $a->setCookieStatus($cookie['status'] ?? null);
                $a->setCookieExpiresAt($cookie['expiresAt'] ?? null);
            }
        }

        return $items;
    }

    public function getFacebookAccount(FacebookAccountModel $item)
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        if ($item->getId()) {
            $select->where(['fa.id' => $item->getId()]);
        }
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (!$row->count()) {
            return false;
        }

        $item->exchangeArray((array)$row->current());
        $this->attachRelated([$item]);
        return $item;
    }

    /** Tìm tài khoản đã kết nối OAuth trước đó theo fbUserId thật (tránh tạo trùng khi kết nối lại). */
    public function getByFbUserId(int $ownerUserId, string $fbUserId): ?FacebookAccountModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        $select->where(['fa.ownerUserId' => $ownerUserId, 'fa.fbUserId' => $fbUserId]);
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (!$row->count()) {
            return null;
        }

        $model = new FacebookAccountModel();
        $model->exchangeArray((array)$row->current());
        return $model;
    }

    /** [id => ['displayName' => ..., 'email' => ...]] — dùng cho join nhẹ từ module khác. */
    public function getInfoMapByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        $select->columns(['id', 'displayName', 'email']);
        $select->where(['fa.id' => array_map('intval', $ids)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['id']] = ['displayName' => $row['displayName'], 'email' => $row['email']];
        }
        return $map;
    }

    // -------------------------------------------------------------------------
    // Thống kê (KPI)

    public function getStats(FacebookAccountModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $dbSql->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        $select->columns(['status', 'total' => new Expression('COUNT(fa.id)')]);
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        $select->group('fa.status');

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $byStatus = [];
        foreach ($rows->toArray() as $row) {
            $byStatus[(int)$row['status']] = (int)$row['total'];
        }

        $accountIds = array_keys($this->getInfoMapByIds($this->listAccountIds($item)));
        $expiringCookie = 0;
        if ($accountIds) {
            $cookieMapper = $this->getContainerEntry(CookieMapper::class);
            $expiringCookie = $cookieMapper->countExpiringByAccountIds($accountIds);
        }

        return [
            'total'          => array_sum($byStatus),
            'active'         => (int)($byStatus[FacebookAccountConst::STATUS_ACTIVE] ?? 0),
            'inactive'       => (int)($byStatus[FacebookAccountConst::STATUS_INACTIVE] ?? 0),
            'checkpoint'     => (int)($byStatus[FacebookAccountConst::STATUS_CHECKPOINT] ?? 0),
            'expired'        => $this->countExpired($item),
            'expiringCookie' => $expiringCookie,
        ];
    }

    /** Số tài khoản có hạn kết nối (expiresAt) đã qua thời điểm hiện tại. */
    private function countExpired(FacebookAccountModel $item): int
    {
        $dbSql  = $this->getDbSql();
        $select = $dbSql->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        $select->columns(['id']);
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        $select->where->isNotNull('fa.expiresAt');
        $select->where->lessThan('fa.expiresAt', DateModel::getCurrentDateTime());
        return $this->countBySelect($select);
    }

    private function listAccountIds(FacebookAccountModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['fa' => FacebookAccountMapper::TABLE_NAME]);
        $select->columns(['id']);
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        return array_map(fn($r) => (int)$r['id'], $rows->toArray());
    }

    // -------------------------------------------------------------------------
    // Write

    public function saveFacebookAccount(FacebookAccountModel $item): FacebookAccountModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $data = [
            'ownerUserId'      => $item->getOwnerUserId() ?? $item->getUserId(),
            'displayName'      => $item->getDisplayName(),
            'email'            => $item->getEmail(),
            'avatarUrl'        => $item->getAvatarUrl(),
            'fbUserId'         => $item->getFbUserId(),
            'userAccessToken'  => $item->getUserAccessToken(),
            'browserProfileId' => $item->getBrowserProfileId(),
            'status'           => $item->getStatus() ?? FacebookAccountConst::STATUS_ACTIVE,
            'accountRole'      => $item->getAccountRole(),
            'isPrimary'        => $item->getIsPrimary() ? 1 : 0,
            'expiresAt'        => $item->getExpiresAt(),
            'lastLoginAt'      => $item->getLastLoginAt(),
            'lastLoginIp'      => $item->getLastLoginIp(),
            'device'           => $item->getDevice(),
            'userAgent'        => $item->getUserAgent(),
            'capabilities'     => $item->getExtraContent(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = DateModel::getTimeStampsCurrent();
            $insert = $dbSql->insert(FacebookAccountMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
            $item->setId((int)$result->getGeneratedValue());
        } else {
            $data['modifiedAt'] = DateModel::getTimeStampsCurrent();
            $update = $dbSql->update(FacebookAccountMapper::TABLE_NAME);
            $update->set($data);
            $update->where(['id' => $item->getId()]);
            $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        }

        return $item;
    }

    public function updateAttrs(FacebookAccountModel $item, array $data)
    {
        if (!$data || !$item->getId()) {
            return null;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $update    = $dbSql->update(FacebookAccountMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => (int)$item->getId()]);
        if ($item->getUserId()) {
            $update->where(['ownerUserId = ?' => (int)$item->getUserId()]);
        }
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    /**
     * Hủy các job posts còn `scheduled` của mọi fanpage thuộc tài khoản này (khi checkpoint).
     * Dùng số nguyên trạng thái trực tiếp (2=scheduled, 6=expired — Posting\Model\Post\PostConst)
     * để tránh phụ thuộc ngược Facebook → Posting.
     */
    public function cancelJobsForAccount(int $accountId): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $fanpageIdsSelect = $dbSql->select(['fp' => 'fanpages']);
        $fanpageIdsSelect->columns(['id']);
        $fanpageIdsSelect->where(['fp.facebookAccountId' => $accountId]);

        $update = $dbSql->update('posts');
        $update->set(['status' => 6]); // PostConst::STATUS_EXPIRED
        $update->where(['status' => 2]); // PostConst::STATUS_SCHEDULED
        $update->where->in('fanpageId', $fanpageIdsSelect);

        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    public function deleteAccount(FacebookAccountModel $item): bool
    {
        if (!$item->getId()) {
            return false;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $delete    = $dbSql->delete(FacebookAccountMapper::TABLE_NAME);
        $delete->where(['id' => $item->getId()]);
        if ($item->getUserId()) {
            $delete->where(['ownerUserId' => $item->getUserId()]);
        }
        $dbAdapter->query($dbSql->buildSqlString($delete), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }
}
