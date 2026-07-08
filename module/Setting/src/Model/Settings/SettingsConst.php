<?php
declare(strict_types=1);

namespace Setting\Model\Settings;

use Application\Model\Constant\AppConstModel;

/**
 * Const bảng settings — cấu hình theo user (màn Cài đặt).
 * - channel dùng chung giá trị với Posting\Model\Post\PostConst::CHANNEL_*
 *   và Facebook\Model\Fanpage\FanpageConst::CHANNEL_* (1=graph_api, 2=browser)
 *   nhưng khai báo độc lập để tránh Setting phụ thuộc ngược Posting/Facebook.
 */
class SettingsConst extends AppConstModel
{
    const CHANNEL_GRAPH_API = 1;
    const CHANNEL_BROWSER = 2;

    const DEFAULT_LANGUAGE = 'vi';
    const DEFAULT_TIMEZONE = 'Asia/Ho_Chi_Minh';
    const DEFAULT_DATE_FORMAT = 'DD/MM/YYYY';
    const DEFAULT_THEME_MODE = 'light';
    const DEFAULT_DISPLAY_DENSITY = 'standard';
    const DEFAULT_POST_TIME = '09:00:00';
    const DEFAULT_APP_VERSION = 'v1.2.0';

    // Giá trị hợp lệ cho các select màn Cài đặt chung.
    const THEME_MODES = ['light', 'dark', 'system'];
    const DISPLAY_DENSITIES = ['compact', 'standard', 'comfortable'];

    // Các key toggle hợp lệ cho SettingsService::toggleOption()
    const TOGGLE_AUTO_SHORTEN_LINK = 'autoShortenLink';
    const TOGGLE_AUTO_SAVE_DRAFT = 'autoSaveDraft';
    const TOGGLE_SHOW_AI_SUGGESTIONS = 'showAiSuggestions';
    const TOGGLE_CONFIRM_BEFORE_POST = 'confirmBeforePost';
    // Toggle "Tùy chọn hệ thống" chung (màn Cài đặt chung / allsetting.png)
    const TOGGLE_CONFIRM_BEFORE_DELETE = 'confirmBeforeDelete';
    const TOGGLE_AUTO_SAVE_CHANGES = 'autoSaveChanges';
    const TOGGLE_NOTIFICATION_SOUND = 'notificationSound';
    const TOGGLE_SHOW_QUICK_HINTS = 'showQuickHints';
    const TOGGLE_PERFORMANCE_TRACKING = 'performanceTracking';

    public static function getAllowedToggleKeys(): array
    {
        return [
            SettingsConst::TOGGLE_AUTO_SHORTEN_LINK,
            SettingsConst::TOGGLE_AUTO_SAVE_DRAFT,
            SettingsConst::TOGGLE_SHOW_AI_SUGGESTIONS,
            SettingsConst::TOGGLE_CONFIRM_BEFORE_POST,
            SettingsConst::TOGGLE_CONFIRM_BEFORE_DELETE,
            SettingsConst::TOGGLE_AUTO_SAVE_CHANGES,
            SettingsConst::TOGGLE_NOTIFICATION_SOUND,
            SettingsConst::TOGGLE_SHOW_QUICK_HINTS,
            SettingsConst::TOGGLE_PERFORMANCE_TRACKING,
        ];
    }
}
