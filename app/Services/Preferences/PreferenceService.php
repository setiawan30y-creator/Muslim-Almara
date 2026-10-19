<?php

namespace App\Services\Preferences;

use App\Enums\AccentColor;
use App\Enums\AsrMethod;
use App\Enums\QuranReadingMode;
use App\Enums\TextSize;
use App\Enums\ThemeMode;
use App\Models\AppSetting;
use App\Models\PrayerMethod;
use App\Models\User;
use App\Services\Prayer\PrayerParameters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

/**
 * Sumber tunggal preferensi pengguna.
 * - Pengguna login: dibaca/ditulis di tabel preferensi miliknya (sinkron lintas perangkat).
 * - Tamu: disimpan di cookie peramban (tanpa data sensitif) agar tampilan langsung benar saat render.
 * Semua nilai divalidasi terhadap daftar yang diizinkan.
 *
 * @2026-Alifiandra
 */
class PreferenceService
{
    public const COOKIE_APPEARANCE = 'almara_tampilan';
    public const COOKIE_QURAN = 'almara_quran';
    public const COOKIE_PRAYER = 'almara_sholat';
    public const COOKIE_HOME = 'almara_beranda';

    /** Cookie berisi nilai pilihan biasa (bukan rahasia), dibaca JS & divalidasi server. */
    public const PLAIN_COOKIES = [self::COOKIE_APPEARANCE, self::COOKIE_QURAN, self::COOKIE_PRAYER, self::COOKIE_HOME];

    public const APPEARANCE_DEFAULTS = [
        'theme_mode' => 'system',
        'accent_color' => 'hijau',
        'interface_text_size' => 'normal',
        'reading_text_size' => 'normal',
        'translation_text_size' => 'normal',
    ];

    public const QURAN_DEFAULTS = [
        'reading_mode' => 'mushaf',
        'show_translation' => true,
        'show_transliteration' => false,
        'audio_mode' => 'surah',
        'reciter' => null,
        'show_tafsir' => true,
    ];

    /** Kartu beranda (blueprint §8.3) — urutan bawaan. */
    public const HOME_CARDS = [
        'next_prayer' => 'Sholat berikutnya & hitung mundur',
        'weather' => 'Cuaca hari ini',
        'quick_actions' => 'Aksi cepat',
        'continue_reading' => 'Lanjutkan bacaan',
        'reading_goal' => 'Target tilawah',
        'verse_of_day' => 'Ayat hari ini',
        'events' => 'Hari besar Islam',
        'journal' => 'Catatan pribadi terbaru',
    ];

    /** Selalu memakai request yang sedang berjalan (aman untuk pengujian & worker jangka panjang). */
    private function request(): Request
    {
        return request();
    }

    private function user(): ?User
    {
        return $this->request()->user();
    }

    // ------------------------------------------------------------------ tampilan

    /** @return array<string, string> */
    public function appearance(): array
    {
        $user = $this->user();
        $raw = $user?->appearance?->only(array_keys(self::APPEARANCE_DEFAULTS)) ?? $this->cookieJson(self::COOKIE_APPEARANCE);
        $raw = array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $raw);

        return self::sanitizeAppearance($raw);
    }

    /** @param array<string, mixed> $in @return array<string, string> */
    public static function sanitizeAppearance(array $in): array
    {
        $out = self::APPEARANCE_DEFAULTS;
        if (ThemeMode::tryFrom((string) ($in['theme_mode'] ?? ''))) {
            $out['theme_mode'] = $in['theme_mode'];
        }
        if (AccentColor::tryFrom((string) ($in['accent_color'] ?? ''))) {
            $out['accent_color'] = $in['accent_color'];
        }
        $ui = TextSize::tryFrom((string) ($in['interface_text_size'] ?? ''));
        if ($ui && in_array($ui, TextSize::interface(), true)) {
            $out['interface_text_size'] = $ui->value;
        }
        foreach (['reading_text_size', 'translation_text_size'] as $k) {
            if (TextSize::tryFrom((string) ($in[$k] ?? ''))) {
                $out[$k] = $in[$k];
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $values */
    public function saveAppearance(array $values): array
    {
        $clean = self::sanitizeAppearance($values);
        if ($user = $this->user()) {
            $user->appearance()->updateOrCreate([], $clean);
            $user->unsetRelation('appearance');
        } else {
            $this->queueCookie(self::COOKIE_APPEARANCE, $clean);
        }

        return $clean;
    }

    // --------------------------------------------------------------- Al-Qur'an

    /** @return array<string, mixed> */
    public function quran(): array
    {
        $user = $this->user();
        $raw = $user?->quranPreference?->only(array_keys(self::QURAN_DEFAULTS)) ?? $this->cookieJson(self::COOKIE_QURAN);
        $raw = array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $raw);

        return self::sanitizeQuran($raw);
    }

    /** @param array<string, mixed> $in */
    public static function sanitizeQuran(array $in): array
    {
        $out = self::QURAN_DEFAULTS;
        if (QuranReadingMode::tryFrom((string) ($in['reading_mode'] ?? ''))) {
            $out['reading_mode'] = $in['reading_mode'];
        }
        if (in_array($in['audio_mode'] ?? null, ['surah', 'verse'], true)) {
            $out['audio_mode'] = $in['audio_mode'];
        }
        if (is_string($in['reciter'] ?? null) && preg_match(\App\Services\Quran\Audio\AudioCatalog::KEY_PATTERN, $in['reciter'])) {
            $out['reciter'] = $in['reciter'];
        }
        foreach (['show_translation', 'show_transliteration', 'show_tafsir'] as $k) {
            if (array_key_exists($k, $in)) {
                $out[$k] = filter_var($in[$k], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $out;
    }

    /** Mode efektif: Mushaf penuh hanya bila edisi Mushaf berlisensi telah dikonfigurasi. */
    public static function mushafAvailable(): bool
    {
        return (bool) config('almara.mushaf_edition');
    }

    public function effectiveReadingMode(): QuranReadingMode
    {
        $mode = QuranReadingMode::from($this->quran()['reading_mode']);

        return $mode === QuranReadingMode::Mushaf && ! self::mushafAvailable() ? QuranReadingMode::Verse : $mode;
    }

    /** @param array<string, mixed> $values */
    public function saveQuran(array $values): array
    {
        $clean = self::sanitizeQuran($values + $this->quran());
        if ($user = $this->user()) {
            $user->quranPreference()->updateOrCreate([], $clean);
            $user->unsetRelation('quranPreference');
        } else {
            $this->queueCookie(self::COOKIE_QURAN, $clean);
        }

        return $clean;
    }

    // ------------------------------------------------------------ jadwal sholat

    /** @return array{method: string, asr: string, offsets: array<string, int>} */
    public function prayerSettings(): array
    {
        $default = (string) AppSetting::get('prayer.default_method', config('almara.default_prayer_method', 'kemenag'));
        $user = $this->user();
        if ($user && $pref = $user->prayerPreference) {
            $raw = ['method' => $pref->method?->key ?? $default, 'asr' => $pref->asr_method?->value, 'offsets' => $pref->offsets_json ?? []];
        } else {
            $raw = $this->cookieJson(self::COOKIE_PRAYER);
        }
        $methods = $this->methods();

        return [
            'method' => isset($methods[$raw['method'] ?? '']) ? $raw['method'] : (isset($methods[$default]) ? $default : (array_key_first($methods) ?? 'kemenag')),
            'asr' => AsrMethod::tryFrom((string) ($raw['asr'] ?? ''))?->value ?? AsrMethod::Standard->value,
            'offsets' => PrayerParameters::cleanOffsets(is_array($raw['offsets'] ?? null) ? $raw['offsets'] : []),
        ];
    }

    public function prayerParameters(): PrayerParameters
    {
        $s = $this->prayerSettings();
        $m = $this->methods()[$s['method']] ?? null;
        if (! $m) {
            return new PrayerParameters(offsets: $s['offsets']);
        }

        return PrayerParameters::fromMethod($m['key'], $m['name'], $m['parameters'], AsrMethod::from($s['asr'])->shadowFactor(), $s['offsets']);
    }

    /** @param array<string, mixed> $values */
    public function savePrayer(array $values): array
    {
        $methods = $this->methods();
        $clean = [
            'method' => isset($methods[$values['method'] ?? '']) ? $values['method'] : $this->prayerSettings()['method'],
            'asr' => AsrMethod::tryFrom((string) ($values['asr'] ?? ''))?->value ?? AsrMethod::Standard->value,
            'offsets' => PrayerParameters::cleanOffsets((array) ($values['offsets'] ?? [])),
        ];
        if ($user = $this->user()) {
            $user->prayerPreference()->updateOrCreate([], [
                'method_id' => $methods[$clean['method']]['id'] ?? null,
                'asr_method' => $clean['asr'],
                'offsets_json' => $clean['offsets'],
            ]);
            $user->unsetRelation('prayerPreference');
        } else {
            $this->queueCookie(self::COOKIE_PRAYER, $clean);
        }

        return $clean;
    }

    /** @return array<string, array{id: int, key: string, name: string, parameters: array, source: string}> */
    public function methods(): array
    {
        return Cache::remember('prayer_methods', 600, function () {
            try {
                return PrayerMethod::query()->where('is_active', true)->orderBy('sort_order')->get()
                    ->mapWithKeys(fn ($m) => [$m->key => ['id' => $m->id, 'key' => $m->key, 'name' => $m->name, 'parameters' => $m->parameters_json, 'source' => $m->source]])
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    // ----------------------------------------------------------------- beranda

    /** @return array{order: array<int, string>, hidden: array<int, string>} */
    public function homeCards(): array
    {
        $raw = $this->user()?->profile?->dashboard_cards_json ?? $this->cookieJson(self::COOKIE_HOME);

        return self::sanitizeHome($raw);
    }

    /** @param array<string, mixed> $in */
    public static function sanitizeHome(array $in): array
    {
        $keys = array_keys(self::HOME_CARDS);
        $order = array_values(array_unique(array_filter((array) ($in['order'] ?? []), fn ($k) => in_array($k, $keys, true))));
        foreach ($keys as $k) {
            if (! in_array($k, $order, true)) {
                $order[] = $k;
            }
        }
        $hidden = array_values(array_intersect($keys, (array) ($in['hidden'] ?? [])));

        return ['order' => $order, 'hidden' => $hidden];
    }

    /** @param array<string, mixed> $values */
    public function saveHome(array $values): array
    {
        $clean = self::sanitizeHome($values);
        if ($user = $this->user()) {
            $user->profile()->updateOrCreate([], ['dashboard_cards_json' => $clean]);
            $user->unsetRelation('profile');
        } else {
            $this->queueCookie(self::COOKIE_HOME, $clean);
        }

        return $clean;
    }

    // ----------------------------------------------------------------- helper

    /** @return array<string, mixed> */
    private function cookieJson(string $name): array
    {
        $raw = $this->request()->cookie($name);
        if (! is_string($raw) || strlen($raw) > 2000) {
            return [];
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function queueCookie(string $name, array $value): void
    {
        // httpOnly=false agar JS dapat menerapkan pratinjau; isinya hanya pilihan tampilan.
        Cookie::queue(Cookie::make($name, json_encode($value), 60 * 24 * 365, '/', null, null, false, false, 'lax'));
    }
}
