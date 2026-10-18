<?php

namespace App\Enums;

/** Warna aksen yang diizinkan (blueprint: Pengaturan Tampilan & Tema). @2026-Alifiandra */
enum AccentColor: string
{
    case Hijau = 'hijau';
    case Biru = 'biru';
    case Ungu = 'ungu';
    case Emas = 'emas';
    case Teal = 'teal';

    public function label(): string
    {
        return match ($this) {
            self::Hijau => 'Hijau Islami',
            self::Biru => 'Biru',
            self::Ungu => 'Ungu',
            self::Emas => 'Emas',
            self::Teal => 'Teal',
        };
    }

    /** Warna contoh untuk tombol pilihan (mode terang). */
    public function swatch(): string
    {
        return match ($this) {
            self::Hijau => '#0f6b5c',
            self::Biru => '#1d5fae',
            self::Ungu => '#6b3fa0',
            self::Emas => '#8a5a00',
            self::Teal => '#00707a',
        };
    }
}
