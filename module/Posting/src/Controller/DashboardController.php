<?php
namespace Posting\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Posting\Service\DashboardService;

/**
 * Controller màn Dashboard (homepage.png). Dữ liệu scope theo user đăng nhập.
 */
class DashboardController extends AppController
{
    /** 5 thẻ KPI + so sánh kỳ trước. */
    public function overviewAction(): JsonResponse
    {
        $service = $this->getContainerEntry(DashboardService::class);
        return $service->getOverviewStats($this->getPostParamsApi());
    }

    /** Biểu đồ hiệu suất đăng bài (cột chồng theo ngày). */
    public function performanceAction(): JsonResponse
    {
        $service = $this->getContainerEntry(DashboardService::class);
        return $service->getPostPerformanceChart($this->getPostParamsApi());
    }

    /** Tỷ lệ trạng thái (donut chart). */
    public function distributionAction(): JsonResponse
    {
        $service = $this->getContainerEntry(DashboardService::class);
        return $service->getStatusDistribution($this->getPostParamsApi());
    }

    /** Bài viết gần đây. */
    public function recentAction(): JsonResponse
    {
        $service = $this->getContainerEntry(DashboardService::class);
        return $service->getRecentPosts($this->getPostParamsApi());
    }

    /** Trạng thái sức khỏe hệ thống. */
    public function healthAction(): JsonResponse
    {
        $service = $this->getContainerEntry(DashboardService::class);
        return $service->getSystemHealth($this->getPostParamsApi());
    }
}
