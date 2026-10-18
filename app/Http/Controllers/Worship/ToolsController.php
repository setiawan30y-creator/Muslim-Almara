<?php

namespace App\Http\Controllers\Worship;

use App\Http\Controllers\Controller;
use App\Models\DhikrItem;
use App\Services\Calendar\HijriCalendar;
use App\Services\Content\StaticContent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// Tasbih, kalender Hijriah, zakat, Asmaul Husna. @2026-Alifiandra
class ToolsController extends Controller
{
    public function tasbih()
    {
        $items = DhikrItem::query()->published()->whereNotNull('count_target')->where('count_target', '>', 1)
            ->orderBy('sort_order')->get(['slug', 'title', 'arabic_text', 'transliteration', 'translation', 'count_target', 'source_reference']);

        return view('pages.tools.tasbih', ['items' => $items]);
    }

    public function zakat()
    {
        return view('pages.tools.zakat');
    }

    public function asmaulHusna(StaticContent $content)
    {
        return view('pages.tools.asmaul-husna', ['items' => $content->asmaulHusna()]);
    }

    public function calendar(Request $request, HijriCalendar $hijri)
    {
        $month = CarbonImmutable::now()->startOfMonth();
        if (preg_match('/^(\d{4})-(\d{2})$/', (string) $request->query('bulan'), $m) && checkdate((int) $m[2], 1, (int) $m[1]) && $m[1] >= 1900 && $m[1] <= 2200) {
            $month = CarbonImmutable::create((int) $m[1], (int) $m[2], 1);
        }

        $eventIndex = [];
        foreach (HijriCalendar::EVENTS as [$hm, $hd, $name]) {
            $eventIndex["{$hm}-{$hd}"] = $name;
        }

        $days = [];
        for ($i = 0; $i < $month->daysInMonth; $i++) {
            $date = $month->addDays($i);
            $h = $hijri->fromGregorian($date);
            $days[] = ['date' => $date, 'hijri' => $h, 'event' => $eventIndex["{$h['month']}-{$h['day']}"] ?? null];
        }
        $first = $days[0]['hijri'];
        $last = end($days)['hijri'];

        return view('pages.tools.calendar', [
            'month' => $month,
            'days' => $days,
            'offset' => (int) $month->dayOfWeekIso - 1,
            'hijriRange' => $first['month'] === $last['month']
                ? "{$first['month_name']} {$first['year']} H"
                : "{$first['month_name']}".($first['year'] !== $last['year'] ? " {$first['year']}" : '')." – {$last['month_name']} {$last['year']} H",
            'today' => CarbonImmutable::now()->startOfDay(),
            'events' => $hijri->upcomingEvents(CarbonImmutable::now(), 6),
        ]);
    }
}
