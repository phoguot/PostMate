<?php

declare(strict_types=1);

namespace Application\Filter;

use Application\Model\AppMessage;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class CommonFieldFilters
{
    public const TYPE_TEXT = 'text';
    public const TYPE_INT  = 'int';

    public const LEN_TITLE           = 500;
    public const LEN_DESCRIPTION     = 2000;
    public const LEN_META_TITLE      = 255;
    public const LEN_META_KEYWORDS   = 500;
    public const LEN_META_DESCRIPTION = 1000;

    public static function intField(string $name, bool $required = false): array
    {
        $validators = [];
        if ($required) {
            $validators[] = [
                'name'                   => NotEmpty::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'messages' => ['isEmpty' => AppMessage::VALIDATOR_REQUIRED],
                ],
            ];
        }
        return [
            'name'       => $name,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'Digits'],
            ],
            'validators' => $validators,
        ];
    }

    public static function dynamicField(string $name, array $options = []): array
    {
        $required  = $options['required'] ?? false;
        $maxLength = $options['maxLength'] ?? null;

        $validators = [];
        if ($required) {
            $validators[] = [
                'name'                   => NotEmpty::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'messages' => ['isEmpty' => AppMessage::VALIDATOR_REQUIRED],
                ],
            ];
        }
        if ($maxLength) {
            $validators[] = [
                'name'    => StringLength::class,
                'options' => [
                    'max'      => $maxLength,
                    'messages' => ['stringLengthTooLong' => AppMessage::INVALID_DATA],
                ],
            ];
        }

        return [
            'name'        => $name,
            'required'    => $required,
            'allow_empty' => ! $required,
            'filters'     => [['name' => 'StringTrim']],
            'validators'  => $validators,
        ];
    }

    public static function intArrayField(string $name): array
    {
        return [
            'name'        => $name,
            'required'    => false,
            'allow_empty' => true,
        ];
    }

    public static function objectArrayField(string $name): array
    {
        return [
            'name'        => $name,
            'required'    => false,
            'allow_empty' => true,
        ];
    }
}
