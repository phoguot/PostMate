<?php

declare(strict_types=1);

namespace Posting\Model\Post;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\Fanpage\FanpageMapper;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

/**
 * Mapper bảng posts.
 * - Dữ liệu thuộc về user đăng nhập: mọi truy vấn scope theo createdById.
 * - Dùng 1 kết nối DB (getDbAdapter/getDbSql), scope theo user đăng nhập.
 * - Media đính kèm ủy quyền cho PostMediaMapper.
 */
class PostMapper extends AppMapper
{
    public const TABLE_NAME = 'posts';

    // -------------------------------------------------------------------------
    // Read

    private function buildPostSelect(PostModel $item): Select
    {
        $select = $this->getDbSql()->select(['p' => PostMapper::TABLE_NAME]);
        $this->applyCommonPostFilters($select, $item);
        return $select;
    }

    private function applyCommonPostFilters(Select $select, PostModel $item): void
    {
        // Scope theo chủ sở hữu (user đăng nhập)
        if ($item->getUserId()) {
            $select->where(['p.createdById' => $item->getUserId()]);
        }
        if ($item->getId()) {
            $select->where(['p.id' => $item->getId()]);
        }
        // Mặc định không lấy bài đã xóa mềm, trừ khi lọc đúng status = deleted
        if ($item->getStatus() !== null) {
            $select->where(['p.status' => $item->getStatus()]);
        } elseif (! empty($item->getStatuses())) {
            $select->where->in('p.status', $item->getStatuses());
        } else {
            $select->where(['p.status != ?' => PostConst::STATUS_DELETED]);
        }
        if ($item->getTargetType()) {
            $select->where(['p.targetType' => $item->getTargetType()]);
        }
        if ($item->getFanpageId()) {
            $select->where(['p.fanpageId' => $item->getFanpageId()]);
        }
        if ($item->getFacebookAccountId()) {
            $select->where(['p.facebookAccountId' => $item->getFacebookAccountId()]);
        }
        if ($item->getBrowserProfileId()) {
            $select->where(['p.browserProfileId' => $item->getBrowserProfileId()]);
        }
        if ($item->getChannel()) {
            $select->where(['p.channel' => $item->getChannel()]);
        }
        if ($item->getFromDate()) {
            $select->where(['p.scheduledAt >= ?' => $item->getFromDate() . ' 00:00:00']);
        }
        if ($item->getToDate()) {
            $select->where(['p.scheduledAt <= ?' => $item->getToDate() . ' 23:59:59']);
        }
        if ($item->getOption('keyword')) {
            $select->where(['p.title LIKE ?' => '%' . $item->getOption('keyword') . '%']);
        }

        $sort    = $item->getOption('sort');
        $dir     = strtoupper($item->getOption('dir') ?: 'DESC');
        $validSortCols = ['id', 'scheduledAt', 'publishedAt', 'createdAt'];
        $sortCol = in_array($sort, $validSortCols, true) ? $sort : 'id';
        $select->order(["p.$sortCol $dir"]);
    }

    /**
     * Tìm kiếm bài viết (offset paging).
     * Trả về ['items' => PostModel[], 'total' => int].
     */
    public function searchPost(PostModel $item, int $page = 1, int $pageSize = 30): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select = $this->buildPostSelect($item);
        $select->limit($pageSize);
        $select->offset(max(0, ($page - 1) * $pageSize));

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        $items = [];
        foreach ($rows->toArray() as $row) {
            $items[] = $this->hydrateModel($row);
        }

        $items = $this->getInforPost($items);
        $total = $this->countBySelect($this->buildPostSelect($item));

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Dựng PostModel từ 1 dòng DB.
     * Cột `options` (jsonb) KHÔNG được đưa qua exchangeArray()->setOptions(): tên cột trùng
     * với property $options dùng chung của AppModel (bag runtime kiểu ?array, dùng cho
     * addOption/getOption ở getInforPost/getRespPost) — gán thẳng chuỗi JSON từ DB vào đó sẽ
     * ném TypeError. Tách riêng: exchangeArray() phần còn lại, rồi setExtraContent() cho `options`.
     */
    private function hydrateModel(array $row): PostModel
    {
        $model = new PostModel();
        $this->exchangeRowInto($model, $row);
        return $model;
    }

    private function exchangeRowInto(PostModel $model, array $row): void
    {
        $optionsRaw = $row['options'] ?? null;
        unset($row['options']);

        $model->exchangeArray($row);
        $model->setExtraContent($optionsRaw);
    }

    private function countBySelect(Select $select): int
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        $select->reset('limit');
        $select->reset('offset');
        $select->reset('order');

        $rawSql = $dbSql->buildSqlString($select);
        $sql    = "SELECT COUNT(*) AS total FROM ($rawSql) AS sub";
        $rows   = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);
        $row    = $rows->current();

        return (int)(((array)$row)['total'] ?? 0);
    }

    /**
     * Gắn thông tin liên quan cho danh sách bài viết:
     * - media (post_media)
     * - fanpageName / browserProfileName (khi các module Fanpage / TrinhDuyet sẵn sàng)
     */
    private function getInforPost(array $items): array
    {
        if (! $items) {
            return $items;
        }

        $postIds = [];
        foreach ($items as $p) {
            /** @var PostModel $p */
            if ($p->getId()) {
                $postIds[] = $p->getId();
            }
        }

        // Media theo postId
        $mediaMap = [];
        if (! empty($postIds)) {
            $mediaModel = new PostMediaModel();
            $mediaModel->setPostIds($postIds);
            $mediaMapper = $this->getContainerEntry(PostMediaMapper::class);
            $rawMap = $mediaMapper->getMediaByPostIds($mediaModel);
            foreach ($rawMap as $pid => $mediaList) {
                $mediaMap[$pid] = array_map(
                    fn(PostMediaModel $m) => $m->getRespPostMedia(),
                    $mediaList
                );
            }
        }

        $fanpageIds = [];
        $accountIds = [];
        $profileIds = [];
        foreach ($items as $p) {
            /** @var PostModel $p */
            if ($p->getFanpageId()) {
                $fanpageIds[] = $p->getFanpageId();
            }
            if ($p->getFacebookAccountId()) {
                $accountIds[] = $p->getFacebookAccountId();
            }
            if ($p->getBrowserProfileId()) {
                $profileIds[] = $p->getBrowserProfileId();
            }
        }
        $fanpageNameMap = [];
        $accountInfoMap = [];
        $profileInfoMap = [];

        if ($fanpageIds) {
            $fanpageNameMap = $this->getContainerEntry(FanpageMapper::class)->getNameMapByIds($fanpageIds);
        }
        if ($accountIds) {
            $accountInfoMap = $this->getContainerEntry(FacebookAccountMapper::class)->getInfoMapByIds($accountIds);
        }
        if ($profileIds) {
            $profileInfoMap = $this->getContainerEntry(BrowserProfileMapper::class)->getInfoMapByIds($profileIds);
        }

        foreach ($items as $row) {
            /** @var PostModel $row */
            if (isset($mediaMap[$row->getId()])) {
                $row->addOption('media', $mediaMap[$row->getId()]);
            }
            if ($row->getFanpageId() && isset($fanpageNameMap[$row->getFanpageId()])) {
                $row->setFanpageName($fanpageNameMap[$row->getFanpageId()]);
            }
            if ($row->getFacebookAccountId() && isset($accountInfoMap[$row->getFacebookAccountId()])) {
                $row->setFacebookAccountName($accountInfoMap[$row->getFacebookAccountId()]['displayName'] ?? null);
            }
            if ($row->getBrowserProfileId() && isset($profileInfoMap[$row->getBrowserProfileId()])) {
                $row->setBrowserProfileName($profileInfoMap[$row->getBrowserProfileId()]['name'] ?? null);
            }
        }

        return $items;
    }

    /**
     * Lấy 1 bài viết theo id (+ scope userId nếu có). Trả false nếu không tìm thấy.
     */
    public function getPost(PostModel $item)
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['p' => PostMapper::TABLE_NAME]);
        if ($item->getId()) {
            $select->where(['p.id' => $item->getId()]);
        }
        if ($item->getUserId()) {
            $select->where(['p.createdById' => $item->getUserId()]);
        }
        if ($item->getStatus() !== null) {
            $select->where(['p.status' => $item->getStatus()]);
        }
        $select->limit(1);

        $row = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );
        if (! $row->count()) {
            return false;
        }

        $this->exchangeRowInto($item, (array)$row->current());
        $this->getInforPost([$item]);
        return $item;
    }

    // -------------------------------------------------------------------------
    // Write

    /**
     * Tạo hoặc cập nhật bài viết.
     * - id = null: INSERT (gắn createdById = userId hiện tại, createdAt).
     * - id có giá trị: UPDATE (gắn modifiedById/modifiedAt).
     * - Nếu truyền $mediaList (mảng) thì ghi đè toàn bộ post_media.
     */
    public function savePost(PostModel $item, ?array $mediaList = null): PostModel
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $data = [
            'title'            => $item->getTitle(),
            'content'          => $item->getContent(),
            'contentType'      => $item->getContentType() ?? PostConst::CONTENT_TYPE_TEXT,
            'targetType'       => $item->getTargetType() ?? PostConst::TARGET_FANPAGE,
            'fanpageId'        => $item->getFanpageId(),
            'facebookAccountId' => $item->getFacebookAccountId(),
            'browserProfileId' => $item->getBrowserProfileId(),
            'aiAgentId'        => $item->getAiAgentId(),
            'status'           => $item->getStatus() ?? PostConst::STATUS_DRAFT,
            'channel'          => $item->getChannel() ?? PostConst::CHANNEL_GRAPH_API,
            'scheduledAt'      => $item->getScheduledAt(),
            'publishedAt'      => $item->getPublishedAt(),
            'attemptCount'     => $item->getAttemptCount() ?? 0,
            'maxAttempts'      => $item->getMaxAttempts() ?? PostConst::DEFAULT_MAX_ATTEMPTS,
            'repeatRule'       => $item->getRepeatRule(),
            'fbPostId'         => $item->getFbPostId(),
            'note'             => $item->getNote(),
            'options'          => $item->getExtraContent(),
        ];

        if (! $item->getId()) {
            $data['createdById'] = $item->getCreatedById() ?? $item->getUserId();
            $data['createdAt']   = DateModel::getTimeStampsCurrent();

            $insert = $dbSql->insert(PostMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbAdapter->query(
                $dbSql->buildSqlString($insert),
                $dbAdapter::QUERY_MODE_EXECUTE
            );
            $item->setId((int)$result->getGeneratedValue());
        } else {
            $data['modifiedById'] = $item->getUserId();
            $data['modifiedAt']   = DateModel::getTimeStampsCurrent();

            $update = $dbSql->update(PostMapper::TABLE_NAME);
            $update->set($data);
            $update->where(['id' => $item->getId()]);
            $dbAdapter->query(
                $dbSql->buildSqlString($update),
                $dbAdapter::QUERY_MODE_EXECUTE
            );
        }

        if ($mediaList !== null) {
            $mediaModel = new PostMediaModel();
            $mediaModel->setPostId($item->getId());
            $this->getContainerEntry(PostMediaMapper::class)
                ->replaceMediaForPost($mediaModel, $mediaList);
        }

        return $item;
    }

    /**
     * Xóa mềm bài viết (status = deleted). Media giữ lại để truy vết.
     */
    public function softDeletePost(PostModel $item): bool
    {
        return (bool)$this->updateAttrsPost($item, ['status' => PostConst::STATUS_DELETED]);
    }

    /**
     * Cập nhật một vài cột của bài viết theo id (+ scope userId).
     */
    public function updateAttrsPost(PostModel $item, array $data)
    {
        if (! $data || ! $item->getId()) {
            return null;
        }
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $update    = $dbSql->update(PostMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => (int)$item->getId()]);
        if ($item->getUserId()) {
            $update->where(['createdById = ?' => (int)$item->getUserId()]);
        }
        $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        return true;
    }

    // -------------------------------------------------------------------------
    // Worker (không scope user — chạy dưới tiến trình hệ thống)

    /**
     * Claim nguyên tử một bài để đăng: flip scheduled → processing.
     * Trả true nếu chính worker này giữ được bài (đổi đúng 1 dòng), false nếu bài
     * đã bị worker khác giữ hoặc không còn ở trạng thái scheduled.
     */
    public function claimForProcessing(int $postId): bool
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $update = $dbSql->update(PostMapper::TABLE_NAME);
        $update->set(['status' => PostConst::STATUS_PROCESSING, 'modifiedAt' => DateModel::getTimeStampsCurrent()]);
        $update->where(['id' => $postId, 'status' => PostConst::STATUS_SCHEDULED]);
        $result = $dbAdapter->query($dbSql->buildSqlString($update), $dbAdapter::QUERY_MODE_EXECUTE);
        return $result->getAffectedRows() === 1;
    }

    /** Các bài scheduled quá hạn (scheduledAt < $deadline) — dùng cho expireStaleJobs. */
    public function findStaleScheduled(string $deadline): array
    {
        $dbSql     = $this->getDbSql();
        $dbAdapter = $this->getDbAdapter();

        $select = $dbSql->select(['p' => PostMapper::TABLE_NAME]);
        $select->where(['p.status' => PostConst::STATUS_SCHEDULED]);
        $select->where(['p.scheduledAt < ?' => $deadline]);
        $select->limit(200);

        $rows = $dbAdapter->query($dbSql->buildSqlString($select), $dbAdapter::QUERY_MODE_EXECUTE);
        $items = [];
        foreach ($rows->toArray() as $row) {
            $items[] = $this->hydrateModel($row);
        }
        return $items;
    }

    // -------------------------------------------------------------------------
    // Thống kê (Dashboard + KPI Lịch đăng / Bài viết) — scope theo userId

    /**
     * Đếm số bài viết theo từng status trong khoảng thời gian (theo scheduledAt).
     * Trả về [status => count].
     */
    public function countPostsByStatus(PostModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['p' => PostMapper::TABLE_NAME]);
        $select->columns([
            'status',
            'total' => new Expression('COUNT(p.id)'),
        ]);
        $select->where(['p.status != ?' => PostConst::STATUS_DELETED]);
        if ($item->getUserId()) {
            $select->where(['p.createdById' => $item->getUserId()]);
        }
        if ($item->getFromDate()) {
            $select->where(['p.scheduledAt >= ?' => $item->getFromDate() . ' 00:00:00']);
        }
        if ($item->getToDate()) {
            $select->where(['p.scheduledAt <= ?' => $item->getToDate() . ' 23:59:59']);
        }
        if ($item->getFanpageId()) {
            $select->where(['p.fanpageId' => $item->getFanpageId()]);
        }
        if ($item->getBrowserProfileId()) {
            $select->where(['p.browserProfileId' => $item->getBrowserProfileId()]);
        }
        $select->group('p.status');

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        $result = [];
        foreach ($rows->toArray() as $row) {
            $result[(int)$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    /**
     * Dữ liệu biểu đồ cột chồng theo ngày.
     * Trả về [ 'YYYY-mm-dd' => [status => count] ].
     */
    public function countPostsByDateAndStatus(PostModel $item): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['p' => PostMapper::TABLE_NAME]);
        $select->columns([
            'day'    => new Expression('DATE(p.scheduledAt)'),
            'status' => 'status',
            'total'  => new Expression('COUNT(p.id)'),
        ]);
        $select->where(['p.status != ?' => PostConst::STATUS_DELETED]);
        if ($item->getUserId()) {
            $select->where(['p.createdById' => $item->getUserId()]);
        }
        if ($item->getFromDate()) {
            $select->where(['p.scheduledAt >= ?' => $item->getFromDate() . ' 00:00:00']);
        }
        if ($item->getToDate()) {
            $select->where(['p.scheduledAt <= ?' => $item->getToDate() . ' 23:59:59']);
        }
        $select->group([new Expression('DATE(p.scheduledAt)'), 'p.status']);
        $select->order(['day ASC']);

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        $result = [];
        foreach ($rows->toArray() as $row) {
            $day = (string)$row['day'];
            $result[$day][(int)$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    /**
     * Bài viết gần đây (order by createdAt desc), giới hạn $limit.
     */
    public function getRecentPosts(PostModel $item, int $limit = 10): array
    {
        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['p' => PostMapper::TABLE_NAME]);
        $select->where(['p.status != ?' => PostConst::STATUS_DELETED]);
        if ($item->getUserId()) {
            $select->where(['p.createdById' => $item->getUserId()]);
        }
        $select->order(['p.createdAt DESC', 'p.id DESC']);
        $select->limit($limit);

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        $items = [];
        foreach ($rows->toArray() as $row) {
            $items[] = $this->hydrateModel($row);
        }
        return $this->getInforPost($items);
    }
}
