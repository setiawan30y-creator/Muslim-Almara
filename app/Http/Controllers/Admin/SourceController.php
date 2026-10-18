<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSource;
use App\Support\Audit;

// Sumber & lisensi konten (blueprint §5). @2026-Alifiandra
class SourceController extends Controller
{
    public function index()
    {
        return view('admin.sources', ['sources' => ContentSource::query()->orderBy('key')->get()]);
    }

    public function markReviewed(ContentSource $source)
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);
        $source->update(['reviewed_at' => now()]);
        Audit::log('source.reviewed', $source, ['key' => $source->key]);

        return back()->with('status', 'Sumber ditandai telah ditinjau.');
    }
}
