<?php
declare(strict_types=1);

namespace Setting\Filter\Settings;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho API nhật ký hệ thống (màn Nhật ký hệ thống — diarysetting.png).
 * - keyword/type/objectType: text; level: int; dateFrom/dateTo: yyyy-mm-dd (service tự đổi epoch).
 */
class ActivityLogListFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::intField('level'));

        foreach (['keyword', 'type', 'objectType', 'dateFrom', 'dateTo'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, ['type' => CommonFieldFilters::TYPE_TEXT]));
        }

        $this->initInputPaging(1, 10);
    }
}
