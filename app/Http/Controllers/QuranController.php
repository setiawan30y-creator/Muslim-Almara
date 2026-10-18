<?php

namespace App\Http\Controllers;

use App\Services\ContentRepository;

// @2026-Alifiandra
class QuranController extends Controller
{
    public function index(ContentRepository $content)
    {
        return view('pages.quran.index', ['surahs' => $content->surahs()]);
    }

    public function show(int $nomor, ContentRepository $content)
    {
        $surah = $content->surah($nomor);
        abort_if(! $surah, 404);

        return view('pages.quran.show', [
            'surah' => $surah,
            'prev' => $content->surahMeta($nomor - 1),
            'next' => $content->surahMeta($nomor + 1),
        ]);
    }
}
