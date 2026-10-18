<?php

namespace App\Enums;

// @2026-Alifiandra
enum TextSize: string
{
    case Small = 'kecil';
    case Normal = 'normal';
    case Large = 'besar';
    case ExtraLarge = 'sangat-besar';

    public function label(): string
    {
        return match ($this) {
            self::Small => 'Kecil',
            self::Normal => 'Normal',
            self::Large => 'Besar',
            self::ExtraLarge => 'Sangat besar',
        };
    }

    /** Ukuran yang tersedia untuk teks antarmuka (tanpa sangat besar). */
    public static function interface(): array
    {
        return [self::Small, self::Normal, self::Large];
    }
}
