<?php

declare(strict_types=1);

namespace Application\Model\Constant;

abstract class AppConstModel
{
    public static array $allowedExtraFields = [];

    public static function isAllowedExtraField(string $key): bool
    {
        return array_key_exists($key, static::$allowedExtraFields);
    }

    public static function castValueField(string $key, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        $type = static::$allowedExtraFields[$key] ?? null;
        if (! $type) {
            return $value;
        }
        return match ($type) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => (bool) $value,
            'string' => (string) $value,
            default  => $value,
        };
    }

    public static function getExtraFieldsArray(?array $fields): array
    {
        if (empty($fields)) {
            return [];
        }
        $result = [];
        foreach (static::$allowedExtraFields as $key => $type) {
            if (isset($fields[$key])) {
                $result[$key] = static::castValueField($key, $fields[$key]);
            }
        }
        return $result;
    }
}
