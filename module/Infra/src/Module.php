<?php
declare(strict_types=1);

namespace Infra;

use Application\Factory\AppInvokableFactory;
use Infra\Model\BrowserProfile\BrowserProfileMapper;
use Infra\Model\BrowserProfile\BrowserProfileModel;
use Infra\Model\Proxy\ProxyMapper;
use Infra\Model\Proxy\ProxyModel;
use Infra\Model\Server\ServerMapper;
use Infra\Model\Server\ServerModel;
use Infra\Service\BrowserProfileService;

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
                ServerModel::class         => ServerModel::class,
                ProxyModel::class          => ProxyModel::class,
                BrowserProfileModel::class => BrowserProfileModel::class,
            ],
            'factories'  => [
                ServerMapper::class         => AppInvokableFactory::class,
                ProxyMapper::class          => AppInvokableFactory::class,
                BrowserProfileMapper::class => AppInvokableFactory::class,
                BrowserProfileService::class => AppInvokableFactory::class,
            ],
        ];
    }
}
