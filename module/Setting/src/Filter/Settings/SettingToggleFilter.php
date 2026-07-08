<?php
declare(strict_types=1);

namespace Setting\Filter\Settings;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter bật/tắt 1 toggle hệ thống (autoShortenLink/autoSaveDraft/showAiSuggestions/confirmBeforePost).
 */
class SettingToggleFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->add(CommonFieldFilters::dynamicField('key', ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => true]));
        $this->add(['name' => 'value', 'required' => true, 'allow_empty' => true]);
    }
}
