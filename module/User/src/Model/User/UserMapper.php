<?php

declare(strict_types=1);

namespace User\Model\User;

use Application\Model\AppMapper;
use Application\Model\DateModel;

/**
 * Mapper bảng users. Dùng 1 kết nối DB (getDbAdapter/getDbSql).
 */
class UserMapper extends AppMapper
{
    const TABLE_NAME = 'users';

    /** Cập nhật một phần thuộc tính của user theo id. */
    public function updateAttrs(int $userId, array $data): void
    {
        if (! $userId || empty($data)) {
            return;
        }
        $data['updatedAt'] = DateModel::getTimeStampsCurrent();

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();
        $update    = $dbSql->update(UserMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $userId]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Lấy user theo id. Trả false nếu không tìm thấy.
     */
    public function getUser(UserModel $item)
    {
        if (! $item->getId()) {
            return false;
        }

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['u' => UserMapper::TABLE_NAME]);
        $select->where(['u.id' => (int)$item->getId()]);
        $select->limit(1);

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );
        if (! $rows->count()) {
            return false;
        }

        $item->exchangeArray((array)$rows->current());
        return $item;
    }

    /**
     * Lấy user theo username hoặc email (dùng cho đăng nhập).
     */
    public function getUserByUsername(UserModel $item): ?UserModel
    {
        $username = $item->getUsername();
        if (! $username) {
            return null;
        }

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['u' => UserMapper::TABLE_NAME]);
        $select->where->nest()
            ->equalTo('u.username', $username)
            ->or
            ->equalTo('u.email', $username)
            ->unnest();
        $select->limit(1);

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );
        if (! $rows->count()) {
            return null;
        }

        $model = new UserModel();
        $model->exchangeArray((array)$rows->current());
        return $model;
    }
}
