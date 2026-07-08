<?php
declare(strict_types=1);

namespace Setting\Model\MetaAppCredential;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng meta_app_credentials — cấu hình Meta App (Cài đặt > Facebook > Token & Quyền).
 * - appSecret/systemUserToken mã hóa at-rest, KHÔNG bao giờ trả nguyên ra ở getRespMetaApp().
 */
class MetaAppCredentialModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $appId = null;
    protected ?string $appSecret = null;
    protected ?string $systemUserToken = null;
    protected ?int $createdById = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    protected ?int $userId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(?string $appId): self
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppSecret(): ?string
    {
        return $this->appSecret;
    }

    public function setAppSecret(?string $appSecret): self
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    public function getSystemUserToken(): ?string
    {
        return $this->systemUserToken;
    }

    public function setSystemUserToken(?string $systemUserToken): self
    {
        $this->systemUserToken = $systemUserToken;
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /** Response — KHÔNG trả appSecret/systemUserToken thô, chỉ báo đã kết nối hay chưa. */
    public function getRespMetaApp(): array
    {
        return [
            'id'                    => AppFormat::castIntOrNull($this->id),
            'appId'                 => AppFormat::castStringOrNull($this->appId),
            'connected'             => (bool)$this->appId,
            'hasSystemUserToken'    => (bool)$this->systemUserToken,
            'modifiedAt'            => AppFormat::castIntOrNull($this->modifiedAt),
            'createdAt'             => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
