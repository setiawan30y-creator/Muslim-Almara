<?php

namespace App\Services\Translate;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Terjemah teks & percakapan (Indonesia ⇄ Arab ⇄ Inggris ⇄ Melayu ⇄ Turki ⇄ Urdu).
 * Penyedia: Google Cloud Translation, DeepL, LibreTranslate, atau MyMemory (bawaan tanpa kunci).
 * Dipanggil dari server: kunci API tidak pernah dikirim ke browser; teks tidak disimpan di database,
 * hanya di-cache singkat agar hemat kuota.
 * @2026-Alifiandra
 */
class TranslateService
{
    public const DRIVERS = ['google' => 'Google Translate', 'deepl' => 'DeepL', 'libretranslate' => 'LibreTranslate', 'mymemory' => 'MyMemory'];

    private function cfg(string $k, mixed $d = null): mixed
    {
        return config('almara.translate.'.$k, $d);
    }

    public function driver(): ?string
    {
        $want = (string) $this->cfg('driver', 'auto');
        if ($want === 'off') {
            return null;
        }
        if ($want !== 'auto') {
            return isset(self::DRIVERS[$want]) ? $want : null;
        }

        return match (true) {
            (bool) $this->cfg('google_key') => 'google',
            (bool) $this->cfg('deepl_key') => 'deepl',
            (bool) $this->cfg('url') => 'libretranslate',
            default => 'mymemory',
        };
    }

    public function enabled(): bool
    {
        return $this->driver() !== null;
    }

    public function providerName(): ?string
    {
        return ($d = $this->driver()) ? self::DRIVERS[$d] : null;
    }

    /** @return array<string, string> kode => nama bahasa */
    public function languages(): array
    {
        return collect((array) $this->cfg('languages', []))->map(fn ($v) => is_array($v) ? $v[0] : $v)->all();
    }

    /** @return array<string, string> kode => kode suara (BCP-47) untuk pengenalan & pelafalan */
    public function speechCodes(): array
    {
        return collect((array) $this->cfg('languages', []))->map(fn ($v, $k) => is_array($v) ? ($v[1] ?? $k) : $k)->all();
    }

    public function translate(string $text, string $source, string $target): ?string
    {
        $driver = $this->driver();
        $text = trim($text);
        if (! $driver || $text === '' || $source === $target) {
            return $driver ? $text : null;
        }
        $key = 'tr:'.$driver.':'.sha1($source.'|'.$target.'|'.$text);
        if (is_string($hit = Cache::get($key))) {
            return $hit;
        }
        try {
            $out = match ($driver) {
                'google' => $this->google($text, $source, $target),
                'deepl' => $this->deepl($text, $source, $target),
                'libretranslate' => $this->libre($text, $source, $target),
                'mymemory' => $this->mymemory($text, $source, $target),
            };
        } catch (\Throwable $e) {
            Log::warning('Terjemah gagal', ['driver' => $driver, 'error' => $e->getMessage()]);
            $out = null;
        }
        if (is_string($out) && $out !== '') {
            $out = html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            Cache::put($key, $out, 86400);

            return $out;
        }

        return null;
    }

    private function http()
    {
        return Http::acceptJson()->timeout((int) $this->cfg('timeout', 10));
    }

    private function google(string $text, string $source, string $target): ?string
    {
        $res = $this->http()->post('https://translation.googleapis.com/language/translate/v2?key='.urlencode((string) $this->cfg('google_key')), [
            'q' => $text, 'source' => $source, 'target' => $target, 'format' => 'text',
        ]);

        return $res->successful() ? $res->json('data.translations.0.translatedText') : $this->fail($res);
    }

    private function deepl(string $text, string $source, string $target): ?string
    {
        $key = (string) $this->cfg('deepl_key');
        $host = str_ends_with($key, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
        $tgt = ['en' => 'EN-US'][$target] ?? strtoupper($target);
        $res = $this->http()->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$key])
            ->post($host.'/v2/translate', ['text' => [$text], 'source_lang' => strtoupper($source), 'target_lang' => $tgt]);

        return $res->successful() ? $res->json('translations.0.text') : $this->fail($res);
    }

    private function libre(string $text, string $source, string $target): ?string
    {
        $res = $this->http()->post(rtrim((string) $this->cfg('url'), '/').'/translate', array_filter([
            'q' => $text, 'source' => $source, 'target' => $target, 'format' => 'text', 'api_key' => $this->cfg('api_key'),
        ]));

        return $res->successful() ? $res->json('translatedText') : $this->fail($res);
    }

    /** MyMemory membatasi ±500 byte per permintaan → teks dipecah per kalimat. */
    private function mymemory(string $text, string $source, string $target): ?string
    {
        $parts = [];
        foreach (self::chunks($text, 450) as $chunk) {
            $res = $this->http()->get('https://api.mymemory.translated.net/get', array_filter([
                'q' => $chunk, 'langpair' => $source.'|'.$target, 'de' => $this->cfg('mymemory_email'),
            ]));
            $out = $res->successful() && (int) $res->json('responseStatus') === 200 ? $res->json('responseData.translatedText') : null;
            if (! is_string($out) || str_contains($out, 'MYMEMORY WARNING')) {
                return $this->fail($res);
            }
            $parts[] = $out;
        }

        return implode(' ', $parts);
    }

    /** @return array<int, string> */
    public static function chunks(string $text, int $maxBytes): array
    {
        $sentences = preg_split('/(?<=[.!?؟。\n])\s+/u', $text) ?: [$text];
        $out = [];
        $cur = '';
        foreach ($sentences as $s) {
            while (strlen($s) > $maxBytes) { // kalimat sangat panjang: potong di batas kata
                $cut = mb_strcut($s, 0, $maxBytes, 'UTF-8');
                $sp = mb_strrpos($cut, ' ');
                $cut = $sp ? mb_substr($cut, 0, $sp) : $cut;
                $out[] = trim($cut);
                $s = trim(mb_substr($s, mb_strlen($cut)));
            }
            if ($cur !== '' && strlen($cur.' '.$s) > $maxBytes) {
                $out[] = $cur;
                $cur = $s;
            } else {
                $cur = trim($cur.' '.$s);
            }
        }
        if ($cur !== '') {
            $out[] = $cur;
        }

        return $out;
    }

    private function fail($res): ?string
    {
        Log::warning('Terjemah: respons penyedia tidak valid', ['status' => $res->status()]);

        return null;
    }
}
