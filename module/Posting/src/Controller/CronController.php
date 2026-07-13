<?php
declare(strict_types=1);

namespace Posting\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Posting\Service\CronService;

/**
 * Controller cho external cron. Không yêu cầu login; bảo vệ bằng secret cấu hình server.
 */
class CronController extends AppController
{
    public function runAction(): JsonResponse
    {
        $params = array_replace($this->getAllQueryParams(), $this->getPostParamsApi());

        $header = $this->getRequest()->getHeaders()->get('X-Cron-Secret');
        if ($header !== false) {
            $params['secret'] = (string)$header->getFieldValue();
        }

        $cronService = $this->getContainerEntry(CronService::class);
        return $cronService->runPostingCron($params);
    }
}
