<?php
declare(strict_types=1);

namespace Posting;

use Application\Factory\AppInvokableFactory;
use Posting\Model\Post\PostMapper;
use Posting\Model\Post\PostMediaMapper;
use Posting\Model\Post\PostMediaModel;
use Posting\Model\Post\PostModel;
use Posting\Service\DashboardService;
use Posting\Service\PostService;

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
                PostModel::class      => PostModel::class,
                PostMediaModel::class => PostMediaModel::class,
            ],
            'factories'  => [
                PostMapper::class      => AppInvokableFactory::class,
                PostMediaMapper::class => AppInvokableFactory::class,
                PostService::class     => AppInvokableFactory::class,
                DashboardService::class => AppInvokableFactory::class,
            ],
        ];
    }
}
