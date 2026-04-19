<?php

declare(strict_types=1);

namespace App\Enums;

enum Domain: string
{
    case Dimensional = 'dimensional';
    case Pressao = 'pressao';
    case Massa = 'massa';
    case Temperatura = 'temperatura';

    public function label(): string
    {
        return match ($this) {
            self::Dimensional => 'Dimensional',
            self::Pressao => 'Pressão',
            self::Massa => 'Massa',
            self::Temperatura => 'Temperatura',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
