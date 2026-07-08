<?php
declare(strict_types=1);

namespace Application\Model\ActivityLog;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng activity_logs — nhật ký hoạt động dùng chung toàn hệ thống.
 */
class ActivityLogModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected ?string $entityRef = null;
    protected ?string $type = null;
    protected ?string $message = null;
    protected ?int $level = null;
    protected ?string $actorRole = null;
    protected ?string $objectName = null;
    protected ?string $objectType = null;
    protected ?string $ipAddress = null;
    protected ?string $device = null;
    protected ?int $createdAt = null;

    // --- Runtime / display fields (join từ users, không lưu cột riêng) ---
    protected ?string $actorName = null;
    protected ?string $actorAvatar = null;
    // Search helpers (keyword/type/level/dateFrom/dateTo) dùng $options kế thừa từ AppModel.

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
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

    public function getEntityRef(): ?string
    {
        return $this->entityRef;
    }

    public function setEntityRef(?string $entityRef): self
    {
        $this->entityRef = $entityRef;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getActorRole(): ?string
    {
        return $this->actorRole;
    }

    public function setActorRole(?string $actorRole): self
    {
        $this->actorRole = $actorRole;
        return $this;
    }

    public function getObjectName(): ?string
    {
        return $this->objectName;
    }

    public function setObjectName(?string $objectName): self
    {
        $this->objectName = $objectName;
        return $this;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function setObjectType(?string $objectType): self
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
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

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?int $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    public function setActorName(?string $actorName): self
    {
        $this->actorName = $actorName;
        return $this;
    }

    public function getActorAvatar(): ?string
    {
        return $this->actorAvatar;
    }

    public function setActorAvatar(?string $actorAvatar): self
    {
        $this->actorAvatar = $actorAvatar;
        return $this;
    }

    public function getRespActivityLog(): array
    {
        return [
            'id'          => AppFormat::castIntOrNull($this->id),
            'entityRef'   => AppFormat::castStringOrNull($this->entityRef),
            'type'        => AppFormat::castStringOrNull($this->type),
            'message'     => AppFormat::castStringOrNull($this->message),
            'level'       => AppFormat::castIntOrNull($this->level),
            'actor'       => [
                'id'     => AppFormat::castIntOrNull($this->userId),
                'name'   => AppFormat::castStringOrNull($this->actorName),
                'avatar' => AppFormat::castStringOrNull($this->actorAvatar),
                'role'   => AppFormat::castStringOrNull($this->actorRole),
            ],
            'objectName'  => AppFormat::castStringOrNull($this->objectName),
            'objectType'  => AppFormat::castStringOrNull($this->objectType),
            'ipAddress'   => AppFormat::castStringOrNull($this->ipAddress),
            'device'      => AppFormat::castStringOrNull($this->device),
            'createdAt'   => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
