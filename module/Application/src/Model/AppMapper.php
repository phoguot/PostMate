<?php
declare(strict_types=1);

namespace Application\Model;

use Application\Factory\AppServiceFactory;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class AppMapper extends AppServiceFactory
{
    private ?Sql $dbSql = null;

    protected function getDbAdapter(): AdapterInterface
    {
        return $this->getContainerEntry(AdapterInterface::class);
    }

    protected function getDbSql(): Sql
    {
        if ($this->dbSql === null) {
            $this->dbSql = new Sql($this->getDbAdapter());
        }
        return $this->dbSql;
    }

    protected function table(string $tableName): TableGateway
    {
        return new TableGateway($tableName, $this->getDbAdapter());
    }

    /**
     * Lấy id vừa insert. Trên PostgreSQL, PDO::lastInsertId() bắt buộc phải
     * truyền tên sequence (khác MySQL) — Laminas\Db trả về null nếu gọi
     * getGeneratedValue()/getLastGeneratedValue() không tham số trên driver pgsql.
     * Quy ước IDENTITY trong data/db/postmate.sql luôn đặt tên sequence là
     * "{table}_id_seq" (giống SERIAL mặc định của Postgres).
     */
    protected function getLastInsertId(string $tableName): int
    {
        return (int) $this->getDbAdapter()->getDriver()->getConnection()
            ->getLastGeneratedValue($tableName . '_id_seq');
    }
}
