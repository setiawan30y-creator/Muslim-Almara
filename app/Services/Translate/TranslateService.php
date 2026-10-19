<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Terjemah teks bebas melalui layanan kompatibel LibreTranslate (dikonfigurasi admin server).
 * Teks tidak disimpan; hasil di-cache singkat agar hemat kuota.
 * @2026-Alifiandra
 */
class TranslateService
{
    public function enabled(): bool
    {
        return (bool) config('almara.translate.url');
    }

    /** @return array<string, string> */
    public function languages(): array
    {
        return (array) config('almara.translate.languages', []);
    }

    public function translate(string $text, string $source, string $target): ?string
    {
        if (! $this->enabled()) {
            return null;
        }
        $key = 'tr:'.sha1($source.'|'.$target.'|'.$text);

        return Cache::remember($key, 86400, function () use ($text, $source, $target) {
            try {
                $res = Http::acceptJson()->timeout((int) config('almara.translate.timeout', 10))
                    ->post(rtrim((string) config('almara.translate.url'), '/').'/translate', array_filter([
                        'q' => $text, 'source' => $source, 'target' => $target, 'format' => 'text',
                        'api_key' => config('almara.translate.api_key'),
                    ]));
                if (! $res->successful() || ! is_string($res->json('translatedText'))) {
                    Log::warning('Terjemah gagal', ['status' => $res->status()]);

                    return null;
                }

                return $res->json('translatedText');
            } catch (\Throwable $e) {
                Log::warning('Terjemah gagal', ['error' => $e->getMessage()]);

                return null;
            }
        }) ?: null;
    }
}
