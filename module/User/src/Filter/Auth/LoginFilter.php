<?php
declare(strict_types=1);

namespace User\Filter\Auth;

use Application\Filter\AppFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho đăng nhập — username (hoặc email) + password bắt buộc.
 */
class LoginFilter extends AppFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['username', 'password'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, [
                'type'     => CommonFieldFilters::TYPE_TEXT,
                'required' => true,
            ]));
        }
    }
}
