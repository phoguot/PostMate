<?php

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Model\JsonResponse;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Cookie\CookieModel;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Infra\Model\BrowserProfile\BrowserProfileModel;
use Posting\Filter\Dashboard\DashboardStatsFilter;
use Posting\Model\Post\PostConst;
use Posting\Model\Post\PostMapper;
use Posting\Model\Post\PostModel;
use User\Service\UserService;

/**
 * Service màn Dashboard (homepage.png). Dữ liệu scope theo user đăng nhập.
 * - getActivityFeed phụ thuộc ai_agents (chưa có module riêng) nên vẫn để dạng hook.
 */
class DashboardService extends AppServiceFactory
{
    private function currentUserId(): ?int
    {
        $userService = $this->getContainerEntry(UserService::class);
        $identity = $userService ? $userService->getIdentity() : null;
        return $identity ? (int)$identity : null;
    }

    /**
     * 5 thẻ KPI + so sánh kỳ trước.
     */
    public function getOverviewStats(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $filter = new DashboardStatsFilter($this->getContainer());
        $filter->setData($payload);
        if (!$filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }

        $formData = $filter->getData();
        $userId   = $this->currentUserId();
        if (!$userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        [$fromDate, $toDate] = $this->resolveRange($formData);
        $mapper = $this->getContainerEntry(PostMapper::class);

        $current = $mapper->countPostsByStatus($this->rangeModel($userId, $fromDate, $toDate));

        // Kỳ trước: prevFrom = fromDate - (toDate - fromDate); prevTo = fromDate - 1
        $span     = strtotime($toDate) - strtotime($fromDate);
        $prevFrom = date(DateModel::COMMON_DATE_FORMAT, strtotime($fromDate) - $span - 86400);
        $prevTo   = date(DateModel::COMMON_DATE_FORMAT, strtotime($fromDate) - 86400);
        $previous = $mapper->countPostsByStatus($this->rangeModel($userId, $prevFrom, $prevTo));

        $cur       = fn(int $s) => (int)($current[$s] ?? 0);
        $totalCur  = array_sum($current);
        $totalPrev = array_sum($previous);
        $published = $cur(PostConst::STATUS_PUBLISHED);

        $data = [
            'total'         => $totalCur,
            'published'     => $published,
            'publishedRate' => $totalCur > 0 ? round($published * 100 / $totalCur, 1) : 0,
            'scheduled'     => $cur(PostConst::STATUS_SCHEDULED),
            'failed'        => $cur(PostConst::STATUS_FAILED),
            'processing'    => $cur(PostConst::STATUS_PROCESSING),
            'deltas'        => [
                'total'      => $this->delta($totalCur, $totalPrev),
                'published'  => $this->delta($published, (int)($previous[PostConst::STATUS_PUBLISHED] ?? 0)),
                'scheduled'  => $this->delta($cur(PostConst::STATUS_SCHEDULED), (int)($previous[PostConst::STATUS_SCHEDULED] ?? 0)),
                'failed'     => $this->delta($cur(PostConst::STATUS_FAILED), (int)($previous[PostConst::STATUS_FAILED] ?? 0)),
                'processing' => $this->delta($cur(PostConst::STATUS_PROCESSING), (int)($previous[PostConst::STATUS_PROCESSING] ?? 0)),
            ],
        ];

        return $apiResult->successResponse($data);
    }

    /**
     * Biểu đồ cột chồng theo ngày (published / scheduled / failed), fill ngày trống = 0.
     */
    public function getPostPerformanceChart(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (!$userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        [$fromDate, $toDate] = $this->resolveRange($payload);
        $mapper = $this->getContainerEntry(PostMapper::class);
        $byDate = $mapper->countPostsByDateAndStatus($this->rangeModel($userId, $fromDate, $toDate));

        $series = [];
        $cursor = strtotime($fromDate);
        $end    = strtotime($toDate);
        while ($cursor <= $end) {
            $day = date(DateModel::COMMON_DATE_FORMAT, $cursor);
            $row = $byDate[$day] ?? [];
            $series[] = [
                'date'      => $day,
                'published' => (int)($row[PostConst::STATUS_PUBLISHED] ?? 0),
                'pending'   => (int)($row[PostConst::STATUS_SCHEDULED] ?? 0),
                'failed'    => (int)($row[PostConst::STATUS_FAILED] ?? 0),
            ];
            $cursor += 86400;
        }

        return $apiResult->successResponse($series);
    }

    /**
     * Tỷ lệ trạng thái (donut chart).
     */
    public function getStatusDistribution(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (!$userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        [$fromDate, $toDate] = $this->resolveRange($payload);
        $mapper = $this->getContainerEntry(PostMapper::class);
        $counts = $mapper->countPostsByStatus($this->rangeModel($userId, $fromDate, $toDate));
        $total  = array_sum($counts);

        $labels = [
            PostConst::STATUS_DRAFT      => 'Nháp',
            PostConst::STATUS_SCHEDULED  => 'Chờ đăng',
            PostConst::STATUS_PROCESSING => 'Đang xử lý',
            PostConst::STATUS_PUBLISHED  => 'Đã đăng',
            PostConst::STATUS_FAILED     => 'Đã lỗi',
            PostConst::STATUS_EXPIRED    => 'Hết hạn',
        ];

        $distribution = [];
        foreach ($labels as $status => $name) {
            $count = (int)($counts[$status] ?? 0);
            $distribution[] = [
                'status'  => $status,
                'name'    => $name,
                'count'   => $count,
                'percent' => $total > 0 ? round($count * 100 / $total, 1) : 0,
            ];
        }

        return $apiResult->successResponse(['total' => $total, 'distribution' => $distribution]);
    }

    /**
     * Bảng bài viết gần đây.
     */
    public function getRecentPosts(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (!$userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $limit  = !empty($payload['limit']) ? min((int)$payload['limit'], 50) : 10;
        $mapper = $this->getContainerEntry(PostMapper::class);
        $model  = new PostModel();
        $model->setUserId($userId);
        $posts  = $mapper->getRecentPosts($model, $limit);

        $data = array_map(fn(PostModel $p) => $p->getRespPost(), $posts);
        return $apiResult->successResponse($data);
    }

    /**
     * Trạng thái sức khỏe hệ thống.
     * Hook: aiAgents thuộc module chưa dựng — vẫn để null. browsers/cookies lấy qua
     * Infra\BrowserProfileMapper / Facebook\CookieMapper.
     */
    public function getSystemHealth(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $userId    = $this->currentUserId();
        if (!$userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $mapper = $this->getContainerEntry(PostMapper::class);
        $counts = $mapper->countPostsByStatus($this->rangeModel($userId, null, null));

        $profileModel = new BrowserProfileModel();
        $profileModel->setUserId($userId);
        $browserProfileMapper = $this->getContainerEntry(BrowserProfileMapper::class);
        $browserStats = $browserProfileMapper->getStats($profileModel);

        $cookieModel = new CookieModel();
        $cookieModel->setUserId($userId);
        $cookieMapper = $this->getContainerEntry(CookieMapper::class);
        $cookieStats = $cookieMapper->getStats($cookieModel);

        $failed = (int)($counts[PostConst::STATUS_FAILED] ?? 0);
        $overall = $failed > 0 || $cookieStats['invalid'] > 0 || $browserStats['offline'] > 0 ? 'warning' : 'good';

        return $apiResult->successResponse([
            'queue'    => ['pending' => (int)($counts[PostConst::STATUS_SCHEDULED] ?? 0)],
            'aiAgents' => null,
            'browsers' => $browserStats,
            'cookies'  => $cookieStats,
            'overall'  => $overall,
        ]);
    }

    // =========================================================================
    // Helpers

    private function resolveRange(array $formData): array
    {
        $toDate   = !empty($formData['toDate']) ? (string)$formData['toDate'] : DateModel::getCurrentDate();
        $fromDate = !empty($formData['fromDate'])
            ? (string)$formData['fromDate']
            : date(DateModel::COMMON_DATE_FORMAT, strtotime($toDate) - 30 * 86400);
        return [$fromDate, $toDate];
    }

    private function rangeModel(int $userId, ?string $fromDate, ?string $toDate): PostModel
    {
        $model = new PostModel();
        $model->setUserId($userId);
        $model->setFromDate($fromDate);
        $model->setToDate($toDate);
        return $model;
    }

    /** % thay đổi so với kỳ trước; null nếu kỳ trước = 0. */
    private function delta(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }
        return round(($current - $previous) * 100 / $previous, 1);
    }
}
