<?php
declare(strict_types=1);

namespace Setting\Model\MetaAppCredential;

use Application\Model\AppMapper;
use Application\Model\DateModel;

/**
 * Mapper bảng meta_app_credentials.
 * - 1 cấu hình Meta App / user (scope theo createdById); upsert theo user.
 */
class MetaAppCredentialMapper extends AppMapper
{
    const TABLE_NAME = 'meta_app_credentials';

    public function getByUserId(int $userId): ?MetaAppCredentialModel
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['mac' => MetaAppCredentialMapper::TABLE_NAME]);
        $select->where(['mac.createdById' => $userId]);
        $select->order(['mac.id DESC']);
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (! $row->count()) {
            return null;
        }

        $model = new MetaAppCredentialModel();
        $model->exchangeArray((array)$row->current());
        return $model;
    }

    /** Upsert theo user: cập nhật nếu đã có cấu hình, tạo mới nếu chưa. */
    public function upsert(int $userId, array $data): MetaAppCredentialModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $existing = $this->getByUserId($userId);
        if ($existing) {
            $data['modifiedAt'] = DateModel::getTimeStampsCurrent();
            $update = $dbSql->update(MetaAppCredentialMapper::TABLE_NAME);
            $update->set($data);
            $update->where(['id' => $existing->getId()]);
            $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
            return $this->getByUserId($userId);
        }

        $data['createdById'] = $userId;
        $data['createdAt']   = DateModel::getTimeStampsCurrent();
        $insert = $dbSql->insert(MetaAppCredentialMapper::TABLE_NAME);
        $insert->values($data);
        $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);

        return $this->getByUserId($userId);
    }
}
