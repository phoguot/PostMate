<?php
declare(strict_types=1);

namespace User;

use Application\Factory\AppInvokableFactory;
use User\Model\User\UserMapper;
use User\Model\User\UserModel;
use User\Service\UserService;

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
                UserModel::class => UserModel::class,
            ],
            'factories'  => [
                UserMapper::class  => AppInvokableFactory::class,
                UserService::class => AppInvokableFactory::class,
            ],
        ];
    }
}
