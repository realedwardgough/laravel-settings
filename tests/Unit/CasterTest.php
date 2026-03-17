<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Unit;

use Egough\LaravelSettings\Support\Caster;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CasterTest extends TestCase
{
    public static function typeDetectionProvider(): array
    {
        return [
            'bool' => [true, 'bool'],
            'int' => [42, 'int'],
            'float' => [4.2, 'float'],
            'array' => [['a' => 1], 'json'],
            'object' => [(object) ['a' => 1], 'json'],
            'string' => ['value', 'string'],
        ];
    }

    #[DataProvider('typeDetectionProvider')]
    public function test_it_detects_supported_types(mixed $value, string $expected): void
    {
        $this->assertSame($expected, Caster::detectType($value));
    }

    public function test_it_encodes_and_decodes_complex_values(): void
    {
        $encoded = Caster::encode('json', ['enabled' => true, 'threshold' => 3]);

        $this->assertSame(
            ['enabled' => true, 'threshold' => 3],
            Caster::decode('json', $encoded),
        );
    }

    public function test_it_encodes_and_decodes_boolean_values(): void
    {
        $this->assertSame('1', Caster::encode('bool', true));
        $this->assertTrue(Caster::decode('bool', '1'));
        $this->assertFalse(Caster::decode('bool', '0'));
    }
}
