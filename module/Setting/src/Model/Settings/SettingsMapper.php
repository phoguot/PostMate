<?php
declare(strict_types=1);

namespace Setting\Model\Settings;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Facebook\Model\Fanpage\FanpageMapper;

/**
 * Mapper bảng settings (PK = userId, 1 dòng / user).
 */
class SettingsMapper extends AppMapper
{
    const TABLE_NAME = 'settings';

    public function getByUserId(int $userId): ?SettingsModel
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['st' => SettingsMapper::TABLE_NAME]);
        $select->where(['st.userId' => $userId]);
        $select->limit(1);

        $row = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        if (! $row->count()) {
            return null;
        }

        $model = new SettingsModel();
        $model->exchangeArray((array)$row->current());
        $this->attachRelated($model);
        return $model;
    }

    private function attachRelated(SettingsModel $model): void
    {
        if (! $model->getDefaultFanpageId()) {
            return;
        }
        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        $nameMap = $fanpageMapper->getNameMapByIds([$model->getDefaultFanpageId()]);
        $model->setDefaultFanpageName($nameMap[$model->getDefaultFanpageId()] ?? null);
    }

    /** Lấy cấu hình của user; nếu chưa có → tạo mặc định rồi trả về. */
    public function ensureDefaults(int $userId): SettingsModel
    {
        $existing = $this->getByUserId($userId);
        if ($existing) {
            return $existing;
        }

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();
        $insert    = $dbSql->insert(SettingsMapper::TABLE_NAME);
        $insert->values([
            'userId'               => $userId,
            'language'             => SettingsConst::DEFAULT_LANGUAGE,
            'timezone'             => SettingsConst::DEFAULT_TIMEZONE,
            'dateFormat'           => SettingsConst::DEFAULT_DATE_FORMAT,
            'themeMode'            => SettingsConst::DEFAULT_THEME_MODE,
            'displayDensity'       => SettingsConst::DEFAULT_DISPLAY_DENSITY,
            'defaultContentType'   => 2, // 2 = PostConst::CONTENT_TYPE_IMAGE (Bài viết ảnh + văn bản)
            'defaultStatus'        => 2, // 2 = PostConst::STATUS_SCHEDULED (Lên lịch)
            'defaultPostTime'      => SettingsConst::DEFAULT_POST_TIME,
            'autoShortenLink'      => 0,
            'autoSaveDraft'        => 0,
            'showAiSuggestions'    => 1,
            'confirmBeforePost'    => 1,
            'confirmBeforeDelete'  => 1,
            'autoSaveChanges'      => 1,
            'notificationSound'    => 0,
            'showQuickHints'       => 1,
            'performanceTracking'  => 0,
            'preferredChannel'     => SettingsConst::CHANNEL_GRAPH_API,
            'allowBrowserFallback' => 1,
            'storageUsed'          => 0,
            'storageLimit'         => 10 * 1024 * 1024 * 1024, // 10 GB
            'appVersion'           => SettingsConst::DEFAULT_APP_VERSION,
            'updatedAt'            => DateModel::getTimeStampsCurrent(),
        ]);
        $dbAdapter->query($dbSql->buildSqlString($insert), $dbAdapter::QUERY_MODE_EXECUTE);

        return $this->getByUserId($userId);
    }

    /** Đặt lại cấu hình của user về mặc định (xóa dòng cũ rồi tạo lại mặc định). */
    public function resetToDefaults(int $userId): SettingsModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();
        $delete    = $dbSql->delete(SettingsMapper::TABLE_NAME);
        $delete->where(['userId' => $userId]);
        $dbAdapter->query($dbSql->buildSqlString($delete), $dbAdapter::QUERY_MODE_EXECUTE);

        return $this->ensureDefaults($userId);
    }

    public function updateSettings(int $userId, array $data): void
    {
        if (empty($data)) {
            return;
        }
        $data['updatedAt'] = DateModel::getTimeStampsCurrent();

        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();
        $update    = $dbSql->update(SettingsMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['userId' => $userId]);
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
    }
}
