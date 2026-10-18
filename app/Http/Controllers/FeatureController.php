<?php

namespace App\Http\Controllers;

use App\Services\ContentRepository;
use App\Services\HijriCalendar;
use App\Services\PrayerTimeService;
use App\Services\QiblaService;
use App\Support\Location;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// @2026-Alifiandra
class FeatureController extends Controller
{
    public function qibla(Request $request, QiblaService $qibla, ContentRepository $content)
    {
        $loc = Location::resolve($request, $content);
        $deg = $qibla->bearing($loc['lat'], $loc['lng']);

        return view('pages.qibla', [
            'loc' => $loc,
            'bearing' => $deg,
            'label' => $qibla->compassLabel($deg),
            'distance' => $qibla->distanceKm($loc['lat'], $loc['lng']),
        ]);
    }

    public function doa(ContentRepository $content)
    {
        $items = collect($content->doa());

        return view('pages.doa', ['items' => $items, 'categories' => $items->pluck('kategori_label', 'kategori')->unique()]);
    }

    public function asmaulHusna(ContentRepository $content)
    {
        return view('pages.asmaul-husna', ['items' => $content->asmaulHusna()]);
    }

    public function tasbih()
    {
        return view('pages.tasbih');
    }

    public function zakat()
    {
        return view('pages.zakat');
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

        return view('pages.calendar', [
            'month' => $month,
            'days' => $days,
            'offset' => (int) $month->dayOfWeekIso - 1, // Senin = 0
            'hijriRange' => $first['month'] === $last['month']
                ? "{$first['month_name']} {$first['year']} H"
                : "{$first['month_name']}".($first['year'] !== $last['year'] ? " {$first['year']}" : '')." – {$last['month_name']} {$last['year']} H",
            'today' => CarbonImmutable::now()->startOfDay(),
            'events' => $hijri->upcomingEvents(CarbonImmutable::now(), 6),
        ]);
    }

    public function menu()
    {
        return view('pages.menu');
    }

    public function sitemap(ContentRepository $content)
    {
        $urls = collect(['/', '/jadwal-sholat', '/al-quran', '/kiblat', '/doa', '/asmaul-husna', '/tasbih', '/kalender', '/zakat', '/tentang', '/kebijakan-privasi', '/syarat-ketentuan'])
            ->merge(collect($content->surahs())->map(fn ($s) => '/al-quran/'.$s['nomor']))
            ->map(fn ($u) => url($u));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
