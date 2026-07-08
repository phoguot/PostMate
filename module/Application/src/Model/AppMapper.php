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
}
