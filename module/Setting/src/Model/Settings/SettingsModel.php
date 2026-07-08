<?php
declare(strict_types=1);

namespace Setting\Model\Settings;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng settings — cấu hình theo user (PK = userId, 1 dòng / user).
 */
class SettingsModel extends AppModel
{
    protected ?int $userId = null;
    protected ?string $language = null;
    protected ?string $timezone = null;
    protected ?string $dateFormat = null;
    protected ?string $themeMode = null;
    protected ?string $displayDensity = null;
    protected ?int $defaultFanpageId = null;
    protected ?int $defaultContentType = null;
    protected ?int $defaultStatus = null;
    protected ?string $defaultPostTime = null;
    protected ?bool $autoShortenLink = null;
    protected ?bool $autoSaveDraft = null;
    protected ?bool $showAiSuggestions = null;
    protected ?bool $confirmBeforePost = null;
    protected ?bool $confirmBeforeDelete = null;
    protected ?bool $autoSaveChanges = null;
    protected ?bool $notificationSound = null;
    protected ?bool $showQuickHints = null;
    protected ?bool $performanceTracking = null;
    protected ?int $preferredChannel = null;
    protected ?bool $allowBrowserFallback = null;
    protected ?int $storageUsed = null;
    protected ?int $storageLimit = null;
    protected ?string $appVersion = null;
    protected ?string $lastBackupAt = null;
    protected ?int $updatedAt = null;

    // --- Runtime / display fields (không lưu DB) ---
    protected ?string $defaultFanpageName = null;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(?string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getThemeMode(): ?string
    {
        return $this->themeMode;
    }

    public function setThemeMode(?string $themeMode): self
    {
        $this->themeMode = $themeMode;
        return $this;
    }

    public function getDisplayDensity(): ?string
    {
        return $this->displayDensity;
    }

    public function setDisplayDensity(?string $displayDensity): self
    {
        $this->displayDensity = $displayDensity;
        return $this;
    }

    public function getDefaultFanpageId(): ?int
    {
        return $this->defaultFanpageId;
    }

    public function setDefaultFanpageId(?int $defaultFanpageId): self
    {
        $this->defaultFanpageId = $defaultFanpageId;
        return $this;
    }

    public function getDefaultContentType(): ?int
    {
        return $this->defaultContentType;
    }

    public function setDefaultContentType(?int $defaultContentType): self
    {
        $this->defaultContentType = $defaultContentType;
        return $this;
    }

    public function getDefaultStatus(): ?int
    {
        return $this->defaultStatus;
    }

    public function setDefaultStatus(?int $defaultStatus): self
    {
        $this->defaultStatus = $defaultStatus;
        return $this;
    }

    public function getDefaultPostTime(): ?string
    {
        return $this->defaultPostTime;
    }

    public function setDefaultPostTime(?string $defaultPostTime): self
    {
        $this->defaultPostTime = $defaultPostTime;
        return $this;
    }

    public function getAutoShortenLink(): ?bool
    {
        return $this->autoShortenLink;
    }

    public function setAutoShortenLink(?bool $autoShortenLink): self
    {
        $this->autoShortenLink = $autoShortenLink;
        return $this;
    }

    public function getAutoSaveDraft(): ?bool
    {
        return $this->autoSaveDraft;
    }

    public function setAutoSaveDraft(?bool $autoSaveDraft): self
    {
        $this->autoSaveDraft = $autoSaveDraft;
        return $this;
    }

    public function getShowAiSuggestions(): ?bool
    {
        return $this->showAiSuggestions;
    }

    public function setShowAiSuggestions(?bool $showAiSuggestions): self
    {
        $this->showAiSuggestions = $showAiSuggestions;
        return $this;
    }

    public function getConfirmBeforePost(): ?bool
    {
        return $this->confirmBeforePost;
    }

    public function setConfirmBeforePost(?bool $confirmBeforePost): self
    {
        $this->confirmBeforePost = $confirmBeforePost;
        return $this;
    }

    public function getConfirmBeforeDelete(): ?bool
    {
        return $this->confirmBeforeDelete;
    }

    public function setConfirmBeforeDelete(?bool $confirmBeforeDelete): self
    {
        $this->confirmBeforeDelete = $confirmBeforeDelete;
        return $this;
    }

    public function getAutoSaveChanges(): ?bool
    {
        return $this->autoSaveChanges;
    }

    public function setAutoSaveChanges(?bool $autoSaveChanges): self
    {
        $this->autoSaveChanges = $autoSaveChanges;
        return $this;
    }

    public function getNotificationSound(): ?bool
    {
        return $this->notificationSound;
    }

    public function setNotificationSound(?bool $notificationSound): self
    {
        $this->notificationSound = $notificationSound;
        return $this;
    }

    public function getShowQuickHints(): ?bool
    {
        return $this->showQuickHints;
    }

    public function setShowQuickHints(?bool $showQuickHints): self
    {
        $this->showQuickHints = $showQuickHints;
        return $this;
    }

    public function getPerformanceTracking(): ?bool
    {
        return $this->performanceTracking;
    }

    public function setPerformanceTracking(?bool $performanceTracking): self
    {
        $this->performanceTracking = $performanceTracking;
        return $this;
    }

    public function getPreferredChannel(): ?int
    {
        return $this->preferredChannel;
    }

    public function setPreferredChannel(?int $preferredChannel): self
    {
        $this->preferredChannel = $preferredChannel;
        return $this;
    }

    public function getAllowBrowserFallback(): ?bool
    {
        return $this->allowBrowserFallback;
    }

    public function setAllowBrowserFallback(?bool $allowBrowserFallback): self
    {
        $this->allowBrowserFallback = $allowBrowserFallback;
        return $this;
    }

    public function getStorageUsed(): ?int
    {
        return $this->storageUsed;
    }

    public function setStorageUsed(?int $storageUsed): self
    {
        $this->storageUsed = $storageUsed;
        return $this;
    }

    public function getStorageLimit(): ?int
    {
        return $this->storageLimit;
    }

    public function setStorageLimit(?int $storageLimit): self
    {
        $this->storageLimit = $storageLimit;
        return $this;
    }

    public function getAppVersion(): ?string
    {
        return $this->appVersion;
    }

    public function setAppVersion(?string $appVersion): self
    {
        $this->appVersion = $appVersion;
        return $this;
    }

    public function getLastBackupAt(): ?string
    {
        return $this->lastBackupAt;
    }

    public function setLastBackupAt(?string $lastBackupAt): self
    {
        $this->lastBackupAt = $lastBackupAt;
        return $this;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?int $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDefaultFanpageName(): ?string
    {
        return $this->defaultFanpageName;
    }

    public function setDefaultFanpageName(?string $defaultFanpageName): self
    {
        $this->defaultFanpageName = $defaultFanpageName;
        return $this;
    }

    public function getRespSettings(): array
    {
        return [
            'language'              => AppFormat::castStringOrNull($this->language),
            'timezone'              => AppFormat::castStringOrNull($this->timezone),
            'dateFormat'            => AppFormat::castStringOrNull($this->dateFormat),
            'themeMode'             => AppFormat::castStringOrNull($this->themeMode),
            'displayDensity'        => AppFormat::castStringOrNull($this->displayDensity),
            'defaultFanpage'        => $this->defaultFanpageId ? [
                'id'   => AppFormat::castIntOrNull($this->defaultFanpageId),
                'name' => AppFormat::castStringOrNull($this->defaultFanpageName),
            ] : null,
            'defaultContentType'    => AppFormat::castIntOrNull($this->defaultContentType),
            'defaultStatus'         => AppFormat::castIntOrNull($this->defaultStatus),
            'defaultPostTime'       => $this->defaultPostTime,
            'autoShortenLink'       => (bool)$this->autoShortenLink,
            'autoSaveDraft'         => (bool)$this->autoSaveDraft,
            'showAiSuggestions'     => (bool)$this->showAiSuggestions,
            'confirmBeforePost'     => (bool)$this->confirmBeforePost,
            'confirmBeforeDelete'   => (bool)$this->confirmBeforeDelete,
            'autoSaveChanges'       => (bool)$this->autoSaveChanges,
            'notificationSound'     => (bool)$this->notificationSound,
            'showQuickHints'        => (bool)$this->showQuickHints,
            'performanceTracking'   => (bool)$this->performanceTracking,
            'preferredChannel'      => AppFormat::castIntOrNull($this->preferredChannel),
            'allowBrowserFallback'  => (bool)$this->allowBrowserFallback,
            'storageUsed'           => AppFormat::castIntOrNull($this->storageUsed) ?? 0,
            'storageLimit'          => AppFormat::castIntOrNull($this->storageLimit) ?? 0,
            'appVersion'            => AppFormat::castStringOrNull($this->appVersion),
            'lastBackupAt'          => $this->lastBackupAt,
            'updatedAt'             => AppFormat::castIntOrNull($this->updatedAt),
        ];
    }
}
