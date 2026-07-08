<?php
declare(strict_types=1);

namespace User;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'user_auth' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/user/auth[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'User\Controller',
                        'controller'    => Controller\AuthController::class,
                        'action'        => 'me',
                    ],
                ],
            ],
            'user_profile' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/user/profile[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'User\Controller',
                        'controller'    => Controller\ProfileController::class,
                        'action'        => 'update',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\AuthController::class    => AppInvokableFactory::class,
            Controller\ProfileController::class => AppInvokableFactory::class,
        ],
    ],

    'controller_plugins' => [],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
