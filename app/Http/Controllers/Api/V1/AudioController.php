<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Preferences\PreferenceService;
use App\Services\Quran\Audio\AudioCatalog;
use Illuminate\Http\Request;

/**
 * GET /api/v1/quran/audio/reciters          — daftar qari (opsional ?surah=)
 * GET /api/v1/quran/audio/surah/{surah}     — URL audio + penanda ayat (?reciter=almara:slug|qf:id)
 * Respons tidak disimpan cache (data penyedia pihak ketiga dibatasi masa simpannya).
 * @2026-Alifiandra
 */
class AudioController extends ApiController
{
    public function reciters(Request $request, AudioCatalog $catalog)
    {
        $data = $request->validate(['surah' => ['nullable', 'integer', 'between:1,114']]);
        $surah = isset($data['surah']) ? (int) $data['surah'] : null;

        return $this->ok($catalog->reciters($surah), [
            'default' => $catalog->resolve(app(PreferenceService::class)->quran()['reciter'], $surah),
        ])->header('Cache-Control', 'no-store');
    }

    public function surah(Request $request, int $surah, PreferenceService $prefs, AudioCatalog $catalog)
    {
        abort_if($surah < 1 || $surah > 114, 404, 'Surah tidak ditemukan.');
        $data = $request->validate(['reciter' => ['nullable', 'string', 'regex:'.AudioCatalog::KEY_PATTERN]]);
        $key = $data['reciter'] ?? $catalog->resolve($prefs->quran()['reciter'], $surah);
        $audio = $key ? $catalog->forSurah($key, $surah) : null;
        if (! $audio) {
            return response()->json(['message' => 'Audio untuk surah ini belum tersedia pada qari yang dipilih.', 'errors' => (object) []], 404)
                ->header('Cache-Control', 'no-store');
        }

        return $this->ok($audio)->header('Cache-Control', 'no-store');
    }
}
