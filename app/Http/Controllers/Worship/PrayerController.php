<?php

namespace App\Http\Controllers\Worship;

use App\Http\Controllers\Controller;
use App\Services\Calendar\HijriCalendar;
use App\Services\Content\StaticContent;
use App\Services\Prayer\PrayerTimeCalculator;
use App\Services\Preferences\PreferenceService;
use App\Support\Location;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// @2026-Alifiandra
class PrayerController extends Controller
{
    public function index(Request $request, PrayerTimeCalculator $prayer, HijriCalendar $hijri, StaticContent $content, PreferenceService $prefs)
    {
        $loc = Location::resolve($request, $content);
        $params = $prefs->prayerParameters();
        $now = $prayer->nowAt($loc['tz']);

        $month = $now->startOfMonth();
        if (preg_match('/^(\d{4})-(\d{2})$/', (string) $request->query('bulan'), $m) && checkdate((int) $m[2], 1, (int) $m[1]) && $m[1] >= 1900 && $m[1] <= 2200) {
            $month = CarbonImmutable::create((int) $m[1], (int) $m[2], 1);
        }

        $rows = $prayer->forMonth((int) $month->year, (int) $month->month, $loc['lat'], $loc['lng'], $loc['tz'], $params);
        foreach ($rows as &$row) {
            $row['hijri'] = $hijri->fromGregorian($row['tanggal']);
        }

        return view('pages.prayer.index', [
            'loc' => $loc,
            'now' => $now,
            'params' => $params,
            'tzLabel' => $prayer->tzLabel($loc['tz']),
            'today' => $prayer->forDate($now->startOfDay(), $loc['lat'], $loc['lng'], $loc['tz'], $params),
            'next' => $prayer->next($now, $loc['lat'], $loc['lng'], $loc['tz'], $params),
            'month' => $month,
            'rows' => $rows,
        ]);
    }

    public function location(Request $request, StaticContent $content)
    {
        $back = (string) $request->query('kembali', '/');
        if (! str_starts_with($back, '/') || str_starts_with($back, '//') || str_contains($back, '\\')) {
            $back = '/';
        }

        return view('pages.prayer.location', [
            'cities' => $content->cities(),
            'current' => Location::resolve($request, $content, remember: false),
            'back' => $back,
        ]);
    }
}
