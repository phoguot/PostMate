<?php

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Model\JsonResponse;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Facebook\Service\FanpageService;
use Posting\Filter\Post\PostDeleteFilter;
use Posting\Filter\Post\PostDuplicateFilter;
use Posting\Filter\Post\PostListFilter;
use Posting\Filter\Post\PostSaveFilter;
use Posting\Filter\Post\PostStatusFilter;
use Posting\Model\Post\PostConst;
use Posting\Model\Post\PostMapper;
use Posting\Model\Post\PostMediaMapper;
use Posting\Model\Post\PostMediaModel;
use Posting\Model\Post\PostModel;
use User\Service\UserService;

/**
 * Service luồng bài viết (Tạo bài viết + Lịch đăng + Bài viết).
 *
 * - Dữ liệu thuộc về user đăng nhập: mọi thao tác scope theo createdById.
 * - Ánh xạ tài liệu: saveDraft / schedulePost / publishNow (PostComposerService);
 *   postList / postStats / postDetail / duplicatePost / deletePost (Schedule + Published).
 * - Các bước browser/queue/graph-api (validatePostability đầy đủ, enqueueJob, spintax…)
 *   phụ thuộc module chưa dựng nên để ở dạng hook.
 */
class PostService extends AppServiceFactory
{
    /** Lấy id user đang đăng nhập. */
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    // =========================================================================
    // LIST + STATS

    public function postList(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new PostListFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $formData = $filter->getData();
        $userId   = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $page     = ! empty($formData['page']) ? (int)$formData['page'] : 1;
        $pageSize = ! empty($formData['pageSize']) ? (int)$formData['pageSize'] : 30;

        $model = new PostModel();
        $model->setUserId($userId);
        $model->setId(! empty($formData['id']) ? (int)$formData['id'] : null);
        $model->setStatus(isset($formData['status']) && $formData['status'] !== '' ? (int)$formData['status'] : null);
        $model->setStatuses($formData['statuses'] ?? []);
        $model->setFanpageId(! empty($formData['fanpageId']) ? (int)$formData['fanpageId'] : null);
        $model->setBrowserProfileId(! empty($formData['browserProfileId']) ? (int)$formData['browserProfileId'] : null);
        $model->setChannel(! empty($formData['channel']) ? (int)$formData['channel'] : null);
        $model->setFromDate($formData['fromDate'] ?? null);
        $model->setToDate($formData['toDate'] ?? null);
        $model->setOptions([
            'keyword' => $formData['keyword'] ?? null,
            'sort'    => $formData['sort'] ?? 'id',
            'dir'     => $formData['dir'] ?? 'desc',
        ]);

        $mapper = $this->getContainerEntry(PostMapper::class);
        $search = $mapper->searchPost($model, $page, $pageSize);

        $result = array_map(fn(PostModel $item) => $item->getRespPost(), $search['items']);
        $total  = (int)$search['total'];

        return $apiResult->successResponse([
            'result'    => $result,
            'paginator' => [
                'page'       => $page,
                'pageSize'   => $pageSize,
                'totalItems' => $total,
                'totalPages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 0,
            ],
        ]);
    }

    /**
     * KPI theo trạng thái (dùng cho màn Lịch đăng / Bài viết).
     */
    public function postStats(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $model = new PostModel();
        $model->setUserId($userId);
        $model->setFromDate($payload['fromDate'] ?? null);
        $model->setToDate($payload['toDate'] ?? null);
        $model->setFanpageId(! empty($payload['fanpageId']) ? (int)$payload['fanpageId'] : null);
        $model->setBrowserProfileId(! empty($payload['browserProfileId']) ? (int)$payload['browserProfileId'] : null);

        $mapper = $this->getContainerEntry(PostMapper::class);
        $counts = $mapper->countPostsByStatus($model);

        return $apiResult->successResponse($this->buildStats($counts));
    }

    private function buildStats(array $counts): array
    {
        $get = fn(int $s) => (int)($counts[$s] ?? 0);
        return [
            'total'      => array_sum($counts),
            'draft'      => $get(PostConst::STATUS_DRAFT),
            'scheduled'  => $get(PostConst::STATUS_SCHEDULED),
            'processing' => $get(PostConst::STATUS_PROCESSING),
            'published'  => $get(PostConst::STATUS_PUBLISHED),
            'failed'     => $get(PostConst::STATUS_FAILED),
            'expired'    => $get(PostConst::STATUS_EXPIRED),
        ];
    }

    // =========================================================================
    // DETAIL

    public function postDetail(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $id        = (int)($payload['id'] ?? 0);
        $userId    = $this->currentUserId();

        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }
        if (! $id) {
            return $apiResult->errorInvalidFormResponse([AppMessage::INVALID_DATA]);
        }

        $model = new PostModel();
        $model->setId($id);
        $model->setUserId($userId);

        $mapper = $this->getContainerEntry(PostMapper::class);
        if (! $mapper->getPost($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        // TODO(LichDang/BaiViet): bổ sung timeline (post_execution_logs) và
        //   metrics (post_metrics) khi các module đó được dựng.

        return $apiResult->successResponse($model->getRespPost());
    }

    // =========================================================================
    // WRITE — Lưu nháp / Lên lịch / Đăng ngay

    /** Lưu nháp: status = draft, không cần scheduledAt. */
    public function saveDraft(array $payload = []): JsonResponse
    {
        return $this->persistPosts($payload, PostConst::STATUS_DRAFT, false);
    }

    /** Lên lịch: status = scheduled, bắt buộc scheduledAt > now. */
    public function schedulePost(array $payload = []): JsonResponse
    {
        return $this->persistPosts($payload, PostConst::STATUS_SCHEDULED, true);
    }

    /** Đăng ngay: như schedule nhưng scheduledAt = now (job chạy ngay). */
    public function publishNow(array $payload = []): JsonResponse
    {
        $payload['scheduledAt'] = DateModel::getCurrentDateTime();
        return $this->persistPosts($payload, PostConst::STATUS_SCHEDULED, false);
    }

    /**
     * Chuẩn hóa + validate + ghi bài viết cho từng fanpage đã chọn.
     * - $requireFutureSchedule = true → bắt scheduledAt phải ở tương lai.
     */
    private function persistPosts(array $payload, int $status, bool $requireFutureSchedule): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new PostSaveFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $id          = ! empty($formData['id']) ? (int)$formData['id'] : null;
        $contentType = ! empty($formData['contentType']) ? (int)$formData['contentType'] : PostConst::CONTENT_TYPE_TEXT;
        if (! in_array($contentType, PostConst::getAllowedContentTypes(), true)) {
            return $apiResult->errorInvalidFormResponse(['contentType' => AppMessage::INVALID_DATA]);
        }

        $media = $this->normalizeMedia($formData['media'] ?? []);

        $contentError = $this->validateContent($contentType, (string)($formData['content'] ?? ''), $media);
        if ($contentError !== null) {
            return $apiResult->errorInvalidFormResponse($contentError);
        }

        $scheduledAt = ! empty($formData['scheduledAt']) ? (string)$formData['scheduledAt'] : null;
        if ($requireFutureSchedule) {
            if (! $scheduledAt) {
                return $apiResult->errorInvalidFormResponse(['scheduledAt' => AppMessage::VALIDATOR_REQUIRED]);
            }
            if (strtotime($scheduledAt) <= time()) {
                return $apiResult->errorInvalidFormResponse(['scheduledAt' => AppMessage::HOUR_SELECT_INVALID]);
            }
        }

        $fanpageIds = $formData['fanpageIds'] ?? [];
        if (empty($fanpageIds) && ! empty($formData['fanpageId'])) {
            $fanpageIds = [(int)$formData['fanpageId']];
        }

        $mapper = $this->getContainerEntry(PostMapper::class);

        // Cập nhật một bài đã tồn tại (single)
        if ($id) {
            $existing = new PostModel();
            $existing->setId($id);
            $existing->setUserId($userId);
            if (! $mapper->getPost($existing)) {
                return $apiResult->errorData404Response([AppMessage::NO_DATA]);
            }
            $fanpageId = ! empty($fanpageIds) ? (int)$fanpageIds[0] : $existing->getFanpageId();
            $saved = $this->buildAndSavePost($mapper, $formData, $userId, $status, $contentType, $scheduledAt, $fanpageId, $media, $id);
            return $apiResult->successResponse(['ids' => [$saved->getId()]], [AppMessage::UPDATE_SUCCESSFULLY]);
        }

        // Tạo mới: mỗi fanpage một bài
        if (empty($fanpageIds)) {
            if ($status === PostConst::STATUS_SCHEDULED) {
                return $apiResult->errorInvalidFormResponse(['fanpageIds' => AppMessage::VALIDATOR_REQUIRED]);
            }
            $fanpageIds = [null];
        }

        $savedIds = [];
        foreach ($fanpageIds as $fanpageId) {
            $fanpageId = $fanpageId !== null ? (int)$fanpageId : null;
            $channel   = $this->resolveChannel($fanpageId, $formData);

            if ($status === PostConst::STATUS_SCHEDULED && $fanpageId) {
                $check = $this->validatePostability($userId, $fanpageId, $formData);
                if (! $check['canPost']) {
                    continue; // fail page nào bỏ page đó, tiếp tục page khác
                }
            }

            $saved = $this->buildAndSavePost($mapper, $formData, $userId, $status, $contentType, $scheduledAt, $fanpageId, $media, null, $channel);
            $savedIds[] = $saved->getId();

            if ($status === PostConst::STATUS_SCHEDULED) {
                $this->enqueueJob($saved, $scheduledAt);
            }
        }

        if (empty($savedIds)) {
            return $apiResult->errorResponse([AppMessage::INVALID_DATA]);
        }

        return $apiResult->successResponse(['ids' => $savedIds], [AppMessage::ADD_SUCCESSFULLY]);
    }

    private function buildAndSavePost(
        PostMapper $mapper,
        array $formData,
        int $userId,
        int $status,
        int $contentType,
        ?string $scheduledAt,
        ?int $fanpageId,
        array $media,
        ?int $id = null,
        ?int $channel = null
    ): PostModel {
        $model = new PostModel();
        $model->exchangeArray($formData);
        $model->setId($id);
        $model->setUserId($userId);
        $model->setCreatedById($userId);
        $model->setStatus($status);
        $model->setContentType($contentType);
        $model->setFanpageId($fanpageId);
        $model->setScheduledAt($scheduledAt);
        if ($channel !== null) {
            $model->setChannel($channel);
        }
        $model->setAttemptCount(0);
        $model->setMaxAttempts(
            ! empty($formData['maxAttempts']) ? (int)$formData['maxAttempts'] : PostConst::DEFAULT_MAX_ATTEMPTS
        );

        $model->setAutoShortenLink(! empty($formData['autoShortenLink']));
        $model->setDisableCommentNotif(! empty($formData['disableCommentNotif']));
        $model->setAutoShare(! empty($formData['autoShare']));
        $model->setExtraContent($model->getExtraFieldsArray());

        return $mapper->savePost($model, $media);
    }

    // =========================================================================
    // DUPLICATE

    public function duplicatePost(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new PostDuplicateFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(PostMapper::class);
        $source = new PostModel();
        $source->setId((int)$filter->getData()['id']);
        $source->setUserId($userId);
        if (! $mapper->getPost($source)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }

        $clone = new PostModel();
        $clone->setUserId($userId);
        $clone->setCreatedById($userId);
        $clone->setTitle($source->getTitle());
        $clone->setContent($source->getContent());
        $clone->setContentType($source->getContentType());
        $clone->setFanpageId($source->getFanpageId());
        $clone->setBrowserProfileId($source->getBrowserProfileId());
        $clone->setChannel($source->getChannel());
        $clone->setNote($source->getNote());
        $clone->setRepeatRule($source->getRepeatRule());
        $clone->setStatus(PostConst::STATUS_DRAFT);
        $clone->setAttemptCount(0);
        $clone->setMaxAttempts($source->getMaxAttempts() ?? PostConst::DEFAULT_MAX_ATTEMPTS);
        $clone->setExtraContent($source->getExtraFieldsArray());

        $media = $this->loadSourceMedia($source->getId());
        $saved = $mapper->savePost($clone, $media);

        return $apiResult->successResponse(['id' => $saved->getId()], [AppMessage::ADD_SUCCESSFULLY]);
    }

    // =========================================================================
    // UPDATE STATUS + DELETE

    public function changeStatusPost(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new PostStatusFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $formData = $filter->getData();
        $userId   = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $status = (int)$formData['status'];
        if (! in_array($status, PostConst::getWritableStatuses(), true)) {
            return $apiResult->errorInvalidFormResponse(['status' => AppMessage::STATUS_INVALID]);
        }

        $mapper = $this->getContainerEntry(PostMapper::class);
        $model  = new PostModel();
        $model->setId((int)$formData['id']);
        $model->setUserId($userId);
        if (! $mapper->getPost($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }
        if ($model->getStatus() === PostConst::STATUS_PROCESSING) {
            return $apiResult->errorResponse(['Bài viết đang được xử lý, không thể đổi trạng thái']);
        }

        $mapper->updateAttrsPost($model, ['status' => $status]);
        return $apiResult->successResponse(['status' => $status], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    public function deletePost(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new PostDeleteFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $userId = $this->currentUserId();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(PostMapper::class);
        $model  = new PostModel();
        $model->setId((int)$filter->getData()['id']);
        $model->setUserId($userId);
        if (! $mapper->getPost($model)) {
            return $apiResult->errorData404Response([AppMessage::NO_DATA]);
        }
        if ($model->getStatus() === PostConst::STATUS_PROCESSING) {
            return $apiResult->errorResponse(['Bài viết đang được xử lý, không thể xóa']);
        }

        $this->cancelJob($model);
        $mapper->softDeletePost($model);

        return $apiResult->successResponse([], [AppMessage::DELETE_SUCCESSFULLY]);
    }

    // =========================================================================
    // Helpers nội bộ

    /**
     * Ràng buộc nội dung theo content_type (theo TaoBaiViet/HAM_XU_LY.md).
     * Trả null nếu hợp lệ, ngược lại trả mảng lỗi.
     */
    private function validateContent(int $contentType, string $content, array $media): ?array
    {
        $images = array_filter($media, fn($m) => (int)($m['type'] ?? 0) === PostConst::MEDIA_TYPE_IMAGE);
        $videos = array_filter($media, fn($m) => (int)($m['type'] ?? 0) === PostConst::MEDIA_TYPE_VIDEO);

        if ($content === '' && empty($media)) {
            return ['content' => AppMessage::VALIDATOR_REQUIRED];
        }
        if (! empty($images) && ! empty($videos)) {
            return ['media' => AppMessage::INVALID_DATA];
        }
        if (count($images) > PostConst::MAX_IMAGES) {
            return ['media' => AppMessage::INVALID_DATA];
        }
        if (count($videos) > PostConst::MAX_VIDEOS) {
            return ['media' => AppMessage::INVALID_DATA];
        }
        return null;
    }

    private function normalizeMedia($media): array
    {
        if (is_string($media)) {
            $decoded = json_decode($media, true);
            $media = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($media)) {
            return [];
        }

        $result = [];
        foreach ($media as $m) {
            if (! is_array($m) || empty($m['url'])) {
                continue;
            }
            $result[] = [
                'type'        => ! empty($m['type']) ? (int)$m['type'] : PostConst::MEDIA_TYPE_IMAGE,
                'url'         => (string)$m['url'],
                'storagePath' => $m['storagePath'] ?? null,
            ];
        }
        return $result;
    }

    private function loadSourceMedia(int $postId): array
    {
        $mediaModel = new PostMediaModel();
        $mediaModel->setPostId($postId);
        $rows = $this->getContainerEntry(PostMediaMapper::class)->getByPostId($mediaModel);

        $media = [];
        foreach ($rows as $m) {
            /** @var PostMediaModel $m */
            $media[] = [
                'type'        => $m->getType(),
                'url'         => $m->getUrl(),
                'storagePath' => $m->getStoragePath(),
            ];
        }
        return $media;
    }

    /**
     * Xác định kênh đăng theo fanpage.apiEnabled (API-first, xem Fanpage/NGHIEP_VU.md mục 1.3).
     * Cho phép ghi đè thủ công qua formData['channel'] nếu người dùng chọn tay.
     */
    private function resolveChannel(?int $fanpageId, array $formData): int
    {
        if (! empty($formData['channel'])) {
            return (int)$formData['channel'];
        }
        if ($fanpageId) {
            $fanpage = new FanpageModel();
            $fanpage->setId($fanpageId);
            if ($this->getContainerEntry(FanpageMapper::class)->getFanpage($fanpage) && $fanpage->getApiEnabled()) {
                return PostConst::CHANNEL_GRAPH_API;
            }
            return PostConst::CHANNEL_BROWSER;
        }
        return PostConst::CHANNEL_GRAPH_API;
    }

    /**
     * Kiểm tra khả năng đăng của fanpage trước khi lên lịch — ủy quyền FanpageService::computeCanPost()
     * (Fanpage/HAM_XU_LY.md).
     */
    private function validatePostability(int $userId, int $fanpageId, array $formData): array
    {
        $fanpage = new FanpageModel();
        $fanpage->setId($fanpageId);
        $fanpage->setUserId($userId);
        if (! $this->getContainerEntry(FanpageMapper::class)->getFanpage($fanpage)) {
            return ['canPost' => false, 'reason' => AppMessage::NO_DATA];
        }
        return $this->getContainerEntry(FanpageService::class)->computeCanPost($fanpage);
    }

    /** Hook: đẩy job vào hàng đợi (QueueService — module LichDang). */
    private function enqueueJob(PostModel $post, ?string $runAt): void
    {
    }

    /** Hook: hủy job khỏi hàng đợi (QueueService — module LichDang). */
    private function cancelJob(PostModel $post): void
    {
    }
}
