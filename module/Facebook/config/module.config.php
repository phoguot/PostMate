<?php
declare(strict_types=1);

namespace Facebook;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'facebook_account' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/facebook/account[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Facebook\Controller',
                        'controller'    => Controller\FacebookAccountController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
            'facebook_fanpage' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/facebook/fanpage[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Facebook\Controller',
                        'controller'    => Controller\FanpageController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
            'facebook_cookie' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/facebook/cookie[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Facebook\Controller',
                        'controller'    => Controller\CookieController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\FacebookAccountController::class => AppInvokableFactory::class,
            Controller\FanpageController::class          => AppInvokableFactory::class,
            Controller\CookieController::class           => AppInvokableFactory::class,
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
