<?php
declare(strict_types=1);

namespace Posting\Filter\Dashboard;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho các API Dashboard đọc thống kê theo khoảng thời gian.
 */
class DashboardStatsFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['fanpageId', 'browserProfileId', 'limit'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }

        $this->add(CommonFieldFilters::dynamicField('groupBy', [
            'type' => CommonFieldFilters::TYPE_TEXT,
        ]));

        $this->createCommonInputFilterDate('fromDate');
        $this->createCommonInputFilterDate('toDate');
    }
}
