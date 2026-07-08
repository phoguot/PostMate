<?php
declare(strict_types=1);

namespace Facebook\Model\FacebookAccount;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng facebook_accounts — tài khoản Facebook dùng để đăng bài.
 * - Dữ liệu thuộc về user đăng nhập (scope theo cột ownerUserId).
 * - Cột capabilities (jsonb) ánh xạ qua extraContent/extraFields (xem FacebookAccountConst::EXT_*).
 */
class FacebookAccountModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?int $ownerUserId = null;
    protected ?string $displayName = null;
    protected ?string $email = null;
    protected ?string $avatarUrl = null;
    protected ?string $fbUserId = null;
    protected ?string $userAccessToken = null;
    protected ?int $browserProfileId = null;
    protected ?int $status = null;
    protected ?string $accountRole = null;
    protected ?bool $isPrimary = null;
    protected ?string $expiresAt = null;
    protected ?string $lastLoginAt = null;
    protected ?string $lastLoginIp = null;
    protected ?string $device = null;
    protected ?string $userAgent = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    // --- Runtime / display fields (không lưu DB, gắn từ join) ---
    protected ?string $browserProfileName = null;
    protected ?string $serverName = null;
    protected ?string $proxyIp = null;
    protected ?int $fanpageCount = null;
    protected ?int $cookieStatus = null;
    protected ?string $cookieExpiresAt = null;

    // --- Search helpers (không lưu DB) ---
    protected ?int $userId = null; // user đăng nhập — dùng để scope theo chủ sở hữu

    // -------------------------------------------------------------------------
    // --- DB columns ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getOwnerUserId(): ?int
    {
        return $this->ownerUserId;
    }

    public function setOwnerUserId(?int $ownerUserId): self
    {
        $this->ownerUserId = $ownerUserId;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function getFbUserId(): ?string
    {
        return $this->fbUserId;
    }

    public function setFbUserId(?string $fbUserId): self
    {
        $this->fbUserId = $fbUserId;
        return $this;
    }

    /** Long-lived Graph API user access token — mã hóa at-rest, KHÔNG bao giờ trả thô ra getRespFacebookAccount(). */
    public function getUserAccessToken(): ?string
    {
        return $this->userAccessToken;
    }

    public function setUserAccessToken(?string $userAccessToken): self
    {
        $this->userAccessToken = $userAccessToken;
        return $this;
    }

    public function getBrowserProfileId(): ?int
    {
        return $this->browserProfileId;
    }

    public function setBrowserProfileId(?int $browserProfileId): self
    {
        $this->browserProfileId = $browserProfileId;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAccountRole(): ?string
    {
        return $this->accountRole;
    }

    public function setAccountRole(?string $accountRole): self
    {
        $this->accountRole = $accountRole;
        return $this;
    }

    public function getIsPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(?bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?string $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?string $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function setLastLoginIp(?string $lastLoginIp): self
    {
        $this->lastLoginIp = $lastLoginIp;
        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): self
    {
        $this->device = $device;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getModifiedAt(): ?int
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?int $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?int $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // --- Runtime / display fields ---

    public function getBrowserProfileName(): ?string
    {
        return $this->browserProfileName;
    }

    public function setBrowserProfileName(?string $browserProfileName): self
    {
        $this->browserProfileName = $browserProfileName;
        return $this;
    }

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function setServerName(?string $serverName): self
    {
        $this->serverName = $serverName;
        return $this;
    }

    public function getProxyIp(): ?string
    {
        return $this->proxyIp;
    }

    public function setProxyIp(?string $proxyIp): self
    {
        $this->proxyIp = $proxyIp;
        return $this;
    }

    public function getFanpageCount(): ?int
    {
        return $this->fanpageCount;
    }

    public function setFanpageCount(?int $fanpageCount): self
    {
        $this->fanpageCount = $fanpageCount;
        return $this;
    }

    public function getCookieStatus(): ?int
    {
        return $this->cookieStatus;
    }

    public function setCookieStatus(?int $cookieStatus): self
    {
        $this->cookieStatus = $cookieStatus;
        return $this;
    }

    public function getCookieExpiresAt(): ?string
    {
        return $this->cookieExpiresAt;
    }

    public function setCookieExpiresAt(?string $cookieExpiresAt): self
    {
        $this->cookieExpiresAt = $cookieExpiresAt;
        return $this;
    }

    // --- Search helpers (không lưu DB) ---

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    // -------------------------------------------------------------------------
    /*
     * capabilities (lưu compressed trong cột capabilities)
     * - Các key hợp lệ khai báo trong FacebookAccountConst::EXT_*
     */

    protected function getConstClass(): ?string
    {
        return FacebookAccountConst::class;
    }

    /** Nhận raw string từ DB, tự decode sang extraFields array. */
    public function setCapabilities(?string $raw): self
    {
        $this->setExtraContent($raw);
        return $this;
    }

    public function getCanPost(): bool
    {
        return (bool)($this->extraFields[FacebookAccountConst::EXT_CAN_POST] ?? false);
    }

    public function setCanPost(?bool $value): self
    {
        return $this->addExtraField(FacebookAccountConst::EXT_CAN_POST, $value);
    }

    public function getCanUpload(): bool
    {
        return (bool)($this->extraFields[FacebookAccountConst::EXT_CAN_UPLOAD] ?? false);
    }

    public function setCanUpload(?bool $value): self
    {
        return $this->addExtraField(FacebookAccountConst::EXT_CAN_UPLOAD, $value);
    }

    public function getCanComment(): bool
    {
        return (bool)($this->extraFields[FacebookAccountConst::EXT_CAN_COMMENT] ?? false);
    }

    public function setCanComment(?bool $value): self
    {
        return $this->addExtraField(FacebookAccountConst::EXT_CAN_COMMENT, $value);
    }

    public function getCanReply(): bool
    {
        return (bool)($this->extraFields[FacebookAccountConst::EXT_CAN_REPLY] ?? false);
    }

    public function setCanReply(?bool $value): self
    {
        return $this->addExtraField(FacebookAccountConst::EXT_CAN_REPLY, $value);
    }

    public function getCanInbox(): bool
    {
        return (bool)($this->extraFields[FacebookAccountConst::EXT_CAN_INBOX] ?? false);
    }

    public function setCanInbox(?bool $value): self
    {
        return $this->addExtraField(FacebookAccountConst::EXT_CAN_INBOX, $value);
    }

    // -------------------------------------------------------------------------

    public function getRespFacebookAccount(): array
    {
        return [
            'id'               => AppFormat::castIntOrNull($this->id),
            'displayName'      => AppFormat::castStringOrNull($this->displayName),
            'email'            => AppFormat::castStringOrNull($this->email),
            'avatarUrl'        => AppFormat::castStringOrNull($this->avatarUrl),
            'oauthConnected'   => (bool)$this->userAccessToken,
            'browserProfile'   => $this->browserProfileId ? [
                'id'   => AppFormat::castIntOrNull($this->browserProfileId),
                'name' => AppFormat::castStringOrNull($this->browserProfileName),
            ] : null,
            'server'           => AppFormat::castStringOrNull($this->serverName),
            'proxyIp'          => AppFormat::castStringOrNull($this->proxyIp),
            'status'           => AppFormat::castIntOrNull($this->status),
            'accountRole'      => AppFormat::castStringOrNull($this->accountRole),
            'isPrimary'        => (bool)$this->isPrimary,
            'expiresAt'        => $this->expiresAt,
            'lastLoginAt'      => $this->lastLoginAt,
            'lastLoginIp'      => AppFormat::castStringOrNull($this->lastLoginIp),
            'device'           => AppFormat::castStringOrNull($this->device),
            'userAgent'        => AppFormat::castStringOrNull($this->userAgent),
            'capabilities'     => [
                'canPost'    => $this->getCanPost(),
                'canUpload'  => $this->getCanUpload(),
                'canComment' => $this->getCanComment(),
                'canReply'   => $this->getCanReply(),
                'canInbox'   => $this->getCanInbox(),
            ],
            'fanpageCount'     => AppFormat::castIntOrNull($this->fanpageCount) ?? 0,
            'cookieStatus'     => AppFormat::castIntOrNull($this->cookieStatus),
            'cookieExpiresAt'  => $this->cookieExpiresAt,
            'modifiedAt'       => AppFormat::castIntOrNull($this->modifiedAt),
            'createdAt'        => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
