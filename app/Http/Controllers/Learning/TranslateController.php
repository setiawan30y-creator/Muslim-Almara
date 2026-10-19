<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Services\Translate\TranslateService;

// Terjemah & kumpulan frasa (Indonesia–Arab–Inggris). @2026-Alifiandra
class TranslateController extends Controller
{
    public function __invoke(TranslateService $translator)
    {
        $data = json_decode((string) file_get_contents(resource_path('data/frasa.json')), true, flags: JSON_THROW_ON_ERROR);

        return view('pages.translate.index', [
            'phrases' => $data['categories'],
            'source' => $data['source'],
            'enabled' => $translator->enabled(),
            'languages' => $translator->languages(),
            'maxChars' => (int) config('almara.translate.max_chars', 1000),
        ]);
    }
}
