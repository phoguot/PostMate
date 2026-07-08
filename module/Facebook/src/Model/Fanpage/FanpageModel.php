<?php
declare(strict_types=1);

namespace Facebook\Model\Fanpage;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng fanpages — fanpage do tài khoản Facebook quản lý.
 * - Dữ liệu thuộc về user đăng nhập, scope gián tiếp qua facebook_accounts.ownerUserId.
 * - Cột capabilities (jsonb) ánh xạ qua extraContent/extraFields (xem FanpageConst::EXT_*).
 */
class FanpageModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?string $fbPageId = null;
    protected ?string $name = null;
    protected ?string $category = null;
    protected ?string $url = null;
    protected ?int $facebookAccountId = null;
    protected ?int $browserProfileId = null;
    protected ?int $likesCount = null;
    protected ?int $followersCount = null;
    protected ?int $status = null;
    protected ?bool $canPost = null;
    protected ?string $lastPostAt = null;
    protected ?string $pageAccessToken = null;
    protected ?string $tokenExpiresAt = null;
    protected ?bool $apiEnabled = null;
    protected ?int $modifiedAt = null;
    protected ?int $createdAt = null;

    // --- Runtime / display fields (không lưu DB, gắn từ join) ---
    protected ?string $facebookAccountName = null;
    protected ?string $browserProfileName = null;
    protected ?string $canPostReason = null;

    // --- Search helpers (không lưu DB) ---
    protected ?int $userId = null; // user đăng nhập — scope gián tiếp qua facebook_accounts
    protected ?int $facebookAccountIds = null; // dùng nội bộ, xem setFacebookAccountIdsFilter

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

    public function getFbPageId(): ?string
    {
        return $this->fbPageId;
    }

    public function setFbPageId(?string $fbPageId): self
    {
        $this->fbPageId = $fbPageId;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
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

    public function getLikesCount(): ?int
    {
        return $this->likesCount;
    }

    public function setLikesCount(?int $likesCount): self
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function getFollowersCount(): ?int
    {
        return $this->followersCount;
    }

    public function setFollowersCount(?int $followersCount): self
    {
        $this->followersCount = $followersCount;
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

    public function getCanPost(): ?bool
    {
        return $this->canPost;
    }

    public function setCanPost(?bool $canPost): self
    {
        $this->canPost = $canPost;
        return $this;
    }

    public function getLastPostAt(): ?string
    {
        return $this->lastPostAt;
    }

    public function setLastPostAt(?string $lastPostAt): self
    {
        $this->lastPostAt = $lastPostAt;
        return $this;
    }

    public function getPageAccessToken(): ?string
    {
        return $this->pageAccessToken;
    }

    public function setPageAccessToken(?string $pageAccessToken): self
    {
        $this->pageAccessToken = $pageAccessToken;
        return $this;
    }

    public function getTokenExpiresAt(): ?string
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?string $tokenExpiresAt): self
    {
        $this->tokenExpiresAt = $tokenExpiresAt;
        return $this;
    }

    public function getApiEnabled(): ?bool
    {
        return $this->apiEnabled;
    }

    public function setApiEnabled(?bool $apiEnabled): self
    {
        $this->apiEnabled = $apiEnabled;
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

    public function getCanPostReason(): ?string
    {
        return $this->canPostReason;
    }

    public function setCanPostReason(?string $canPostReason): self
    {
        $this->canPostReason = $canPostReason;
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
     * - Các key hợp lệ khai báo trong FanpageConst::EXT_*
     */

    protected function getConstClass(): ?string
    {
        return FanpageConst::class;
    }

    public function setCapabilities(?string $raw): self
    {
        $this->setExtraContent($raw);
        return $this;
    }

    public function getCanUpload(): bool
    {
        return (bool)($this->extraFields[FanpageConst::EXT_CAN_UPLOAD] ?? false);
    }

    public function setCanUpload(?bool $value): self
    {
        return $this->addExtraField(FanpageConst::EXT_CAN_UPLOAD, $value);
    }

    public function getCanComment(): bool
    {
        return (bool)($this->extraFields[FanpageConst::EXT_CAN_COMMENT] ?? false);
    }

    public function setCanComment(?bool $value): self
    {
        return $this->addExtraField(FanpageConst::EXT_CAN_COMMENT, $value);
    }

    public function getCanReply(): bool
    {
        return (bool)($this->extraFields[FanpageConst::EXT_CAN_REPLY] ?? false);
    }

    public function setCanReply(?bool $value): self
    {
        return $this->addExtraField(FanpageConst::EXT_CAN_REPLY, $value);
    }

    public function getCanInbox(): bool
    {
        return (bool)($this->extraFields[FanpageConst::EXT_CAN_INBOX] ?? false);
    }

    public function setCanInbox(?bool $value): self
    {
        return $this->addExtraField(FanpageConst::EXT_CAN_INBOX, $value);
    }

    // -------------------------------------------------------------------------

    public function getRespFanpage(): array
    {
        return [
            'id'                => AppFormat::castIntOrNull($this->id),
            'fbPageId'          => AppFormat::castStringOrNull($this->fbPageId),
            'name'              => AppFormat::castStringOrNull($this->name),
            'category'          => AppFormat::castStringOrNull($this->category),
            'url'               => AppFormat::castStringOrNull($this->url),
            'facebookAccount'   => $this->facebookAccountId ? [
                'id'   => AppFormat::castIntOrNull($this->facebookAccountId),
                'name' => AppFormat::castStringOrNull($this->facebookAccountName),
            ] : null,
            'browserProfile'    => $this->browserProfileId ? [
                'id'   => AppFormat::castIntOrNull($this->browserProfileId),
                'name' => AppFormat::castStringOrNull($this->browserProfileName),
            ] : null,
            'likesCount'        => AppFormat::castIntOrNull($this->likesCount) ?? 0,
            'followersCount'    => AppFormat::castIntOrNull($this->followersCount) ?? 0,
            'status'            => AppFormat::castIntOrNull($this->status),
            'canPost'           => (bool)$this->canPost,
            'canPostReason'     => $this->canPostReason,
            'capabilities'      => [
                'canUpload'  => $this->getCanUpload(),
                'canComment' => $this->getCanComment(),
                'canReply'   => $this->getCanReply(),
                'canInbox'   => $this->getCanInbox(),
            ],
            'lastPostAt'        => $this->lastPostAt,
            'apiEnabled'        => (bool)$this->apiEnabled,
            'tokenExpiresAt'    => $this->tokenExpiresAt,
            'channel'           => $this->apiEnabled ? FanpageConst::CHANNEL_GRAPH_API : FanpageConst::CHANNEL_BROWSER,
            'modifiedAt'        => AppFormat::castIntOrNull($this->modifiedAt),
            'createdAt'         => AppFormat::castIntOrNull($this->createdAt),
        ];
    }
}
