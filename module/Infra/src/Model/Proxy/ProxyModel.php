<?php
declare(strict_types=1);

namespace Infra\Model\Proxy;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng proxies — không có màn UI riêng, dùng nội bộ để join/hiển thị IP.
 */
class ProxyModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $ip = null;
    protected ?string $country = null;
    protected ?int $type = null;
    protected ?int $status = null;
    protected ?int $createdById = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): self
    {
        $this->type = $type;
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

    public function getRespProxy(): array
    {
        return [
            'id'      => AppFormat::castIntOrNull($this->id),
            'ip'      => AppFormat::castStringOrNull($this->ip),
            'country' => AppFormat::castStringOrNull($this->country),
            'type'    => AppFormat::castIntOrNull($this->type),
            'status'  => AppFormat::castIntOrNull($this->status),
        ];
    }
}
