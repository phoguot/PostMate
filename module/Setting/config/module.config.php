<?php
declare(strict_types=1);

namespace Setting;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            // setting_meta_app khai báo TRƯỚC setting: route '/setting[/:action]' cũng khớp
            // '/setting/meta-app' (coi meta-app là :action) nếu được thử trước — Laminas
            // TreeRouteStack thử các route theo thứ tự khai báo, nên route cụ thể hơn phải đứng trước.
            'setting_meta_app' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/setting/meta-app[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Setting\Controller',
                        'controller'    => Controller\MetaAppController::class,
                        'action'        => 'connect',
                    ],
                ],
            ],
            'setting' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/setting[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Setting\Controller',
                        'controller'    => Controller\SettingController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\SettingController::class => AppInvokableFactory::class,
            Controller\MetaAppController::class  => AppInvokableFactory::class,
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
