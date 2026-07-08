<?php
declare(strict_types=1);

namespace User\Filter\Profile;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cập nhật hồ sơ (họ tên, ảnh đại diện) — mọi field optional (patch).
 */
class ProfileUpdateFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['fullName', 'avatarUrl'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, ['type' => CommonFieldFilters::TYPE_TEXT]));
        }
    }
}
