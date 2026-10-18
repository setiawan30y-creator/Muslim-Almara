<?php

namespace App\Services\Quran\Audio;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien Quran.Foundation Content API v4 (audio per surah).
 * - OAuth2 client_credentials (scope "content"); token disimpan di cache server < 1 jam.
 * - Kredensial TIDAK pernah dikirim ke browser; browser hanya menerima URL audio publik.
 * - Data di-cache maksimal sesuai config (≤ 1 minggu, ketentuan penyedia).
 * - Mode simulasi (QF_MOCK) untuk mencoba pemutar tanpa kredensial.
 *
 * Dokumentasi: https://api-docs.quran.foundation
 * @2026-Alifiandra
 */
class QuranFoundationClient
{
    public const MOCK_ID = 0;

    private function cfg(string $key, mixed $default = null): mixed
    {
        return config('almara.audio.quran_foundation.'.$key, $default);
    }

    public function isMock(): bool
    {
        return (bool) $this->cfg('enabled') && (bool) $this->cfg('mock');
    }

    public function configured(): bool
    {
        return (bool) $this->cfg('enabled') && ($this->isMock() || ($this->cfg('client_id') && $this->cfg('client_secret')));
    }

    public function attribution(): string
    {
        return (string) $this->cfg('attribution', 'Quran data provided by Quran.Foundation');
    }

    private function prelive(): bool
    {
        return $this->cfg('environment') === 'prelive';
    }

    private function tokenUrl(): string
    {
        return $this->prelive() ? 'https://prelive-oauth2.quran.foundation/oauth2/token' : 'https://oauth2.quran.foundation/oauth2/token';
    }

    private function apiBase(): string
    {
        return ($this->prelive() ? 'https://apis-prelive.quran.foundation' : 'https://apis.quran.foundation').'/content/api/v4';
    }

    private function ttl(): int
    {
        return max(60, min(604800, (int) $this->cfg('cache_seconds', 86400)));
    }

    private function cacheKey(string $suffix): string
    {
        return 'qf:'.($this->prelive() ? 'pre' : 'prod').':'.$suffix;
    }

    private function token(bool $fresh = false): ?string
    {
        $key = $this->cacheKey('token');
        if ($fresh) {
            Cache::forget($key);
        }
        if ($t = Cache::get($key)) {
            return $t;
        }
        $res = Http::asForm()->timeout((int) $this->cfg('timeout', 8))
            ->withBasicAuth((string) $this->cfg('client_id'), (string) $this->cfg('client_secret'))
            ->post($this->tokenUrl(), ['grant_type' => 'client_credentials', 'scope' => 'content']);
        if (! $res->successful() || ! is_string($res->json('access_token'))) {
            Log::warning('Quran.Foundation: gagal mengambil token', ['status' => $res->status()]);

            return null;
        }
        $expires = max(120, (int) $res->json('expires_in', 3600));
        Cache::put($key, $res->json('access_token'), $expires - 60);

        return $res->json('access_token');
    }

    /** @return array<string, mixed>|null */
    private function get(string $path, array $query = []): ?array
    {
        foreach ([false, true] as $fresh) {
            $token = $this->token($fresh);
            if (! $token) {
                return null;
            }
            $res = Http::acceptJson()->timeout((int) $this->cfg('timeout', 8))
                ->withHeaders(['x-auth-token' => $token, 'x-client-id' => (string) $this->cfg('client_id')])
                ->get($this->apiBase().$path, $query);
            if ($res->status() === 401 && ! $fresh) {
                continue; // token kedaluwarsa → minta ulang sekali
            }
            if (! $res->successful()) {
                Log::warning('Quran.Foundation: permintaan gagal', ['path' => $path, 'status' => $res->status()]);

                return null;
            }

            return $res->json();
        }

        return null;
    }

    /** @return array<int, array{id: int, name: string, style: ?string}> */
    public function reciters(): array
    {
        if (! $this->configured()) {
            return [];
        }
        if ($this->isMock()) {
            return [['id' => self::MOCK_ID, 'name' => 'Qari simulasi (nada uji)', 'style' => 'Simulasi']];
        }
        $key = $this->cacheKey('reciters');
        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }
        try {
            $json = $this->get('/resources/chapter_reciters', ['language' => 'id']);
        } catch (\Throwable $e) {
            Log::warning('Quran.Foundation: daftar qari gagal', ['error' => $e->getMessage()]);
            $json = null;
        }
        if (! $json) {
            return [];
        }
        $list = collect($json['reciters'] ?? [])->filter(fn ($r) => isset($r['id']))->map(fn ($r) => [
            'id' => (int) $r['id'],
            'name' => (string) ($r['translated_name']['name'] ?? $r['name'] ?? 'Qari'),
            'style' => $r['style']['name'] ?? null,
        ])->sortBy('name')->values()->all();
        Cache::put($key, $list, $this->ttl());

        return $list;
    }

    /** @return array{url: string, segments: array<int, array{ayah: int, start_ms: int, end_ms: int}>, duration_ms: ?int}|null */
    public function chapterAudio(int $reciterId, int $surah, int $ayahCount = 0): ?array
    {
        if (! $this->configured() || $surah < 1 || $surah > 114) {
            return null;
        }
        if ($this->isMock()) {
            return [
                'url' => route('quran.audio.sample', $surah),
                'segments' => SampleTone::segments($ayahCount),
                'duration_ms' => SampleTone::durationMs($ayahCount),
            ];
        }
        $key = $this->cacheKey("chapter:{$reciterId}:{$surah}");
        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }
        try {
            $json = $this->get("/chapter_recitations/{$reciterId}/{$surah}", ['segments' => 'true']);
        } catch (\Throwable $e) {
            Log::warning('Quran.Foundation: audio surah gagal', ['error' => $e->getMessage()]);
            $json = null;
        }
        $file = $json['audio_file'] ?? null;
        $url = is_array($file) ? ($file['audio_url'] ?? null) : null;
        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            return null;
        }
        $segments = collect($file['timestamps'] ?? [])->map(function ($t) {
            $parts = explode(':', (string) ($t['verse_key'] ?? ''));
            $ayah = (int) ($parts[1] ?? 0);

            return ['ayah' => $ayah, 'start_ms' => (int) ($t['timestamp_from'] ?? 0), 'end_ms' => (int) ($t['timestamp_to'] ?? 0)];
        })->filter(fn ($s) => $s['ayah'] > 0 && $s['end_ms'] > $s['start_ms'])->values()->all();

        $out = ['url' => $url, 'segments' => $segments, 'duration_ms' => $segments ? end($segments)['end_ms'] : null];
        Cache::put($key, $out, $this->ttl());

        return $out;
    }
}
