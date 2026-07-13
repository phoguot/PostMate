<?php
declare(strict_types=1);

namespace Posting;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'posting_post'      => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/posting/post[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Posting\Controller',
                        'controller'    => Controller\PostController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
            'posting_dashboard' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/posting/dashboard[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Posting\Controller',
                        'controller'    => Controller\DashboardController::class,
                        'action'        => 'overview',
                    ],
                ],
            ],
            'posting_cron'      => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/cron/posting[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Posting\Controller',
                        'controller'    => Controller\CronController::class,
                        'action'        => 'run',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\PostController::class      => AppInvokableFactory::class,
            Controller\DashboardController::class => AppInvokableFactory::class,
            Controller\CronController::class      => AppInvokableFactory::class,
        ],
    ],

    'controller_plugins' => [],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies'          => [
            'ViewJsonStrategy',
        ],
    ],
];
