<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Translate\TranslateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/translate {q, source, target} — terjemah teks bebas (bila layanan dikonfigurasi).
 * @2026-Alifiandra
 */
class TranslateController extends ApiController
{
    public function __invoke(Request $request, TranslateService $translator)
    {
        $langs = array_keys($translator->languages());
        $data = $request->validate([
            'q' => ['required', 'string', 'max:'.(int) config('almara.translate.max_chars', 1000)],
            'source' => ['required', Rule::in($langs)],
            'target' => ['required', Rule::in($langs), 'different:source'],
        ], [], ['q' => 'teks', 'source' => 'bahasa asal', 'target' => 'bahasa tujuan']);

        if (! $translator->enabled()) {
            return response()->json(['message' => 'Layanan terjemah teks belum diaktifkan. Gunakan kumpulan frasa.', 'errors' => (object) []], 503);
        }
        $out = $translator->translate(trim($data['q']), $data['source'], $data['target']);
        if ($out === null) {
            return response()->json(['message' => 'Layanan terjemah sedang tidak dapat dihubungi. Coba lagi nanti.', 'errors' => (object) []], 502);
        }

        return $this->ok(['text' => $out], ['provider' => 'libretranslate', 'note' => 'Terjemahan mesin — periksa kembali untuk teks penting.'])->header('Cache-Control', 'no-store');
    }
}
