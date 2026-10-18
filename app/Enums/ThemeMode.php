<?php

namespace App\Enums;

// @2026-Alifiandra
enum ThemeMode: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Ikuti sistem',
            self::Light => 'Terang',
            self::Dark => 'Gelap',
        };
    }
}
