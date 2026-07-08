<?php

declare(strict_types=1);

namespace Application\Model;

use Application\Model\Constant\AppConstModel;
use Exception;

class AppModel
{
    protected ?array $options = null;
/*
     * Cột extraContent trong DB — lưu dạng gzip(json_encode(array))
     * - extraContent : raw string từ DB
     * - extraFields  : array sau khi decode, dùng trên RAM
     * - Subclass khai báo getConstClass() để validate/cast từng field
     */
    protected ?string $extraContent = null;
    protected ?array $extraFields = null;
/**
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->exchangeArray($options);
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function addOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }


    /**
     * @param      $key
     * @param null $default
     * @return null
     */
    public function getOption($key, $default = null)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }
        return $default;
    }


    /**
     * Overloading: allow property access
     *
     * @param string $name
     * @param mixed  $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if ('mapper' == $name || ! method_exists($this, $method)) {
            throw new Exception('Invalid property specified');
        }
        $this->$method($value);
    }

    /**
     * Overloading: allow property access
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if ('mapper' == $name || ! method_exists($this, $method)) {
            throw new Exception('Invalid property specified');
        }
        return $this->$method();
    }

    /**
     * Set object state
     */
    public function exchangeArray($options)
    {
        if (is_array($options)) {
            foreach ($options as $key => $value) {
            // Thử trực tiếp trước (key không có underscore)
                $method = 'set' . ucfirst($key);
                if (! in_array($method, get_class_methods($this))) {
        // Thử snake_case → camelCase: password_hash → setPasswordHash
                    $camel  = lcfirst(str_replace('_', '', ucwords($key, '_')));
                    $method = 'set' . ucfirst($camel);
                }
                if (in_array($method, get_class_methods($this))) {
                    $this->$method($this->normalizeValue($method, $value));
                }
            }
        }
        return $this;
    }

    private function normalizeValue(string $method, mixed $value): mixed
    {
        if ($value === '' || $value === null) {
            return null;
        }
        $param = (new \ReflectionMethod($this, $method))->getParameters()[0] ?? null;
        $type = $param?->getType();
        if (! $type instanceof \ReflectionNamedType) {
            return $value;
        }
        $typeName = $type->getName();
        return match ($typeName) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => (bool) $value,
            'string' => (string) $value,
            default => $value,
        };
    }

    // -------------------------------------------------------------------------
    /*
     * extraContent / extraFields
     * - extraContent : raw string từ DB (gzip hoặc JSON)
     * - extraFields  : decoded array dùng trên RAM
     */

    /**
     * Subclass override để trả về Const class tương ứng (vd: PostConst::class)
     * AppModel dùng để validate key và cast value khi addExtraField
     */
    protected function getConstClass(): ?string
    {
        return null;
    }

    public function getExtraContent(): ?string
    {
        return $this->extraContent;
    }

    /**
     * Nhận raw string từ DB (json_encode), tự decode sang extraFields array
     */
    public function setExtraContent(mixed $raw): self
    {
        if (is_array($raw)) {
            $this->extraFields = $raw;
            $this->extraContent = json_encode($raw) ?: null;
            return $this;
        }
        $this->extraContent = is_string($raw) && $raw !== '' ? $raw : null;
        if ($this->extraContent !== null) {
            $decoded = json_decode($this->extraContent, true);
            $this->extraFields = is_array($decoded) ? $decoded : [];
        }
        return $this;
    }

    public function addExtraField(string $key, mixed $value): self
    {
        /** @var AppConstModel|null $constClass */
        $constClass = $this->getConstClass();
        if ($constClass !== null) {
            if (! $constClass::isAllowedExtraField($key)) {
                return $this;
            }
            $value = $constClass::castValueField($key, $value);
        }
        if (! is_array($this->extraFields)) {
            $this->extraFields = [];
        }
        $this->extraFields[$key] = $value;
        return $this;
    }

    public function getExtraFieldsArray(): array
    {
        /** @var AppConstModel|null $constClass */
        $constClass = $this->getConstClass();
        if ($constClass !== null) {
            return $constClass::getExtraFieldsArray($this->extraFields);
        }
        return $this->extraFields ?? [];
    }
}
