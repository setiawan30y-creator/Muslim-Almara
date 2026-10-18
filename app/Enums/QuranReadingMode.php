<?php

namespace App\Enums;

// @2026-Alifiandra
enum QuranReadingMode: string
{
    case Mushaf = 'mushaf';
    case Verse = 'verse';

    public function label(): string
    {
        return match ($this) {
            self::Mushaf => 'Mushaf penuh',
            self::Verse => 'Potongan ayat',
        };
    }
}
