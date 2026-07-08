<?php
declare(strict_types=1);

namespace Infra\Model\BrowserProfile;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng browser_profiles — Chrome profile chống phát hiện.
 * - Dữ liệu thuộc về user đăng nhập (scope theo cột createdById).
 * - Cột fingerprintJson là JSON tự do (canvas/webgl/fonts/timezone/screen…),
 *   không giới hạn theo whitelist EXT_* như capabilities — dùng extraContent/extraFields
 *   nguyên bản (getConstClass() = null → không cast/lọc field).
 */
class BrowserProfileModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?string $code = null;
    protected ?string $profileName = null;
    protected ?int $profileId = null;
    protected ?int $serverId = null;
    protected ?int $proxyId = null;
    protected ?int $facebookAccountId = null;
    protected ?int $status = null;
    protected ?int $mode = null;
    protected ?string $chromeVersion = null;
    protected ?string $os = null;
    protected ?string $userAgent = null;
    protected ?float $cpuPercent = null;
    protected ?int $ramMb = null;
    protected ?string $startedAt = null;
    protected ?string $lastActiveAt = null;
    protected ?int $uptimeMinutes = null;
    protected ?int $createdById = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    // --- Runtime / display fields (không lưu DB, gắn từ join) ---
    protected ?string $serverName = null;
    protected ?string $serverIp = null;
    protected ?string $proxyIp = null;
    protected ?string $facebookAccountEmail = null;

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

    public function getProfileName(): ?string
    {
        return $this->profileName;
    }

    public function setProfileName(?string $profileName): self
    {
        $this->profileName = $profileName;
        return $this;
    }

    public function getProfileId(): ?int
    {
        return $this->profileId;
    }

    public function setProfileId(?int $profileId): self
    {
        $this->profileId = $profileId;
        return $this;
    }

    public function getServerId(): ?int
    {
        return $this->serverId;
    }

    public function setServerId(?int $serverId): self
    {
        $this->serverId = $serverId;
        return $this;
    }

    public function getProxyId(): ?int
    {
        return $this->proxyId;
    }

    public function setProxyId(?int $proxyId): self
    {
        $this->proxyId = $proxyId;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMode(): ?int
    {
        return $this->mode;
    }

    public function setMode(?int $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    public function getChromeVersion(): ?string
    {
        return $this->chromeVersion;
    }

    public function setChromeVersion(?string $chromeVersion): self
    {
        $this->chromeVersion = $chromeVersion;
        return $this;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function setOs(?string $os): self
    {
        $this->os = $os;
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

    /** JSON tự do: canvas/webgl/fonts/timezone/screen... */
    public function setFingerprintJson(?string $raw): self
    {
        $this->setExtraContent($raw);
        return $this;
    }

    public function getFingerprintSummary(): array
    {
        return $this->getExtraFieldsArray();
    }

    public function getCpuPercent(): ?float
    {
        return $this->cpuPercent;
    }

    public function setCpuPercent(?float $cpuPercent): self
    {
        $this->cpuPercent = $cpuPercent;
        return $this;
    }

    public function getRamMb(): ?int
    {
        return $this->ramMb;
    }

    public function setRamMb(?int $ramMb): self
    {
        $this->ramMb = $ramMb;
        return $this;
    }

    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    public function setStartedAt(?string $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getLastActiveAt(): ?string
    {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(?string $lastActiveAt): self
    {
        $this->lastActiveAt = $lastActiveAt;
        return $this;
    }

    public function getUptimeMinutes(): ?int
    {
        return $this->uptimeMinutes;
    }

    public function setUptimeMinutes(?int $uptimeMinutes): self
    {
        $this->uptimeMinutes = $uptimeMinutes;
        return $this;
    }

    public function getCreatedById(): ?int
    {
        return $this->createdById;
    }

    public function setCreatedById(?int $createdById): self
    {
        $this->createdById = $createdById;
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

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function setServerName(?string $serverName): self
    {
        $this->serverName = $serverName;
        return $this;
    }

    public function getServerIp(): ?string
    {
        return $this->serverIp;
    }

    public function setServerIp(?string $serverIp): self
    {
        $this->serverIp = $serverIp;
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

    public function getFacebookAccountEmail(): ?string
    {
        return $this->facebookAccountEmail;
    }

    public function setFacebookAccountEmail(?string $facebookAccountEmail): self
    {
        $this->facebookAccountEmail = $facebookAccountEmail;
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

    public function getRespBrowserProfile(): array
    {
        return [
            'id'               => AppFormat::castIntOrNull($this->id),
            'code'             => AppFormat::castStringOrNull($this->code),
            'profileName'      => AppFormat::castStringOrNull($this->profileName),
            'server'           => $this->serverId ? [
                'id'   => AppFormat::castIntOrNull($this->serverId),
                'name' => AppFormat::castStringOrNull($this->serverName),
                'ip'   => AppFormat::castStringOrNull($this->serverIp),
            ] : null,
            'proxyIp'          => AppFormat::castStringOrNull($this->proxyIp),
            'facebookAccount'  => $this->facebookAccountId ? [
                'id'    => AppFormat::castIntOrNull($this->facebookAccountId),
                'email' => AppFormat::castStringOrNull($this->facebookAccountEmail),
            ] : null,
            'status'           => AppFormat::castIntOrNull($this->status),
            'mode'             => AppFormat::castIntOrNull($this->mode),
            'chromeVersion'    => AppFormat::castStringOrNull($this->chromeVersion),
            'os'               => AppFormat::castStringOrNull($this->os),
            'userAgent'        => AppFormat::castStringOrNull($this->userAgent),
            'fingerprint'      => $this->getFingerprintSummary(),
            'cpuPercent'       => AppFormat::castDoubleOrNull($this->cpuPercent),
            'ramMb'            => AppFormat::castIntOrNull($this->ramMb),
            'startedAt'        => $this->startedAt,
            'lastActiveAt'     => $this->lastActiveAt,
            'uptimeMinutes'    => AppFormat::castIntOrNull($this->uptimeMinutes),
            'modifiedAt'       => AppFormat::castIntOrNull($this->modifiedAt),
            'createdAt'        => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
