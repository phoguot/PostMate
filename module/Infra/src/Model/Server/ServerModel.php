<?php
declare(strict_types=1);

namespace Infra\Model\Server;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng servers — máy chủ host browser_profiles.
 * - Không có màn UI riêng (docs chỉ có màn Trình duyệt); dùng nội bộ để join/hiển thị.
 */
class ServerModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $name = null;
    protected ?string $ipAddress = null;
    protected ?int $status = null;
    protected ?float $cpuUsage = null;
    protected ?float $ramUsage = null;
    protected ?int $maxInstances = null;
    protected ?int $createdById = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    protected ?int $userId = null;
    protected ?int $runningInstances = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCpuUsage(): ?float
    {
        return $this->cpuUsage;
    }

    public function setCpuUsage(?float $cpuUsage): self
    {
        $this->cpuUsage = $cpuUsage;
        return $this;
    }

    public function getRamUsage(): ?float
    {
        return $this->ramUsage;
    }

    public function setRamUsage(?float $ramUsage): self
    {
        $this->ramUsage = $ramUsage;
        return $this;
    }

    public function getMaxInstances(): ?int
    {
        return $this->maxInstances;
    }

    public function setMaxInstances(?int $maxInstances): self
    {
        $this->maxInstances = $maxInstances;
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

    public function getRunningInstances(): ?int
    {
        return $this->runningInstances;
    }

    public function setRunningInstances(?int $runningInstances): self
    {
        $this->runningInstances = $runningInstances;
        return $this;
    }

    public function getRespServer(): array
    {
        return [
            'id'                => AppFormat::castIntOrNull($this->id),
            'name'              => AppFormat::castStringOrNull($this->name),
            'ipAddress'         => AppFormat::castStringOrNull($this->ipAddress),
            'status'            => AppFormat::castIntOrNull($this->status),
            'cpuUsage'          => AppFormat::castDoubleOrNull($this->cpuUsage),
            'ramUsage'          => AppFormat::castDoubleOrNull($this->ramUsage),
            'maxInstances'      => AppFormat::castIntOrNull($this->maxInstances) ?? 0,
            'runningInstances'  => AppFormat::castIntOrNull($this->runningInstances) ?? 0,
            'modifiedAt'        => AppFormat::castIntOrNull($this->modifiedAt),
            'createdAt'         => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
