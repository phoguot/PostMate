<?php
declare(strict_types=1);

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\JsonResponse;

/**
 * Service cho endpoint cron HTTP. Endpoint này chỉ nhận secret riêng, không dùng session.
 */
class CronService extends AppServiceFactory
{
    public function runPostingCron(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();
        $expectedSecret = $this->configuredSecret();
        $providedSecret = (string)($payload['secret'] ?? '');

        if ($expectedSecret === '' || $providedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
            $response = $apiResult->errorData403Response([AppMessage::COMMON_403]);
            $response->setStatusCode(403);
            return $response;
        }

        $limit = ! empty($payload['limit']) ? (int)$payload['limit'] : $this->defaultLimit();
        $queueService = $this->getContainerEntry(QueueService::class);
        $result = $queueService->drainDueJobs($limit);

        return $apiResult->successResponse($result);
    }

    private function configuredSecret(): string
    {
        $config = $this->getContainerEntry('config');
        return (string)($config['cron']['postingSecret'] ?? '');
    }

    private function defaultLimit(): int
    {
        $config = $this->getContainerEntry('config');
        return max(1, min(20, (int)($config['cron']['postingDefaultLimit'] ?? 5)));
    }
}
