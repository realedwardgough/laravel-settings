<?php

namespace Egough\LaravelSettings\Support;

final class Caster
{
    public static function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'bool',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_array($value) => 'json',
            is_object($value) => 'json',
            default => 'string',
        };
    }

    public static function encode(string $type, mixed $value): ?string
    {
        if ($value === null) return null;

        return match ($type) {
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            'bool' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    public static function decode(string $type, ?string $value): mixed
    {
        if ($value === null) return null;

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => $value === '1',
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }
}
