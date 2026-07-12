<?php
declare(strict_types=1);

namespace Posting\Model\Post;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Model bảng posts — bài viết / lịch đăng.
 * - Dữ liệu thuộc về user đăng nhập (scope theo cột createdById).
 * - Cột options (jsonb) được ánh xạ qua extraContent/extraFields (xem PostConst::EXT_*).
 */
class PostModel extends AppModel
{
    // --- DB columns ---
    protected ?int $id = null;
    protected ?string $title = null;
    protected ?string $content = null;
    protected ?int $contentType = null;
    protected ?int $targetType = null;
    protected ?int $fanpageId = null;
    protected ?int $facebookAccountId = null;
    protected ?int $browserProfileId = null;
    protected ?int $aiAgentId = null;
    protected ?int $status = null;
    protected ?int $channel = null;
    protected ?string $scheduledAt = null;
    protected ?string $publishedAt = null;
    protected ?int $attemptCount = null;
    protected ?int $maxAttempts = null;
    protected ?string $repeatRule = null;
    protected ?string $fbPostId = null;
    protected ?string $note = null;
    protected ?int $modifiedAt = null;
    protected ?int $modifiedById = null;
    protected ?int $createdById = null;
    protected ?int $createdAt = null;

    // --- Runtime / display fields (không lưu DB) ---
    protected ?string $fanpageName = null;
    protected ?string $facebookAccountName = null;
    protected ?string $browserProfileName = null;

    // --- Search helpers (không lưu DB) ---
    protected ?int $userId = null; // user đăng nhập — dùng để scope theo chủ sở hữu
    protected ?string $fromDate = null;
    protected ?string $toDate = null;
    protected ?array $statuses = [];

    protected array $user = [];

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContentType(): ?int
    {
        return $this->contentType;
    }

    public function setContentType(?int $contentType): self
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getTargetType(): ?int
    {
        return $this->targetType;
    }

    public function setTargetType(?int $targetType): self
    {
        $this->targetType = $targetType;
        return $this;
    }

    public function getFanpageId(): ?int
    {
        return $this->fanpageId;
    }

    public function setFanpageId(?int $fanpageId): self
    {
        $this->fanpageId = $fanpageId;
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

    public function getAiAgentId(): ?int
    {
        return $this->aiAgentId;
    }

    public function setAiAgentId(?int $aiAgentId): self
    {
        $this->aiAgentId = $aiAgentId;
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

    public function getChannel(): ?int
    {
        return $this->channel;
    }

    public function setChannel(?int $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function getScheduledAt(): ?string
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?string $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getPublishedAt(): ?string
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?string $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getAttemptCount(): ?int
    {
        return $this->attemptCount;
    }

    public function setAttemptCount(?int $attemptCount): self
    {
        $this->attemptCount = $attemptCount;
        return $this;
    }

    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(?int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    public function getRepeatRule(): ?string
    {
        return $this->repeatRule;
    }

    public function setRepeatRule(?string $repeatRule): self
    {
        $this->repeatRule = $repeatRule;
        return $this;
    }

    public function getFbPostId(): ?string
    {
        return $this->fbPostId;
    }

    public function setFbPostId(?string $fbPostId): self
    {
        $this->fbPostId = $fbPostId;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
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

    public function getModifiedById(): ?int
    {
        return $this->modifiedById;
    }

    public function setModifiedById(?int $modifiedById): self
    {
        $this->modifiedById = $modifiedById;
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

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // --- Runtime / display fields ---

    public function getFanpageName(): ?string
    {
        return $this->fanpageName;
    }

    public function setFanpageName(?string $fanpageName): self
    {
        $this->fanpageName = $fanpageName;
        return $this;
    }

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

    // -------------------------------------------------------------------------
    /*
     * options fields (lưu compressed trong cột options)
     * - Các key hợp lệ khai báo trong PostConst::EXT_*
     * - Logic decode/validate/cast kế thừa từ AppModel (dùng getConstClass)
     */

    protected function getConstClass(): ?string
    {
        return PostConst::class;
    }

    public function getAutoShortenLink(): bool
    {
        return (bool)($this->extraFields[PostConst::EXT_AUTO_SHORTEN_LINK] ?? false);
    }

    public function setAutoShortenLink(?bool $value): self
    {
        return $this->addExtraField(PostConst::EXT_AUTO_SHORTEN_LINK, $value);
    }

    public function getDisableCommentNotif(): bool
    {
        return (bool)($this->extraFields[PostConst::EXT_DISABLE_COMMENT_NOTIF] ?? false);
    }

    public function setDisableCommentNotif(?bool $value): self
    {
        return $this->addExtraField(PostConst::EXT_DISABLE_COMMENT_NOTIF, $value);
    }

    public function getAutoShare(): bool
    {
        return (bool)($this->extraFields[PostConst::EXT_AUTO_SHARE] ?? false);
    }

    public function setAutoShare(?bool $value): self
    {
        return $this->addExtraField(PostConst::EXT_AUTO_SHARE, $value);
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

    public function getFromDate(): ?string
    {
        return $this->fromDate;
    }

    public function setFromDate(?string $fromDate): self
    {
        $this->fromDate = $fromDate;
        return $this;
    }

    public function getToDate(): ?string
    {
        return $this->toDate;
    }

    public function setToDate(?string $toDate): self
    {
        $this->toDate = $toDate;
        return $this;
    }

    public function getStatuses(): ?array
    {
        return $this->statuses;
    }

    public function setStatuses(?array $statuses): self
    {
        $this->statuses = $statuses;
        return $this;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    // -------------------------------------------------------------------------

    public function getRespPost(): array
    {
        return [
            // --- DB columns ---
            'id'                 => AppFormat::castIntOrNull($this->id),
            'title'              => AppFormat::castStringOrNull($this->title),
            'content'            => AppFormat::castStringOrNull($this->content),
            'contentType'        => AppFormat::castIntOrNull($this->contentType),
            'targetType'         => AppFormat::castIntOrNull($this->targetType) ?? PostConst::TARGET_FANPAGE,
            'fanpage'            => $this->fanpageId ? [
                'id'   => AppFormat::castIntOrNull($this->fanpageId),
                'name' => AppFormat::castStringOrNull($this->fanpageName),
            ] : null,
            'facebookAccount'    => $this->facebookAccountId ? [
                'id'   => AppFormat::castIntOrNull($this->facebookAccountId),
                'name' => AppFormat::castStringOrNull($this->facebookAccountName),
            ] : null,
            'browserProfile'     => $this->browserProfileId ? [
                'id'   => AppFormat::castIntOrNull($this->browserProfileId),
                'name' => AppFormat::castStringOrNull($this->browserProfileName),
            ] : null,
            'aiAgentId'          => AppFormat::castIntOrNull($this->aiAgentId),
            'status'             => AppFormat::castIntOrNull($this->status),
            'channel'            => AppFormat::castIntOrNull($this->channel),
            'scheduledAt'        => $this->scheduledAt,
            'publishedAt'        => $this->publishedAt,
            'attemptCount'       => AppFormat::castIntOrNull($this->attemptCount),
            'maxAttempts'        => AppFormat::castIntOrNull($this->maxAttempts),
            'repeatRule'         => AppFormat::castStringOrNull($this->repeatRule),
            'fbPostId'           => AppFormat::castStringOrNull($this->fbPostId),
            'note'               => AppFormat::castStringOrNull($this->note),
            'modifiedAt'         => AppFormat::castIntOrNull($this->modifiedAt),
            'modifiedById'       => AppFormat::castIntOrNull($this->modifiedById),
            'createdAt'          => AppFormat::castIntOrNull($this->createdAt),
            'createdById'        => AppFormat::castIntOrNull($this->createdById),

            // --- options (jsonb) ---
            'options'            => [
                'autoShortenLink'     => $this->getAutoShortenLink(),
                'disableCommentNotif' => $this->getDisableCommentNotif(),
                'autoShare'           => $this->getAutoShare(),
            ],

            // --- Runtime / display fields ---
            'media'              => $this->getOption('media') ?? [],
            'metrics'            => $this->getOption('metrics') ?? null,
            'timeline'           => $this->getOption('timeline') ?? [],
            'user'               => $this->getUser() ?? [],
        ];
    }
}
