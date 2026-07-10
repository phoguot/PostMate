<?php
declare(strict_types=1);

namespace Facebook;

use Application\Factory\AppInvokableFactory;
use Facebook\Model\Cookie\CookieMapper;
use Facebook\Model\Cookie\CookieModel;
use Facebook\Model\FacebookAccount\FacebookAccountMapper;
use Facebook\Model\FacebookAccount\FacebookAccountModel;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Facebook\Service\CookieService;
use Facebook\Service\FacebookAccountService;
use Facebook\Service\FanpageService;
use Facebook\Service\GraphApiClient;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                FacebookAccountModel::class => FacebookAccountModel::class,
                FanpageModel::class         => FanpageModel::class,
                CookieModel::class          => CookieModel::class,
            ],
            'factories'  => [
                FacebookAccountMapper::class  => AppInvokableFactory::class,
                FanpageMapper::class          => AppInvokableFactory::class,
                CookieMapper::class           => AppInvokableFactory::class,
                FacebookAccountService::class => AppInvokableFactory::class,
                FanpageService::class         => AppInvokableFactory::class,
                CookieService::class          => AppInvokableFactory::class,
                GraphApiClient::class         => AppInvokableFactory::class,
            ],
        ];
    }
}
