<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Cache;

/**
 * Data statis non-agama/berskala kecil yang dibaca dari resources/data:
 * daftar kota (lokasi manual) dan Asmaul Husna.
 * Konten agama yang memerlukan review (Al-Qur'an, doa, dzikir) berada di database.
 *
 * @2026-Alifiandra
 */
class StaticContent
{
    public function __construct(private string $path)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function asmaulHusna(): array
    {
        return $this->json('asmaul-husna.json');
    }

    /** @return array<int, array<string, mixed>> */
    public function cities(): array
    {
        return $this->json('kota.json');
    }

    /** @return array<string, mixed>|null */
    public function city(string $slug): ?array
    {
        foreach ($this->cities() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }

        return null;
    }

    /** @return array<mixed> */
    private function json(string $file): array
    {
        $full = $this->path.DIRECTORY_SEPARATOR.$file;

        return Cache::rememberForever('content:'.$file.':'.@filemtime($full), function () use ($full) {
            if (! is_file($full)) {
                return [];
            }

            return json_decode((string) file_get_contents($full), true, flags: JSON_THROW_ON_ERROR);
        });
    }
}
