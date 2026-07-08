<?php

namespace Application\Model;

use Laminas\Http\PhpEnvironment\Response;

class JsonResponse extends Response
{
    protected array $variables = [];

    public function __construct(
        array $variables = [],
        int $status = Response::STATUS_CODE_200,
        array $headers = []
    ) {
        $this->setVariables($variables, true);
        $this->setStatusCode($status);

        $this->getHeaders()->addHeaderLine(
            'Content-Type',
            $headers['Content-Type'] ?? 'application/json; charset=utf-8'
        );
    }

    public function setVariables(array $variables, bool $overwrite = false): self
    {
        if ($overwrite) {
            $this->variables = $variables;
        } else {
            $this->variables = array_merge($this->variables, $variables);
        }

        $this->refreshContent();
        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setVariable(string $name, mixed $value): self
    {
        $this->variables[$name] = $value;
        $this->refreshContent();
        return $this;
    }

    public function getVariable(string $name, mixed $default = null): mixed
    {
        return $this->variables[$name] ?? $default;
    }

    protected function refreshContent(): void
    {
        $this->setContent(json_encode(
            $this->variables,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));
    }
}
