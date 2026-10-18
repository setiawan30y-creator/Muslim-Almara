<?php

namespace App\Http\Controllers\Worship;

use App\Http\Controllers\Controller;
use App\Models\DhikrCategory;
use App\Models\DhikrItem;
use App\Models\DuaCategory;
use App\Models\DuaItem;
use Illuminate\Http\Request;

/**
 * Dzikir & Doa — hanya konten berstatus terbit (blueprint §4.4, Standar Wajib Bacaan).
 * @2026-Alifiandra
 */
class DoaDzikirController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'doa' ? 'doa' : 'dzikir';
        $load = fn ($class) => $class::query()->orderBy('sort_order')
            ->with(['items' => fn ($q) => $q->published()->with('source:id,name,attribution')])
            ->get()->filter(fn ($c) => $c->items->isNotEmpty())->values();

        return view('pages.dhikr.index', [
            'tab' => $tab,
            'dzikir' => $load(DhikrCategory::class),
            'doa' => $load(DuaCategory::class),
        ]);
    }

    public function show(string $type, string $slug)
    {
        $class = $type === 'doa' ? DuaItem::class : DhikrItem::class;
        $item = $class::query()->published()->where('slug', $slug)->with(['category', 'source', 'reviewer:id,name'])->firstOrFail();

        return view('pages.dhikr.show', ['item' => $item, 'type' => $type]);
    }
}
