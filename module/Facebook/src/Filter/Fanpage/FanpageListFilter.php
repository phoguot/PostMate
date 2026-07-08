<?php
declare(strict_types=1);

namespace Facebook\Filter\Fanpage;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho API danh sách fanpage.
 */
class FanpageListFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['id', 'status', 'facebookAccountId'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }
        $this->add(CommonFieldFilters::dynamicField('keyword', ['type' => CommonFieldFilters::TYPE_TEXT]));

        $this->initInputPaging(1, 30);
    }
}
