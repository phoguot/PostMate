<?php
declare(strict_types=1);

namespace Facebook\Filter\FacebookAccount;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho API danh sách tài khoản Facebook.
 */
class FacebookAccountListFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['id', 'status'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }
        $this->add(CommonFieldFilters::dynamicField('keyword', ['type' => CommonFieldFilters::TYPE_TEXT]));

        $this->initInputPaging(1, 30);
    }
}
