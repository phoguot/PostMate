<?php
namespace Posting\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Posting\Service\PostService;

/**
 * Controller luồng bài viết (Tạo bài viết / Lịch đăng / Bài viết).
 * - Mỏng: chỉ lấy service từ container và forward payload API.
 * - Dữ liệu scope theo user đăng nhập (service tự lấy identity).
 */
class PostController extends AppController
{
    /**
     * DANH SÁCH BÀI VIẾT (Lịch đăng / Bài viết)
     * - Lọc theo status/fanpage/trình duyệt/khoảng ngày/keyword, phân trang cursor.
     */
    public function indexAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->postList($this->getPostParamsApi());
    }

    /** KPI theo trạng thái (Tổng / Đã đăng / Chờ đăng / Đã lỗi / ...). */
    public function statsAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->postStats($this->getPostParamsApi());
    }

    /** Chi tiết 1 bài viết (kèm media; timeline/metrics bổ sung sau). */
    public function detailAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->postDetail($this->getPostParamsApi());
    }

    /** Lưu nháp. */
    public function savedraftAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->saveDraft($this->getPostParamsApi());
    }

    /** Lên lịch đăng. */
    public function scheduleAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->schedulePost($this->getPostParamsApi());
    }

    /** Đăng ngay. */
    public function publishnowAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->publishNow($this->getPostParamsApi());
    }

    /** Nhân bản bài viết → tạo bản nháp mới. */
    public function duplicateAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->duplicatePost($this->getPostParamsApi());
    }

    /** Đổi trạng thái (VD hủy lịch về nháp). */
    public function updatestatusAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->changeStatusPost($this->getPostParamsApi());
    }

    /** Xóa bài viết (soft-delete). */
    public function deleteAction(): JsonResponse
    {
        $service = $this->getContainerEntry(PostService::class);
        return $service->deletePost($this->getPostParamsApi());
    }
}
