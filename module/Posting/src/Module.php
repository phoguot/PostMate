<?php
declare(strict_types=1);

namespace Posting;

use Application\Factory\AppInvokableFactory;
use Posting\Model\Job\JobMapper;
use Posting\Model\Log\ExecutionLogMapper;
use Posting\Model\Post\PostMapper;
use Posting\Model\Post\PostMediaMapper;
use Posting\Model\Post\PostMediaModel;
use Posting\Model\Post\PostModel;
use Posting\Service\BrowserAgentClient;
use Posting\Service\CronService;
use Posting\Service\DashboardService;
use Posting\Service\GraphPublisher;
use Posting\Service\PostExecutor;
use Posting\Service\PostService;
use Posting\Service\QueueService;

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
                JobMapper::class => AppInvokableFactory::class,
                ExecutionLogMapper::class => AppInvokableFactory::class,
                PostService::class     => AppInvokableFactory::class,
                CronService::class     => AppInvokableFactory::class,
                DashboardService::class => AppInvokableFactory::class,
                QueueService::class => AppInvokableFactory::class,
                PostExecutor::class => AppInvokableFactory::class,
                BrowserAgentClient::class => AppInvokableFactory::class,
                GraphPublisher::class => AppInvokableFactory::class,
            ],
        ];
    }
}
