<?php

namespace App\Enums;

/**
 * Alur publikasi konten agama (blueprint §5.3): draft → review → approved → published.
 * Hanya `published` yang tampil ke pengguna. @2026-Alifiandra
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Approved = 'approved';
    case Published = 'published';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Review => 'Menunggu review',
            self::Approved => 'Disetujui',
            self::Published => 'Terbit',
            self::Suspended => 'Ditangguhkan',
            self::Archived => 'Diarsipkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::Review, self::Approved => 'warning',
            self::Suspended => 'danger',
            default => 'neutral',
        };
    }
}
