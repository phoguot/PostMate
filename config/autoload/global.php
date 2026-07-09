<?php

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\AdapterServiceFactory;
use Laminas\Session\Service\SessionConfigFactory;
use Laminas\Session\Service\SessionManagerFactory;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;

return [
    'cors' => [
        'allowed_origins' => [
            'https://post-mate-fe.vercel.app',
            'http://localhost:4200',
        ],
    ],
    'service_manager' => [
        'factories' => [
            AdapterInterface::class    => AdapterServiceFactory::class,
            SessionConfig::class       => SessionConfigFactory::class,
            SessionManager::class      => SessionManagerFactory::class,
        ],
        'aliases' => [
            'Laminas\Db\Adapter\Adapter' => AdapterInterface::class,
        ],
    ],
    'session_manager' => [
        'validators' => [],
    ],
];
