<?php
declare(strict_types=1);

namespace Infra\Filter\BrowserProfile;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter dùng chung cho các API chỉ cần id: detail / start / stop / restart / open / delete.
 */
class BrowserProfileIdFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::intField('id', true));
    }
}
