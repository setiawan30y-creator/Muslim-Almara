<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\WorshipItemResource;
use App\Models\DhikrCategory;
use App\Models\DhikrItem;
use App\Models\DuaCategory;
use App\Models\DuaItem;
use App\Models\IslamicEvent;
use App\Services\Calendar\HijriCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// Dzikir, doa (hanya terbit), dan peristiwa Islam. @2026-Alifiandra
class WorshipContentController extends ApiController
{
    public function categories(string $type)
    {
        $class = $type === 'dua' ? DuaCategory::class : DhikrCategory::class;

        return $this->ok($class::query()->orderBy('sort_order')->withCount(['items' => fn ($q) => $q->published()])->get()
            ->map(fn ($c) => ['slug' => $c->slug, 'title' => $c->title, 'description' => $c->description, 'items_count' => $c->items_count]));
    }

    public function items(Request $request, string $type)
    {
        $data = $request->validate(['category' => ['nullable', 'string', 'max:80'], 'per_page' => ['nullable', 'integer', 'between:1,100']]);
        $class = $type === 'dua' ? DuaItem::class : DhikrItem::class;
        $p = $class::query()->published()->with('category:id,slug')
            ->when($data['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->orderBy('category_id')->orderBy('sort_order')->paginate($this->perPage(50));

        return $this->paginated($p, WorshipItemResource::class);
    }

    public function events(Request $request, HijriCalendar $hijri)
    {
        $from = CarbonImmutable::now();

        return $this->ok([
            'events' => IslamicEvent::query()->orderBy('hijri_month')->orderBy('hijri_day')->get(['title', 'hijri_month', 'hijri_day', 'date_basis', 'description']),
            'upcoming' => array_map(fn ($e) => ['title' => $e['nama'], 'hijri' => $e['hijri'], 'date' => $e['tanggal']->format('Y-m-d'), 'days_left' => $e['sisa_hari']],
                $hijri->upcomingEvents($from, 5)),
        ], ['date_basis' => 'calculated', 'note' => 'Tanggal dihitung (urfi); dapat berbeda ±1 hari dari penetapan resmi.']);
    }
}
