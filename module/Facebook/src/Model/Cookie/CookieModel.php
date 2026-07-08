<?php
declare(strict_types=1);

namespace Facebook\Model\Cookie;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng cookies — phiên đăng nhập Facebook.
 * - cookieBlob chỉ dùng nội bộ (nạp vào profile / export có kiểm quyền),
 *   KHÔNG bao giờ trả ra ở getRespCookie().
 */
class CookieModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?string $code = null;
    protected ?int $facebookAccountId = null;
    protected ?int $browserProfileId = null;
    protected ?float $sizeKb = null;
    protected ?int $status = null;
    protected ?string $expiresAt = null;
    protected ?string $lastLoginAt = null;
    protected ?string $lastLoginIp = null;
    protected ?string $device = null;
    protected ?string $userAgent = null;
    protected ?string $cookieBlob = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    // --- Runtime / display fields (không lưu DB, gắn từ join) ---
    protected ?string $facebookAccountName = null;
    protected ?string $browserProfileName = null;
    protected ?array $fanpageNames = null;
    protected ?int $daysLeft = null;

    // --- Search helpers (không lưu DB) ---
    protected ?int $userId = null;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getFacebookAccountId(): ?int
    {
        return $this->facebookAccountId;
    }

    public function setFacebookAccountId(?int $facebookAccountId): self
    {
        $this->facebookAccountId = $facebookAccountId;
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

    public function getSizeKb(): ?float
    {
        return $this->sizeKb;
    }

    public function setSizeKb(?float $sizeKb): self
    {
        $this->sizeKb = $sizeKb;
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

    /** Nội dung cookie mã hóa at-rest — chỉ dùng nội bộ (nạp profile / export có kiểm quyền). */
    public function getCookieBlob(): ?string
    {
        return $this->cookieBlob;
    }

    public function setCookieBlob(?string $cookieBlob): self
    {
        $this->cookieBlob = $cookieBlob;
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

    public function getFacebookAccountName(): ?string
    {
        return $this->facebookAccountName;
    }

    public function setFacebookAccountName(?string $facebookAccountName): self
    {
        $this->facebookAccountName = $facebookAccountName;
        return $this;
    }

    public function getBrowserProfileName(): ?string
    {
        return $this->browserProfileName;
    }

    public function setBrowserProfileName(?string $browserProfileName): self
    {
        $this->browserProfileName = $browserProfileName;
        return $this;
    }

    public function getFanpageNames(): ?array
    {
        return $this->fanpageNames;
    }

    public function setFanpageNames(?array $fanpageNames): self
    {
        $this->fanpageNames = $fanpageNames;
        return $this;
    }

    public function getDaysLeft(): ?int
    {
        if ($this->daysLeft !== null) {
            return $this->daysLeft;
        }
        if (!$this->expiresAt) {
            return null;
        }
        $diff = (int)ceil((strtotime($this->expiresAt) - time()) / 86400);
        return $diff;
    }

    public function setDaysLeft(?int $daysLeft): self
    {
        $this->daysLeft = $daysLeft;
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

    /** Response dùng cho danh sách/chi tiết — KHÔNG bao giờ trả cookieBlob thô. */
    public function getRespCookie(): array
    {
        return [
            'id'                => AppFormat::castIntOrNull($this->id),
            'code'              => AppFormat::castStringOrNull($this->code),
            'facebookAccount'   => $this->facebookAccountId ? [
                'id'   => AppFormat::castIntOrNull($this->facebookAccountId),
                'name' => AppFormat::castStringOrNull($this->facebookAccountName),
            ] : null,
            'browserProfile'    => $this->browserProfileId ? [
                'id'   => AppFormat::castIntOrNull($this->browserProfileId),
                'name' => AppFormat::castStringOrNull($this->browserProfileName),
            ] : null,
            'fanpages'          => $this->fanpageNames ?? [],
            'sizeKb'            => AppFormat::castDoubleOrNull($this->sizeKb),
            'status'            => AppFormat::castIntOrNull($this->status),
            'expiresAt'         => $this->expiresAt,
            'daysLeft'          => $this->getDaysLeft(),
            'lastLoginAt'       => $this->lastLoginAt,
            'lastLoginIp'       => AppFormat::castStringOrNull($this->lastLoginIp),
            'device'            => AppFormat::castStringOrNull($this->device),
            'userAgent'         => AppFormat::castStringOrNull($this->userAgent),
            'modifiedAt'        => AppFormat::castIntOrNull($this->modifiedAt),
            'createdAt'         => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
