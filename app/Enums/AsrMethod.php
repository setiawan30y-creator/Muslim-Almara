<?php

namespace App\Enums;

// @2026-Alifiandra
enum AsrMethod: string
{
    case Standard = 'standard';
    case Hanafi = 'hanafi';

    public function label(): string
    {
        return match ($this) {
            self::Standard => "Jumhur (Syafi'i, Maliki, Hanbali)",
            self::Hanafi => 'Hanafi',
        };
    }

    public function shadowFactor(): int
    {
        return $this === self::Hanafi ? 2 : 1;
    }
}
