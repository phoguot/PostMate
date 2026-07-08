<?php
declare(strict_types=1);

namespace Facebook\Filter\Cookie;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter dùng chung cho các API chỉ cần id: detail / refresh / export / delete.
 */
class CookieIdFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::intField('id', true));
    }
}
