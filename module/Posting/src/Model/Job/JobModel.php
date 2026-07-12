<?php
declare(strict_types=1);

namespace Posting\Model\Job;

use Application\Model\AppModel;

/**
 * Model bảng post_jobs — một job đăng bài trong hàng đợi.
 */
class JobModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $postId = null;
    protected ?int $status = null;
    protected ?string $runAt = null;
    protected ?string $lockToken = null;
    protected ?string $lockedAt = null;
    protected ?string $lastError = null;
    protected ?int $createdAt = null;
    protected ?int $modifiedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getPostId(): ?int
    {
        return $this->postId;
    }

    public function setPostId(?int $postId): self
    {
        $this->postId = $postId;
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

    public function getRunAt(): ?string
    {
        return $this->runAt;
    }

    public function setRunAt(?string $runAt): self
    {
        $this->runAt = $runAt;
        return $this;
    }

    public function getLockToken(): ?string
    {
        return $this->lockToken;
    }

    public function setLockToken(?string $lockToken): self
    {
        $this->lockToken = $lockToken;
        return $this;
    }

    public function getLockedAt(): ?string
    {
        return $this->lockedAt;
    }

    public function setLockedAt(?string $lockedAt): self
    {
        $this->lockedAt = $lockedAt;
        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setLastError(?string $lastError): self
    {
        $this->lastError = $lastError;
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

    public function getModifiedAt(): ?int
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?int $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    protected function getConstClass(): ?string
    {
        return JobConst::class;
    }
}
