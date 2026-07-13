<?php

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Model\JsonResponse;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\FacebookAccount\FacebookAccountModel;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Facebook\Service\FacebookAccountService;
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
        $model->setTargetType(! empty($formData['targetType']) ? (int)$formData['targetType'] : null);
        $model->setFanpageId(! empty($formData['fanpageId']) ? (int)$formData['fanpageId'] : null);
        $model->setFacebookAccountId(! empty($formData['facebookAccountId']) ? (int)$formData['facebookAccountId'] : null);
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

        $targetTypeProvided = ! empty($formData['targetType']);
        $targetType = $targetTypeProvided ? (int)$formData['targetType'] : PostConst::TARGET_FANPAGE;
        if (! in_array($targetType, PostConst::getAllowedTargetTypes(), true)) {
            return $apiResult->errorInvalidFormResponse(['targetType' => AppMessage::INVALID_DATA]);
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
            if ($this->isNativeScheduledPost($existing)) {
                $cancel = $this->cancelNativeGraphSchedule($existing);
                if (empty($cancel['success'])) {
                    return $apiResult->errorResponse([$cancel['error'] ?? 'Không hủy được lịch Facebook']);
                }
            }
            $this->cancelJob($existing);
            // FE không gửi lại targetType khi sửa → giữ nguyên đích của bài hiện có (tránh mất facebookAccountId/fanpageId).
            if (! $targetTypeProvided) {
                $targetType = $existing->getTargetType() ?? PostConst::TARGET_FANPAGE;
            }
            $targetIds = $this->resolveTargetIds($targetType, $formData);
            $targetId  = ! empty($targetIds)
                ? (int)$targetIds[0]
                : ($targetType === PostConst::TARGET_PROFILE ? $existing->getFacebookAccountId() : $existing->getFanpageId());
            if ($status === PostConst::STATUS_SCHEDULED && $targetId) {
                $check = $this->validatePostability($userId, $targetType, $targetId, $formData);
                if (! $check['canPost']) {
                    return $apiResult->errorResponse([$check['reason'] ?? AppMessage::INVALID_DATA]);
                }
            }

            // Khi sửa, giữ channel cũ nếu FE không gửi lại; profile vẫn luôn dùng browser.
            $channelData = $formData;
            if (empty($channelData['channel']) && $existing->getChannel()) {
                $channelData['channel'] = $existing->getChannel();
            }
            $channel = $this->resolveChannel($targetType, $targetId, $channelData);
            $saved = $this->buildAndSavePost($mapper, $formData, $userId, $status, $contentType, $scheduledAt, $targetType, $targetId, $media, $id, $channel);
            $data = ['ids' => [$saved->getId()]];
            if ($status === PostConst::STATUS_SCHEDULED) {
                $data['delivery'] = [$this->dispatchScheduledPost($saved, $media, $scheduledAt)];
            }
            return $apiResult->successResponse($data, [AppMessage::UPDATE_SUCCESSFULLY]);
        }

        // Danh sách đích đăng: fanpage (fanpageIds/fanpageId) hoặc trang cá nhân (facebookAccountIds/facebookAccountId).
        $targetIds = $this->resolveTargetIds($targetType, $formData);

        // Tạo mới: mỗi đích một bài
        if (empty($targetIds)) {
            if ($status === PostConst::STATUS_SCHEDULED) {
                $field = $targetType === PostConst::TARGET_PROFILE ? 'facebookAccountIds' : 'fanpageIds';
                return $apiResult->errorInvalidFormResponse([$field => AppMessage::VALIDATOR_REQUIRED]);
            }
            $targetIds = [null];
        }

        $savedIds = [];
        $delivery = [];
        foreach ($targetIds as $targetId) {
            $targetId = $targetId !== null ? (int)$targetId : null;
            $channel  = $this->resolveChannel($targetType, $targetId, $formData);

            if ($status === PostConst::STATUS_SCHEDULED && $targetId) {
                $check = $this->validatePostability($userId, $targetType, $targetId, $formData);
                if (! $check['canPost']) {
                    continue; // đích nào lỗi thì bỏ, tiếp tục đích khác
                }
            }

            $saved = $this->buildAndSavePost($mapper, $formData, $userId, $status, $contentType, $scheduledAt, $targetType, $targetId, $media, null, $channel);
            $savedIds[] = $saved->getId();

            if ($status === PostConst::STATUS_SCHEDULED) {
                $delivery[] = $this->dispatchScheduledPost($saved, $media, $scheduledAt);
            }
        }

        if (empty($savedIds)) {
            return $apiResult->errorResponse([AppMessage::INVALID_DATA]);
        }

        $data = ['ids' => $savedIds];
        if (! empty($delivery)) {
            $data['delivery'] = $delivery;
        }

        return $apiResult->successResponse($data, [AppMessage::ADD_SUCCESSFULLY]);
    }

    /**
     * Chuẩn hóa danh sách id đích đăng theo targetType:
     * - TARGET_PROFILE → facebookAccountIds (fallback facebookAccountId)
     * - TARGET_FANPAGE → fanpageIds (fallback fanpageId)
     */
    private function resolveTargetIds(int $targetType, array $formData): array
    {
        if ($targetType === PostConst::TARGET_PROFILE) {
            $ids = $formData['facebookAccountIds'] ?? [];
            if (empty($ids) && ! empty($formData['facebookAccountId'])) {
                $ids = [(int)$formData['facebookAccountId']];
            }
            return $ids;
        }

        $ids = $formData['fanpageIds'] ?? [];
        if (empty($ids) && ! empty($formData['fanpageId'])) {
            $ids = [(int)$formData['fanpageId']];
        }
        return $ids;
    }

    private function buildAndSavePost(
        PostMapper $mapper,
        array $formData,
        int $userId,
        int $status,
        int $contentType,
        ?string $scheduledAt,
        int $targetType,
        ?int $targetId,
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
        $model->setTargetType($targetType);
        if ($targetType === PostConst::TARGET_PROFILE) {
            $model->setFacebookAccountId($targetId);
            $model->setFanpageId(null);
        } else {
            $model->setFanpageId($targetId);
            $model->setFacebookAccountId(null);
        }
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
        $clone->setTargetType($source->getTargetType() ?? PostConst::TARGET_FANPAGE);
        $clone->setFanpageId($source->getFanpageId());
        $clone->setFacebookAccountId($source->getFacebookAccountId());
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

        if ($status !== PostConst::STATUS_SCHEDULED) {
            if ($this->isNativeScheduledPost($model)) {
                $cancel = $this->cancelNativeGraphSchedule($model);
                if (empty($cancel['success'])) {
                    return $apiResult->errorResponse([$cancel['error'] ?? 'Không hủy được lịch Facebook']);
                }
            }
            $this->cancelJob($model);
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

        if ($this->isNativeScheduledPost($model)) {
            $cancel = $this->cancelNativeGraphSchedule($model);
            if (empty($cancel['success'])) {
                return $apiResult->errorResponse([$cancel['error'] ?? 'Không hủy được lịch Facebook']);
            }
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
        $mediaMapper = $this->getContainerEntry(PostMediaMapper::class);
        $rows = $mediaMapper->getByPostId($mediaModel);

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
    private function resolveChannel(int $targetType, ?int $targetId, array $formData): int
    {
        // Trang cá nhân: Graph API không cho publish lên timeline → luôn dùng browser automation.
        if ($targetType === PostConst::TARGET_PROFILE) {
            return PostConst::CHANNEL_BROWSER;
        }
        if (! empty($formData['channel'])) {
            return (int)$formData['channel'];
        }
        if ($targetId) {
            $fanpage = new FanpageModel();
            $fanpage->setId($targetId);
            $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
            if ($fanpageMapper->getFanpage($fanpage) && $fanpage->getApiEnabled()) {
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
    private function validatePostability(int $userId, int $targetType, int $targetId, array $formData): array
    {
        if ($targetType === PostConst::TARGET_PROFILE) {
            $account = new FacebookAccountModel();
            $account->setId($targetId);
            $account->setUserId($userId);
            $accountMapper = $this->getContainerEntry(FacebookAccountMapper::class);
            if (! $accountMapper->getFacebookAccount($account)) {
                return ['canPost' => false, 'reason' => AppMessage::NO_DATA];
            }
            $accountService = $this->getContainerEntry(FacebookAccountService::class);
            return $accountService->computeCanPost($account);
        }

        $fanpage = new FanpageModel();
        $fanpage->setId($targetId);
        $fanpage->setUserId($userId);
        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        if (! $fanpageMapper->getFanpage($fanpage)) {
            return ['canPost' => false, 'reason' => AppMessage::NO_DATA];
        }
        $fanpageService = $this->getContainerEntry(FanpageService::class);
        return $fanpageService->computeCanPost($fanpage);
    }

    /** Đẩy job vào hàng đợi để worker đăng theo lịch. */
    private function enqueueJob(PostModel $post, ?string $runAt): void
    {
        if (! $post->getId() || ! $runAt) {
            return;
        }
        $queueService = $this->getContainerEntry(QueueService::class);
        $queueService->enqueueJob((int)$post->getId(), $runAt);
    }

    /**
     * Chọn cách chạy bài scheduled:
     * - Graph API fanpage đủ điều kiện: giao lịch cho Facebook tự publish.
     * - Các case còn lại: ghi post_jobs để HTTP cron/worker xử lý.
     */
    private function dispatchScheduledPost(PostModel $post, array $media, ?string $scheduledAt): array
    {
        $result = [
            'id'       => $post->getId(),
            'delivery' => 'queue',
            'fbPostId' => null,
        ];

        if ($this->canUseNativeGraphSchedule($post, $scheduledAt)) {
            $graphPublisher = $this->getContainerEntry(GraphPublisher::class);
            $graphResult = $graphPublisher->schedule($post, $media, (string)$scheduledAt);
            if (! empty($graphResult['success']) && ! empty($graphResult['fbPostId'])) {
                $fbPostId = (string)$graphResult['fbPostId'];
                $postMapper = $this->getContainerEntry(PostMapper::class);
                $postMapper->updateAttrsPost($post, ['fbPostId' => $fbPostId]);
                $post->setFbPostId($fbPostId);
                return [
                    'id'       => $post->getId(),
                    'delivery' => 'facebook_schedule',
                    'fbPostId' => $fbPostId,
                    'tracking' => 'queue',
                ];
            }
            $result['nativeScheduleError'] = $graphResult['error'] ?? 'Không lên lịch trực tiếp được qua Graph API';
        }

        $this->enqueueJob($post, $scheduledAt);
        return $result;
    }

    private function canUseNativeGraphSchedule(PostModel $post, ?string $scheduledAt): bool
    {
        if (! $scheduledAt || (int)$post->getTargetType() !== PostConst::TARGET_FANPAGE) {
            return false;
        }
        if ((int)$post->getChannel() !== PostConst::CHANNEL_GRAPH_API) {
            return false;
        }

        $timestamp = strtotime($scheduledAt);
        if ($timestamp === false) {
            return false;
        }
        $leadSec = $timestamp - time();
        return $leadSec >= 600 && $leadSec <= 6480000;
    }

    private function isNativeScheduledPost(PostModel $post): bool
    {
        return (int)$post->getStatus() === PostConst::STATUS_SCHEDULED
            && (int)$post->getChannel() === PostConst::CHANNEL_GRAPH_API
            && (string)$post->getFbPostId() !== '';
    }

    private function cancelNativeGraphSchedule(PostModel $post): array
    {
        $graphPublisher = $this->getContainerEntry(GraphPublisher::class);
        $result = $graphPublisher->delete($post);
        if (! empty($result['success'])) {
            $postMapper = $this->getContainerEntry(PostMapper::class);
            $postMapper->updateAttrsPost($post, ['fbPostId' => null]);
            $post->setFbPostId(null);
        }
        return $result;
    }

    /** Hủy job khỏi hàng đợi (khi xóa bài / sửa lịch). */
    private function cancelJob(PostModel $post): void
    {
        if (! $post->getId()) {
            return;
        }
        $queueService = $this->getContainerEntry(QueueService::class);
        $queueService->cancelJob((int)$post->getId());
    }
}
