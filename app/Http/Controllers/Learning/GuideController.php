<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\GuideCategory;
use App\Models\GuideItem;
use App\Models\QuranVerse;

/**
 * Panduan Tajwid, Umroh, Haji, Ziarah — hanya materi berstatus terbit.
 * @2026-Alifiandra
 */
class GuideController extends Controller
{
    public function index()
    {
        $counts = GuideItem::query()->published()->join('guide_categories', 'guide_categories.id', '=', 'guide_items.category_id')
            ->selectRaw('guide_categories.module, count(*) c')->groupBy('guide_categories.module')->pluck('c', 'module');

        return view('pages.guide.index', ['modules' => GuideCategory::MODULES, 'counts' => $counts]);
    }

    public function module(string $modul)
    {
        abort_unless(isset(GuideCategory::MODULES[$modul]), 404);
        $categories = GuideCategory::query()->where('module', $modul)->orderBy('sort_order')
            ->with(['items' => fn ($q) => $q->published()])->get()->filter(fn ($c) => $c->items->isNotEmpty())->values();

        return view('pages.guide.module', ['modul' => $modul, 'meta' => GuideCategory::MODULES[$modul], 'categories' => $categories]);
    }

    public function show(string $modul, string $slug)
    {
        abort_unless(isset(GuideCategory::MODULES[$modul]), 404);
        $item = GuideItem::query()->published()->where('slug', $slug)
            ->whereHas('category', fn ($q) => $q->where('module', $modul))->with(['category', 'source'])->firstOrFail();

        // Urutan materi dalam modul untuk navigasi sebelumnya/berikutnya
        $sequence = GuideItem::query()->published()->join('guide_categories', 'guide_categories.id', '=', 'guide_items.category_id')
            ->where('guide_categories.module', $modul)->orderBy('guide_categories.sort_order')->orderBy('guide_items.sort_order')
            ->get(['guide_items.slug', 'guide_items.title'])->values();
        $pos = $sequence->search(fn ($r) => $r->slug === $item->slug);

        $verse = null;
        if ($ref = $item->exampleRef()) {
            $verse = QuranVerse::query()->with(['surah', 'translationId'])->where('ayah_number', $ref[1])
                ->whereHas('surah', fn ($q) => $q->where('number', $ref[0]))->first();
        }

        return view('pages.guide.show', [
            'modul' => $modul,
            'meta' => GuideCategory::MODULES[$modul],
            'item' => $item,
            'verse' => $verse,
            'prev' => $pos > 0 ? $sequence[$pos - 1] : null,
            'next' => $pos !== false && $pos < $sequence->count() - 1 ? $sequence[$pos + 1] : null,
            'position' => $pos === false ? null : $pos + 1,
            'total' => $sequence->count(),
        ]);
    }
}
