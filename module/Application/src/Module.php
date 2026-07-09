<?php

declare(strict_types=1);

namespace Application;

use Laminas\EventManager\EventInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\Mvc\MvcEvent;

class Module implements BootstrapListenerInterface
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap(EventInterface $e): void
    {
        /** @var MvcEvent $e */
        $app          = $e->getTarget();
        $eventManager = $app->getEventManager();

        // Chạy trước routing để chặn sớm preflight OPTIONS (CORS).
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], 1000);
        // Gắn header CORS vào mọi response (kể cả lỗi/exception).
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], -1000);
    }

    public function onRoute(MvcEvent $e): void
    {
        $request = $e->getRequest();
        if (! $request instanceof HttpRequest || $request->getMethod() !== 'OPTIONS') {
            return;
        }

        $response = $e->getResponse();
        $this->applyCorsHeaders($e);
        if ($response instanceof HttpResponse) {
            $response->setStatusCode(204);
        }
        $e->stopPropagation(true);
    }

    public function onFinish(MvcEvent $e): void
    {
        $this->applyCorsHeaders($e);
    }

    private function applyCorsHeaders(MvcEvent $e): void
    {
        $request  = $e->getRequest();
        $response = $e->getResponse();
        if (! $request instanceof HttpRequest || ! $response instanceof HttpResponse) {
            return;
        }

        $originHeader = $request->getHeader('Origin');
        if (! $originHeader) {
            return;
        }
        $origin = $originHeader->getFieldValue();

        $config  = $e->getApplication()->getServiceManager()->get('config');
        $allowed = $config['cors']['allowed_origins'] ?? [];
        if (! in_array($origin, $allowed, true)) {
            return;
        }

        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin', $origin);
        $headers->addHeaderLine('Access-Control-Allow-Credentials', 'true');
        $headers->addHeaderLine('Vary', 'Origin');
        $headers->addHeaderLine('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->addHeaderLine('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
