<?php
declare(strict_types=1);

namespace User\Model\User;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng users.
 * - passwordHash không bao giờ được trả ra ngoài response.
 */
class UserModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?string $username = null;
    protected ?string $email = null;
    protected ?string $passwordHash = null;
    protected ?string $fullName = null;
    protected ?string $avatarUrl = null;
    protected ?string $role = null;
    protected ?string $plan = null;
    protected ?string $fbUserId = null;
    protected ?int $status = null;
    protected ?int $createdAt = null;
    protected ?int $updatedAt = null;

    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
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

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;
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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(?string $plan): self
    {
        $this->plan = $plan;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
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

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?int $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // -------------------------------------------------------------------------

    /** Dữ liệu an toàn để trả ra client (KHÔNG kèm passwordHash). */
    public function getRespUser(): array
    {
        return [
            'id'        => AppFormat::castIntOrNull($this->id),
            'username'  => AppFormat::castStringOrNull($this->username),
            'email'     => AppFormat::castStringOrNull($this->email),
            'fullName'  => AppFormat::castStringOrNull($this->fullName),
            'avatarUrl' => AppFormat::castStringOrNull($this->avatarUrl),
            'role'      => AppFormat::castStringOrNull($this->role),
            'plan'      => AppFormat::castStringOrNull($this->plan),
            'fbUserId'  => AppFormat::castStringOrNull($this->fbUserId),
            'status'    => AppFormat::castIntOrNull($this->status),
            'createdAt' => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
