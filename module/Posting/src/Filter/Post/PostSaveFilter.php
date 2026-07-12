<?php
declare(strict_types=1);

namespace Posting\Filter\Post;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;
use Application\Model\AppMessage;
use Posting\Model\Post\PostConst;

/**
 * Filter cho tạo/cập nhật bài viết (Lưu nháp / Lên lịch / Đăng ngay).
 * - Gom nhiều khối payload từ màn Tạo bài viết (nội dung, thiết lập đăng,
 *   thiết lập nâng cao) về một mảng phẳng.
 * - fanpageIds: chọn nhiều fanpage → service tạo một bài cho mỗi fanpage.
 */
class PostSaveFilter extends AuthScopedFilter
{
    private bool $isValidFanpageIds = true;
    private bool $isValidFacebookAccountIds = true;

    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $intFields = ['id', 'targetType', 'fanpageId', 'facebookAccountId', 'browserProfileId', 'aiAgentId', 'contentType', 'status', 'channel', 'maxAttempts'];
        foreach ($intFields as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }

        $textFields = [
            'title'      => CommonFieldFilters::LEN_TITLE,
            'repeatRule' => CommonFieldFilters::LEN_TITLE,
            'note'       => PostConst::NOTE_MAX_LENGTH,
        ];
        foreach ($textFields as $fieldName => $maxLength) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'maxLength' => $maxLength,
            ]));
        }

        // Toggle options (0/1)
        foreach (['autoShortenLink', 'disableCommentNotif', 'autoShare'] as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }

        $this->add($this->richTextField('content'));
        $this->add(CommonFieldFilters::objectArrayField('media'));
        $this->add(CommonFieldFilters::intArrayField('fanpageIds'));
        $this->add(CommonFieldFilters::intArrayField('facebookAccountIds'));

        $this->createCommonInputFilterDateTime('scheduledAt');
    }

    public function setData($data)
    {
        $data    = is_array($data) ? $data : [];
        $content = $data['content'] ?? [];
        $schedule = $data['schedule'] ?? [];
        $advanced = $data['advanced'] ?? [];
        $optionsIn = $data['options'] ?? $advanced['options'] ?? [];

        [$fanpageIds, $isValidFanpageIds] = $this->normalizeIntArray(
            $data['fanpageIds'] ?? $data['fanpages'] ?? null
        );
        $this->isValidFanpageIds = $isValidFanpageIds;

        [$facebookAccountIds, $isValidAccountIds] = $this->normalizeIntArray(
            $data['facebookAccountIds'] ?? $data['facebookAccounts'] ?? null
        );
        $this->isValidFacebookAccountIds = $isValidAccountIds;

        return parent::setData([
            'id'                  => $data['id'] ?? null,
            'targetType'          => $data['targetType'] ?? null,
            'fanpageId'           => $data['fanpageId'] ?? null,
            'facebookAccountId'   => $data['facebookAccountId'] ?? null,
            'browserProfileId'    => $data['browserProfileId'] ?? $advanced['browserProfileId'] ?? null,
            'aiAgentId'           => $data['aiAgentId'] ?? null,
            'contentType'         => $data['contentType'] ?? $content['type'] ?? null,
            'status'              => $data['status'] ?? null,
            'channel'             => $data['channel'] ?? null,
            'maxAttempts'         => $data['maxAttempts'] ?? null,
            'title'               => $data['title'] ?? $content['title'] ?? '',
            'content'             => $data['content'] ?? $content['text'] ?? '',
            'repeatRule'          => $data['repeatRule'] ?? $schedule['repeatRule'] ?? '',
            'note'                => $data['note'] ?? $advanced['note'] ?? '',
            'autoShortenLink'     => $this->toBoolInt($data['autoShortenLink'] ?? $optionsIn['autoShortenLink'] ?? null),
            'disableCommentNotif' => $this->toBoolInt($data['disableCommentNotif'] ?? $optionsIn['disableCommentNotif'] ?? null),
            'autoShare'           => $this->toBoolInt($data['autoShare'] ?? $optionsIn['autoShare'] ?? null),
            'media'               => $data['media'] ?? $content['media'] ?? $content['images'] ?? [],
            'fanpageIds'          => $fanpageIds,
            'facebookAccountIds'  => $facebookAccountIds,
            'scheduledAt'         => $data['scheduledAt'] ?? $schedule['scheduledAt'] ?? '',
        ]);
    }

    public function isValid($context = null): bool
    {
        $isValid = parent::isValid($context);
        if (!$isValid) {
            return false;
        }
        if (!$this->isValidFanpageIds) {
            $this->setError('fanpageIds', AppMessage::INVALID_DATA);
            return false;
        }
        if (!$this->isValidFacebookAccountIds) {
            $this->setError('facebookAccountIds', AppMessage::INVALID_DATA);
            return false;
        }
        return true;
    }

    private function richTextField(string $fieldName, bool $required = false): array
    {
        return [
            'name'       => $fieldName,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [],
        ];
    }

    private function toBoolInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    private function normalizeIntArray($value): array
    {
        if ($value === null || $value === '') {
            return [[], true];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        } elseif (is_scalar($value)) {
            $value = [$value];
        }
        if (!is_array($value)) {
            return [[], false];
        }

        $ids = [];
        foreach ($value as $id) {
            if (!is_scalar($id)) {
                return [[], false];
            }
            $id = trim((string)$id);
            if ($id === '' || !ctype_digit($id) || (int)$id <= 0) {
                return [[], false];
            }
            $ids[] = (int)$id;
        }
        return [array_values(array_unique($ids)), true];
    }
}
