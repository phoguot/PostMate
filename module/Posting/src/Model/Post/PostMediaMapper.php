<?php

declare(strict_types=1);

namespace Posting\Model\Post;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Laminas\Db\Adapter\Driver\Pdo\Result;

/**
 * Mapper bảng post_media.
 * - Media thuộc về post (scope theo postId); quyền sở hữu do PostMapper/PostService kiểm.
 * - Chiến lược ghi: thay thế toàn bộ media của một post (delete + insert theo orderIndex).
 */
class PostMediaMapper extends AppMapper
{
    public const TABLE_NAME = 'post_media';

    /**
     * Ghi đè toàn bộ media cho một post.
     * $mediaList: [ ['type' => int, 'url' => string, 'storagePath' => ?string], ... ]
     * (orderIndex tự sinh theo thứ tự phần tử)
     */
    public function replaceMediaForPost(PostMediaModel $item, array $mediaList): bool
    {
        $postId = (int)$item->getPostId();
        if (! $postId) {
            return false;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();

        // Xóa media cũ
        $this->deleteByPostId($item);

        // Insert media mới theo thứ tự
        $orderIndex = 0;
        foreach ($mediaList as $media) {
            $type = ! empty($media['type']) ? (int)$media['type'] : PostConst::MEDIA_TYPE_IMAGE;
            $url  = ! empty($media['url']) ? (string)$media['url'] : null;
            if (! $url) {
                continue;
            }

            $insert = $dbSql->insert(PostMediaMapper::TABLE_NAME);
            $insert->values([
                'postId'      => $postId,
                'type'        => $type,
                'url'         => $url,
                'storagePath' => $media['storagePath'] ?? null,
                'orderIndex'  => $orderIndex,
                'createdAt'   => DateModel::getTimeStampsCurrent(),
            ]);

            /** @var Result $result */
            $dbAdapter->query(
                $dbSql->buildSqlString($insert),
                $dbAdapter::QUERY_MODE_EXECUTE
            );
            $orderIndex++;
        }

        return true;
    }

    public function deleteByPostId(PostMediaModel $item): bool
    {
        if (! $item->getPostId()) {
            return false;
        }

        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $delete    = $dbSql->delete(PostMediaMapper::TABLE_NAME);
        $delete->where(['postId' => (int)$item->getPostId()]);

        $dbAdapter->query(
            $dbSql->buildSqlString($delete),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        return true;
    }

    /**
     * Lấy media của 1 post, sắp theo orderIndex.
     */
    public function getByPostId(PostMediaModel $item): array
    {
        if (! $item->getPostId()) {
            return [];
        }

        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['pm' => PostMediaMapper::TABLE_NAME]);
        $select->where(['pm.postId = ?' => (int)$item->getPostId()]);
        $select->order(['pm.orderIndex ASC', 'pm.id ASC']);

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        $results = [];
        foreach ($rows->toArray() as $row) {
            $model = new PostMediaModel();
            $model->exchangeArray($row);
            $results[] = $model;
        }

        return $results;
    }

    /**
     * Lấy media của nhiều post cùng lúc (dùng khi list bài viết).
     * Trả về [postId => [PostMediaModel, ...]]
     */
    public function getMediaByPostIds(PostMediaModel $item): array
    {
        $postIds = $item->getPostIds();
        if (empty($postIds)) {
            return [];
        }

        $dbAdapter = $this->getDbAdapter();
        $dbSql     = $this->getDbSql();
        $select    = $dbSql->select(['pm' => PostMediaMapper::TABLE_NAME]);
        $select->where(['pm.postId' => array_map('intval', $postIds)]);
        $select->order(['pm.postId ASC', 'pm.orderIndex ASC', 'pm.id ASC']);

        $rows = $dbAdapter->query(
            $dbSql->buildSqlString($select),
            $dbAdapter::QUERY_MODE_EXECUTE
        );

        $map = [];
        foreach ($rows->toArray() as $row) {
            $model = new PostMediaModel();
            $model->exchangeArray($row);
            $map[(int)$row['postId']][] = $model;
        }

        return $map;
    }
}
