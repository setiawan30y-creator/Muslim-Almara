<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AccentColor;
use App\Enums\AsrMethod;
use App\Enums\QuranReadingMode;
use App\Enums\TextSize;
use App\Enums\ThemeMode;
use App\Http\Controllers\Controller;
use App\Services\Prayer\PrayerParameters;
use App\Services\Preferences\PreferenceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengaturan → Tampilan & Tema, Al-Qur'an, Jadwal Sholat, Beranda.
 * Tamu: tersimpan di perangkat. Login: tersimpan di akun & tersinkron.
 * @2026-Alifiandra
 */
class SettingsController extends Controller
{
    public function __construct(private PreferenceService $prefs)
    {
    }

    public function index()
    {
        return view('pages.settings.index');
    }

    public function appearance()
    {
        return view('pages.settings.appearance', ['current' => $this->prefs->appearance()]);
    }

    public function saveAppearance(Request $request)
    {
        $sizes = array_map(fn ($s) => $s->value, TextSize::cases());
        $data = $request->validate([
            'theme_mode' => ['required', Rule::enum(ThemeMode::class)],
            'accent_color' => ['required', Rule::enum(AccentColor::class)],
            'interface_text_size' => ['required', Rule::in(array_map(fn ($s) => $s->value, TextSize::interface()))],
            'reading_text_size' => ['required', Rule::in($sizes)],
            'translation_text_size' => ['required', Rule::in($sizes)],
        ]);
        $saved = $this->prefs->saveAppearance($data);

        return $request->expectsJson()
            ? response()->json(['data' => $saved, 'meta' => ['stored' => $request->user() ? 'account' : 'device']])
            : back()->with('status', 'Tampilan disimpan.');
    }

    public function quran()
    {
        return view('pages.settings.quran', [
            'current' => $this->prefs->quran(),
            'mushafAvailable' => PreferenceService::mushafAvailable(),
            'reciters' => app(\App\Services\Quran\Audio\AudioCatalog::class)->reciters(),
        ]);
    }

    public function saveQuran(Request $request)
    {
        $data = $request->validate([
            'reading_mode' => ['sometimes', Rule::enum(QuranReadingMode::class)],
            'show_translation' => ['sometimes', 'boolean'],
            'show_transliteration' => ['sometimes', 'boolean'],
            'show_tafsir' => ['sometimes', 'boolean'],
            'audio_mode' => ['sometimes', Rule::in(['surah', 'verse'])],
            'reciter' => ['sometimes', 'nullable', 'string', 'regex:'.\App\Services\Quran\Audio\AudioCatalog::KEY_PATTERN],
        ]);
        // checkbox yang tidak dicentang tidak terkirim pada form biasa
        if (! $request->expectsJson()) {
            foreach (['show_translation', 'show_transliteration', 'show_tafsir'] as $k) {
                $data[$k] = $request->boolean($k);
            }
        }
        $saved = $this->prefs->saveQuran($data);

        return $request->expectsJson()
            ? response()->json(['data' => $saved])
            : back()->with('status', "Pengaturan Al-Qur'an disimpan.");
    }

    public function prayer()
    {
        return view('pages.settings.prayer', [
            'current' => $this->prefs->prayerSettings(),
            'methods' => $this->prefs->methods(),
            'params' => $this->prefs->prayerParameters(),
        ]);
    }

    public function savePrayer(Request $request)
    {
        $rules = [
            'method' => ['required', Rule::in(array_keys($this->prefs->methods()))],
            'asr' => ['required', Rule::enum(AsrMethod::class)],
        ];
        foreach (PrayerParameters::OFFSET_KEYS as $k) {
            $rules["offsets.{$k}"] = ['nullable', 'integer', 'between:-30,30'];
        }
        $this->prefs->savePrayer($request->validate($rules, [], ['method' => 'metode', 'asr' => 'mazhab Asar']));

        return back()->with('status', 'Pengaturan jadwal sholat disimpan.');
    }

    public function home()
    {
        return view('pages.settings.home', ['current' => $this->prefs->homeCards()]);
    }

    public function saveHome(Request $request)
    {
        $keys = array_keys(PreferenceService::HOME_CARDS);
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => [Rule::in($keys)],
            'visible' => ['nullable', 'array'],
            'visible.*' => [Rule::in($keys)],
        ]);
        $this->prefs->saveHome(['order' => $data['order'], 'hidden' => array_values(array_diff($keys, $data['visible'] ?? []))]);

        return back()->with('status', 'Susunan beranda disimpan.');
    }
}
