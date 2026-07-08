<?php
declare(strict_types=1);

namespace Application\Factory;

use Psr\Container\ContainerInterface;

class AppServiceFactory
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    protected function getContainerEntry(string $id): mixed
    {
        return $this->container->get($id);
    }
}
