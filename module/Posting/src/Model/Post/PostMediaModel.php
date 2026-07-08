<?php
declare(strict_types=1);

namespace Posting\Model\Post;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng post_media — ảnh/video đính kèm bài viết.
 * - Tối đa 10 ảnh HOẶC 1 video cho mỗi post (ràng buộc kiểm ở service).
 */
class PostMediaModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?int $postId = null;
    protected ?int $type = null;
    protected ?string $url = null;
    protected ?string $storagePath = null;
    protected ?int $orderIndex = null;
    protected ?int $createdAt = null;

    // --- Search helpers (không lưu DB) ---
    protected ?array $postIds = null;

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

    public function getPostId(): ?int
    {
        return $this->postId;
    }

    public function setPostId(?int $postId): self
    {
        $this->postId = $postId;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    public function setStoragePath(?string $storagePath): self
    {
        $this->storagePath = $storagePath;
        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(?int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
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

    // --- Search helpers (không lưu DB) ---

    public function getPostIds(): ?array
    {
        return $this->postIds;
    }

    public function setPostIds(?array $postIds): self
    {
        $this->postIds = $postIds;
        return $this;
    }

    // -------------------------------------------------------------------------

    public function getRespPostMedia(): array
    {
        return [
            'id'          => AppFormat::castIntOrNull($this->id),
            'postId'      => AppFormat::castIntOrNull($this->postId),
            'type'        => AppFormat::castIntOrNull($this->type),
            'url'         => AppFormat::castStringOrNull($this->url),
            'orderIndex'  => AppFormat::castIntOrNull($this->orderIndex),
            'createdAt'   => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
