<?php
declare(strict_types=1);

namespace User\Filter\Profile;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter đổi mật khẩu — mật khẩu hiện tại / mới / xác nhận đều bắt buộc.
 */
class ChangePasswordFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['currentPassword', 'newPassword', 'confirmPassword'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, [
                'type'     => CommonFieldFilters::TYPE_TEXT,
                'required' => true,
            ]));
        }
    }
}
