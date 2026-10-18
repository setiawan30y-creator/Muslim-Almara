<?php

namespace App\Http\Controllers;

use App\Models\ContentSource;
use App\Models\PrayerMethod;

// Halaman informasi. @2026-Alifiandra
class PageController extends Controller
{
    public function about()
    {
        return view('pages.static.tentang', [
            'sources' => ContentSource::query()->orderBy('content_type')->get(),
            'methods' => PrayerMethod::query()->where('is_active', true)->orderBy('sort_order')->get(['name', 'source']),
        ]);
    }
}
