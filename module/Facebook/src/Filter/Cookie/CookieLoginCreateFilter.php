<?php
declare(strict_types=1);

namespace Facebook\Filter\Cookie;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho API "Đăng nhập" (tạo cookie mới) — login qua profile (user/pass/2FA) hoặc import cookie.
 */
class CookieLoginCreateFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::intField('facebookAccountId', true));
        $this->add(CommonFieldFilters::dynamicField('method', ['type' => CommonFieldFilters::TYPE_TEXT]));
        $this->add(['name' => 'payload', 'required' => false, 'allow_empty' => true]);
    }
}
