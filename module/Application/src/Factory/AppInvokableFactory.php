<?php
declare(strict_types=1);

namespace Application\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AppInvokableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): object
    {
        $instance = new $requestedName();
        if (method_exists($instance, 'setContainer')) {
            $instance->setContainer($container);
        }
        return $instance;
    }
}
