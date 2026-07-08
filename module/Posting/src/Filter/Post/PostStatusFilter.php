<?php
declare(strict_types=1);

namespace Posting\Filter\Post;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

class PostStatusFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['id', 'status'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName, true));
        }
    }
}
