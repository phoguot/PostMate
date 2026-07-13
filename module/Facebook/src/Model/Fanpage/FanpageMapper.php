<?php
declare(strict_types=1);

namespace Facebook\Model\Fanpage;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Mapper bảng fanpages.
 * - Dữ liệu thuộc về user đăng nhập: scope gián tiếp qua facebook_accounts.ownerUserId
 *   (JOIN facebook_accounts để lọc, cùng module nên JOIN trực tiếp bằng tên bảng).
 */
class FanpageMapper extends AppMapper
{
    const TABLE_NAME = 'fanpages';

    // -------------------------------------------------------------------------
    // Read

    private function buildSelect(FanpageModel $item): Select
    {
        $select = $this->getDbSql()->select(['fp' => FanpageMapper::TABLE_NAME]);
        $select->join(
            ['fa' => FacebookAccountMapper::TABLE_NAME],
            'fa.id = fp.facebookAccountId',
            ['facebookAccountName' => 'displayName', 'ownerUserId'],
            Select::JOIN_INNER
        );
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        if ($item->getId()) {
            $select->where(['fp.id' => $item->getId()]);
        }
        if ($item->getFacebookAccountId()) {
            $select->where(['fp.facebookAccountId' => $item->getFacebookAccountId()]);
        }
        if ($item->getStatus() !== null) {
            $select->where(['fp.status' => $item->getStatus()]);
        }
        if ($item->getOption('keyword')) {
            $select->where(['fp.name LIKE ?' => '%' . $item->getOption('keyword') . '%']);
        }
        $select->order(['fp.id DESC']);
        return $select;
    }

    public function searchFanpage(FanpageModel $item, int $page = 1, int $pageSize = 30): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $this->buildSelect($item);
        $select->limit($pageSize);
        $select->offset(max(0, ($page - 1) * $pageSize));

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);

        $items = [];
        foreach ($rows->toArray() as $row) {
            $model = new FanpageModel();
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
        if (!$items) {
            return $items;
        }
        $profileIds = [];
        foreach ($items as $fp) {
            /** @var FanpageModel $fp */
            if ($fp->getBrowserProfileId()) {
                $profileIds[] = $fp->getBrowserProfileId();
            }
        }
        $profileInfoMap = [];
        if ($profileIds) {
            $browserProfileMapper = $this->getContainerEntry(BrowserProfileMapper::class);
            $profileInfoMap = $browserProfileMapper->getInfoMapByIds($profileIds);
        }

        foreach ($items as $fp) {
            /** @var FanpageModel $fp */
            $info = $profileInfoMap[$fp->getBrowserProfileId()] ?? null;
            if ($info) {
                $fp->setBrowserProfileName($info['name'] ?? null);
            }
        }
        return $items;
    }

    public function getFanpage(FanpageModel $item)
    {
        $select = $this->buildSelect($item);
        $select->limit(1);

        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (!$row->count()) {
            return false;
        }

        $item->exchangeArray((array)$row->current());
        $this->attachRelated([$item]);
        return $item;
    }

    /** Tìm fanpage đã đồng bộ trước đó theo fbPageId thật (upsert khi kết nối lại qua OAuth). */
    public function getByFbPageId(string $fbPageId): ?FanpageModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['fp' => FanpageMapper::TABLE_NAME]);
        $select->where(['fp.fbPageId' => $fbPageId]);
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (!$row->count()) {
            return null;
        }

        $model = new FanpageModel();
        $model->exchangeArray((array)$row->current());
        return $model;
    }

    /** [id => name] — dùng cho join nhẹ từ module khác (vd Posting). */
    public function getNameMapByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['fp' => FanpageMapper::TABLE_NAME]);
        $select->columns(['id', 'name']);
        $select->where(['fp.id' => array_map('intval', $ids)]);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['id']] = $row['name'];
        }
        return $map;
    }

    /** [facebookAccountId => count] số fanpage liên kết theo từng tài khoản. */
    public function countByAccountIds(array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['fp' => FanpageMapper::TABLE_NAME]);
        $select->columns(['facebookAccountId', 'total' => new Expression('COUNT(fp.id)')]);
        $select->where(['fp.facebookAccountId' => array_map('intval', $accountIds)]);
        $select->group('fp.facebookAccountId');

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $map = [];
        foreach ($rows->toArray() as $row) {
            $map[(int)$row['facebookAccountId']] = (int)$row['total'];
        }
        return $map;
    }

    // -------------------------------------------------------------------------
    // Thống kê (KPI)

    public function getStats(FanpageModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $dbSql->select(['fp' => FanpageMapper::TABLE_NAME]);
        $select->join(
            ['fa' => FacebookAccountMapper::TABLE_NAME],
            'fa.id = fp.facebookAccountId',
            [],
            Select::JOIN_INNER
        );
        $select->columns(['status', 'total' => new Expression('COUNT(fp.id)')]);
        if ($item->getUserId()) {
            $select->where(['fa.ownerUserId' => $item->getUserId()]);
        }
        $select->group('fp.status');

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $byStatus = [];
        foreach ($rows->toArray() as $row) {
            $byStatus[(int)$row['status']] = (int)$row['total'];
        }

        return [
            'total'       => array_sum($byStatus),
            'active'      => (int)($byStatus[FanpageConst::STATUS_ACTIVE] ?? 0),
            'needRelogin' => (int)($byStatus[FanpageConst::STATUS_NEED_RELOGIN] ?? 0),
            'inactive'    => (int)($byStatus[FanpageConst::STATUS_INACTIVE] ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Write

    public function saveFanpage(FanpageModel $item): FanpageModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $data = [
            'fbPageId'          => $item->getFbPageId(),
            'name'              => $item->getName(),
            'category'          => $item->getCategory(),
            'url'               => $item->getUrl(),
            'facebookAccountId' => $item->getFacebookAccountId(),
            'browserProfileId'  => $item->getBrowserProfileId(),
            'likesCount'        => $item->getLikesCount() ?? 0,
            'followersCount'    => $item->getFollowersCount() ?? 0,
            'status'            => $item->getStatus() ?? FanpageConst::STATUS_ACTIVE,
            'canPost'           => (int)(bool)$item->getCanPost(),
            'capabilities'      => $item->getExtraContent(),
            'lastPostAt'        => $item->getLastPostAt(),
            'pageAccessToken'   => $item->getPageAccessToken(),
            'tokenExpiresAt'    => $item->getTokenExpiresAt(),
            'apiEnabled'        => (int)(bool)$item->getApiEnabled(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = DateModel::getTimeStampsCurrent();
            $insert = $dbSql->insert(FanpageMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);
            $item->setId((int)$result->getGeneratedValue());
        } else {
            $data['modifiedAt'] = DateModel::getTimeStampsCurrent();
            $update = $dbSql->update(FanpageMapper::TABLE_NAME);
            $update->set($data);
            $update->where(['id' => $item->getId()]);
            $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        }

        return $item;
    }

    public function updateAttrs(FanpageModel $item, array $data)
    {
        if (!$data || !$item->getId()) {
            return null;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $update    = $dbSql->update(FanpageMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => (int)$item->getId()]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    /** Cập nhật canPost + canPostReason hàng loạt theo id (dùng bởi recomputeCanPost). */
    public function updateCanPost(int $id, bool $canPost): void
    {
        $this->updateAttrs((new FanpageModel())->setId($id), ['canPost' => (int)$canPost]);
    }

    /**
     * Hủy job posts còn `scheduled` của fanpage này (khi gỡ liên kết / cần đăng nhập lại).
     * Dùng số nguyên trạng thái trực tiếp (2=scheduled, 6=expired — Posting\Model\Post\PostConst)
     * để tránh phụ thuộc ngược Facebook → Posting.
     */
    public function cancelJobsForFanpage(int $fanpageId): void
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $update = $dbSql->update('posts');
        $update->set(['status' => 6]); // PostConst::STATUS_EXPIRED
        $update->where(['status' => 2, 'fanpageId' => $fanpageId]); // PostConst::STATUS_SCHEDULED

        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    /** Gỡ liên kết fanpage — thu hồi token + xóa khỏi hệ thống quản lý. */
    public function unlinkFanpage(FanpageModel $item): bool
    {
        if (!$item->getId()) {
            return false;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $delete    = $dbSql->delete(FanpageMapper::TABLE_NAME);
        $delete->where(['id' => $item->getId()]);
        $dbAdapter->query($dbSql->buildSqlString($delete), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }
}
