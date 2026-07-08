<?php
declare(strict_types=1);

namespace Setting\Filter\Settings;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cập nhật cấu hình (patch — mọi field đều optional).
 */
class SettingUpdateFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        foreach (['defaultFanpageId', 'defaultContentType', 'defaultStatus', 'preferredChannel'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }

        foreach (['language', 'timezone', 'defaultPostTime', 'dateFormat', 'themeMode', 'displayDensity'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, ['type' => CommonFieldFilters::TYPE_TEXT]));
        }

        $this->add(['name' => 'allowBrowserFallback', 'required' => false, 'allow_empty' => true]);
    }
}
