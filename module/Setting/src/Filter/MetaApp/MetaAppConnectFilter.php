<?php
declare(strict_types=1);

namespace Setting\Filter\MetaApp;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter kết nối Meta App (appId + appSecret).
 */
class MetaAppConnectFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::dynamicField('appId', ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => true]));
        $this->add(CommonFieldFilters::dynamicField('appSecret', ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => true]));
    }
}
