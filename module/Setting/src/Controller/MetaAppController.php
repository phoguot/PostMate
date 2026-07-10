<?php
declare(strict_types=1);

namespace Setting\Controller;

use Application\Controller\AppController;
use Application\Model\JsonResponse;
use Setting\Service\MetaAppService;

/**
 * Controller Token & Quyền (Cài đặt > Facebook > Meta App).
 */
class MetaAppController extends AppController
{
    /** Trạng thái cấu hình Meta App hiện tại. */
    public function statusAction(): JsonResponse
    {
        $service = $this->getContainerEntry(MetaAppService::class);
        return $service->getMetaAppStatus($this->getPostParamsApi());
    }

    /** Kết nối Meta App (appId + appSecret). */
    public function connectAction(): JsonResponse
    {
        $service = $this->getContainerEntry(MetaAppService::class);
        return $service->connectMetaApp($this->getPostParamsApi());
    }

    /** Cấp Page Access Token cho toàn bộ fanpage sở hữu. */
    public function issuetokensAction(): JsonResponse
    {
        $service = $this->getContainerEntry(MetaAppService::class);
        return $service->issuePageTokens($this->getPostParamsApi());
    }

    /** Làm mới token. */
    public function refreshtokensAction(): JsonResponse
    {
        $service = $this->getContainerEntry(MetaAppService::class);
        return $service->refreshTokens($this->getPostParamsApi());
    }
}
