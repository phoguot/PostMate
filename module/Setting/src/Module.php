<?php
declare(strict_types=1);

namespace Setting;

use Application\Factory\AppInvokableFactory;
use Setting\Model\MetaAppCredential\MetaAppCredentialMapper;
use Setting\Model\MetaAppCredential\MetaAppCredentialModel;
use Setting\Model\Settings\SettingsMapper;
use Setting\Model\Settings\SettingsModel;
use Setting\Service\MetaAppService;
use Setting\Service\SettingsService;

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
                SettingsModel::class           => SettingsModel::class,
                MetaAppCredentialModel::class  => MetaAppCredentialModel::class,
            ],
            'factories'  => [
                SettingsMapper::class          => AppInvokableFactory::class,
                MetaAppCredentialMapper::class => AppInvokableFactory::class,
                SettingsService::class         => AppInvokableFactory::class,
                MetaAppService::class          => AppInvokableFactory::class,
            ],
        ];
    }
}
