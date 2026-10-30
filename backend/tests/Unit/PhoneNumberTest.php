<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function numbers(): array
    {
        return [
            'mobile sénégalais sans indicatif' => ['77 123 45 67', '+221771234567'],
            'fixe sénégalais' => ['33 820 12 34', '+221338201234'],
            'indicatif avec 00' => ['00221 76 000 11 22', '+221760001122'],
            'diaspora en France' => ['+33 6 12 34 56 78', '+33612345678'],
            'trop court' => ['771234', null],
            'lettres' => ['appelez-moi', null],
        ];
    }

    public function test_it_formats_senegalese_numbers_for_display(): void
    {
        $this->assertSame('+221 77 123 45 67', PhoneNumber::format('+221771234567'));
        $this->assertSame('+33612345678', PhoneNumber::format('+33612345678'));
    }

    #[DataProvider('numbers')]
    public function test_it_normalizes_phone_numbers(string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }
}
