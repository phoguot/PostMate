<?php
declare(strict_types=1);

namespace Posting\Filter\Post;

use Application\Filter\AuthScopedFilter;
use Application\Filter\CommonFieldFilters;

/**
 * Filter cho API danh sách bài viết (Lịch đăng / Bài viết).
 * Bộ lọc: status, fanpageId, browserProfileId, channel, khoảng ngày, keyword; phân trang offset.
 */
class PostListFilter extends AuthScopedFilter
{
    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $intFields = ['id', 'status', 'targetType', 'fanpageId', 'facebookAccountId', 'browserProfileId', 'channel', 'contentType'];
        foreach ($intFields as $fieldName) {
            $this->add(CommonFieldFilters::intField($fieldName));
        }

        $textFields = ['keyword', 'sort', 'dir'];
        foreach ($textFields as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, [
                'type' => CommonFieldFilters::TYPE_TEXT,
            ]));
        }

        $this->add(CommonFieldFilters::intArrayField('statuses'));
        $this->createCommonInputFilterDate('fromDate');
        $this->createCommonInputFilterDate('toDate');

        // Phân trang offset (page/pageSize) — mặc định trang 1, 30 bản ghi.
        $this->initInputPaging(1, 30);
    }

    public function setData($data)
    {
        $data = is_array($data) ? $data : [];
        $data['statuses'] = $this->normalizeIntArray($data['statuses'] ?? []);

        return parent::setData($data);
    }

    private function normalizeIntArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        } elseif (is_scalar($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $item = trim((string)$item);
            if ($item !== '' && ctype_digit($item) && (int)$item > 0) {
                $result[] = (int)$item;
            }
        }

        return array_values(array_unique($result));
    }
}
