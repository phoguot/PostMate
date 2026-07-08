<?php
declare(strict_types=1);

namespace Facebook\Filter\FacebookAccount;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter dùng chung cho các API chỉ cần id: reLogin / delete / detail.
 */
class FacebookAccountIdFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::intField('id', true));
    }
}
