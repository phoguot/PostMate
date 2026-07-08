<?php
declare(strict_types=1);

namespace Infra;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'infra_browser_profile' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/api/infra/browser-profile[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        '__NAMESPACE__' => 'Infra\Controller',
                        'controller'    => Controller\BrowserProfileController::class,
                        'action'        => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\BrowserProfileController::class => AppInvokableFactory::class,
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
