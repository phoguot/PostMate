<?php
declare(strict_types=1);

namespace Posting\Filter\Post;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho Nhân bản bài viết — chỉ cần id bài gốc.
 */
class PostDuplicateFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::intField('id', true));
    }
}
