<?php
declare(strict_types=1);

namespace Facebook\Filter\Cookie;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho API danh sách cookie.
 */
class CookieListFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['id', 'status', 'facebookAccountId'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }

        $this->initInputPaging(1, 30);
    }
}
