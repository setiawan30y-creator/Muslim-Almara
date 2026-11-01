<?php

namespace App\Services\Quran\Audio;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Qari pilihan dari Islamic Network / AlQuran Cloud (https://alquran.cloud) — tanpa kunci API.
 * Audio per ayat dari CDN mereka (diputar langsung oleh peramban, ayat demi ayat → sorot ayat akurat).
 * Ketentuan penyedia: rekaman bebas untuk penggunaan non-komersial; boleh disertakan dalam produk
 * komersial namun hak cipta tetap milik qari dan qari dapat meminta penghapusan
 * (https://alquran.cloud/terms-and-conditions). Admin dapat membatasi daftar qari lewat .env.
 * @2026-Alifiandra
 */
class IslamicNetworkClient
{
    public const API = 'https://api.alquran.cloud/v1';
    public const CDN_HOSTS = ['cdn.islamic.network', 'cdn.alquran.cloud', 'cdn.islamic.app'];

    /** Daftar cadangan bila API daftar qari belum dapat dihubungi. */
    public const FALLBACK = [
        'ar.alafasy' => 'Mishary Rashid Alafasy',
        'ar.abdurrahmaansudais' => 'Abdurrahmaan As-Sudais',
        'ar.saoodshuraym' => 'Saood bin Ibraaheem Ash-Shuraym',
        'ar.mahermuaiqly' => 'Maher Al Muaiqly',
        'ar.husary' => 'Mahmoud Khalil Al-Husary',
        'ar.husarymujawwad' => 'Mahmoud Khalil Al-Husary (Mujawwad)',
        'ar.minshawi' => 'Mohamed Siddiq al-Minshawi',
        'ar.minshawimujawwad' => 'Mohamed Siddiq al-Minshawi (Mujawwad)',
        'ar.abdulbasitmurattal' => 'Abdul Basit',
        'ar.shaatree' => 'Abu Bakr Ash-Shaatree',
        'ar.ahmedajamy' => 'Ahmed ibn Ali al-Ajamy',
        'ar.hudhaify' => 'Ali Al-Hudhaify',
        'ar.muhammadayyoub' => 'Muhammad Ayyoub',
        'ar.muhammadjibreel' => 'Muhammad Jibreel',
    ];

    private function cfg(string $k, mixed $d = null): mixed
    {
        return config('almara.audio.islamic_network.'.$k, $d);
    }

    public function enabled(): bool
    {
        return (bool) $this->cfg('enabled', true);
    }

    public function attribution(): string
    {
        return 'Audio: Islamic Network / AlQuran Cloud';
    }

    /** @return array<int, string> identifier yang diizinkan admin (kosong = semua) */
    private function allowList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->cfg('reciters', '')))));
    }

    public static function styleOf(string $id, string $name): string
    {
        $s = strtolower($id.' '.$name);

        return match (true) {
            str_contains($s, 'mujawwad') => 'Mujawwad',
            str_contains($s, 'muallim') || str_contains($s, 'teacher') => "Mu'allim",
            default => 'Murattal',
        };
    }

    /** @return array<int, array{id: string, name: string, style: string}> */
    public function reciters(): array
    {
        if (! $this->enabled()) {
            return [];
        }
        $list = Cache::get('in:reciters');
        if (! is_array($list)) {
            $list = null;
            try {
                $res = Http::acceptJson()->timeout(8)->get(self::API.'/edition/format/audio');
                if ($res->successful() && is_array($res->json('data'))) {
                    $list = collect($res->json('data'))
                        ->filter(fn ($e) => ($e['language'] ?? '') === 'ar' && preg_match('/^ar\.[a-z0-9]{2,40}$/', (string) ($e['identifier'] ?? '')))
                        ->map(fn ($e) => ['id' => $e['identifier'], 'name' => trim((string) ($e['englishName'] ?? $e['name'] ?? $e['identifier']))])
                        ->values()->all();
                    Cache::put('in:reciters', $list, 7 * 86400);
                }
            } catch (\Throwable $e) {
                Log::warning('Islamic Network: daftar qari gagal', ['error' => $e->getMessage()]);
            }
            if (! $list) {
                $list = collect(self::FALLBACK)->map(fn ($n, $id) => ['id' => $id, 'name' => $n])->values()->all();
                Cache::put('in:reciters', $list, 3600); // coba lagi satu jam kemudian
            }
        }
        $allow = $this->allowList();

        return collect($list)
            ->filter(fn ($r) => ! $allow || in_array($r['id'], $allow, true))
            ->map(fn ($r) => $r + ['style' => self::styleOf($r['id'], $r['name'])])
            ->sortBy('name')->values()->all();
    }

    public static function isCdnUrl(string $url): bool
    {
        $p = parse_url($url);

        return ($p['scheme'] ?? '') === 'https' && in_array(strtolower($p['host'] ?? ''), self::CDN_HOSTS, true);
    }

    /** Audio per ayat satu surah. @return array<int, array{ayah: int, url: string}>|null */
    public function surahTracks(string $edition, int $surah): ?array
    {
        if (! $this->enabled() || ! preg_match('/^ar\.[a-z0-9]{2,40}$/', $edition) || $surah < 1 || $surah > 114) {
            return null;
        }
        $key = "in:surah:{$edition}:{$surah}";
        if (is_array($hit = Cache::get($key))) {
            return $hit;
        }
        try {
            $res = Http::acceptJson()->timeout(10)->get(self::API."/surah/{$surah}/{$edition}");
        } catch (\Throwable $e) {
            Log::warning('Islamic Network: audio surah gagal', ['error' => $e->getMessage()]);

            return null;
        }
        $ayahs = $res->successful() ? $res->json('data.ayahs') : null;
        if (! is_array($ayahs) || ! $ayahs) {
            return null;
        }
        $tracks = [];
        foreach ($ayahs as $a) {
            $url = (string) ($a['audio'] ?? '');
            $n = (int) ($a['numberInSurah'] ?? 0);
            if ($n < 1 || ! self::isCdnUrl($url)) {
                return null; // data tidak sesuai harapan → jangan putar sebagian
            }
            $alt = collect((array) ($a['audioSecondary'] ?? []))->filter(fn ($u) => is_string($u) && self::isCdnUrl($u))->values()->first();
            $tracks[] = ['ayah' => $n, 'url' => $url] + ($alt ? ['alt' => $alt] : []);
        }
        Cache::put($key, $tracks, 7 * 86400);

        return $tracks;
    }
}
