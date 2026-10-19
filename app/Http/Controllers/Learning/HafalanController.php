<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Services\Memorization\MemorizationService;
use App\Services\Quran\QuranService;
use Illuminate\Http\Request;

/**
 * Hafalan & Murojaah. Latihan (sembunyikan teks) terbuka untuk semua;
 * penilaian & jadwal murojaah tersimpan di akun (data pribadi).
 * @2026-Alifiandra
 */
class HafalanController extends Controller
{
    public function __construct(private QuranService $quran)
    {
    }

    public function index(Request $request, MemorizationService $mem)
    {
        $user = $request->user();

        return view('pages.hafalan.index', [
            'surahs' => $this->quran->surahs(),
            'stats' => $user ? $mem->stats($user) : null,
            'perSurah' => $user ? $mem->perSurah($user) : collect(),
        ]);
    }

    public function practice(Request $request, int $nomor, MemorizationService $mem)
    {
        $surah = $this->quran->surah($nomor);
        abort_if(! $surah, 404);
        $data = $request->validate([
            'dari' => ['nullable', 'integer', 'min:1', 'max:'.$surah->ayah_count],
            'sampai' => ['nullable', 'integer', 'min:1', 'max:'.$surah->ayah_count],
            'murojaah' => ['nullable', 'boolean'],
        ]);
        $from = (int) ($data['dari'] ?? 1);
        $to = max($from, (int) ($data['sampai'] ?? $surah->ayah_count));
        $user = $request->user();

        $verses = $this->quran->verses($surah)->filter(fn ($v) => $v->ayah_number >= $from && $v->ayah_number <= $to)->values();
        $states = $user ? $mem->forVerses($user, $verses->pluck('id')) : [];
        $reviewOnly = $user && $request->boolean('murojaah');
        if ($reviewOnly) {
            $verses = $verses->filter(fn ($v) => isset($states[$v->id]) && $states[$v->id]->isDue())->values();
        }

        return view('pages.hafalan.practice', [
            'surah' => $surah,
            'verses' => $verses,
            'states' => $states,
            'from' => $from,
            'to' => $to,
            'reviewOnly' => $reviewOnly,
            'prev' => $this->quran->surah($nomor - 1),
            'next' => $this->quran->surah($nomor + 1),
        ]);
    }
}
