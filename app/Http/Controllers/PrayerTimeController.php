<?php

namespace App\Http\Controllers;

use App\Services\ContentRepository;
use App\Services\HijriCalendar;
use App\Services\PrayerTimeService;
use App\Support\Location;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// @2026-Alifiandra
class PrayerTimeController extends Controller
{
    public function index(Request $request, PrayerTimeService $prayer, HijriCalendar $hijri, ContentRepository $content)
    {
        $loc = Location::resolve($request, $content);
        $now = $prayer->nowAt($loc['tz']);

        $month = $now->startOfMonth();
        if (preg_match('/^(\d{4})-(\d{2})$/', (string) $request->query('bulan'), $m) && checkdate((int) $m[2], 1, (int) $m[1]) && $m[1] >= 1900 && $m[1] <= 2200) {
            $month = CarbonImmutable::create((int) $m[1], (int) $m[2], 1);
        }

        $rows = $prayer->forMonth((int) $month->year, (int) $month->month, $loc['lat'], $loc['lng'], $loc['tz']);
        foreach ($rows as &$row) {
            $row['hijri'] = $hijri->fromGregorian($row['tanggal']);
        }

        return view('pages.prayer', [
            'loc' => $loc,
            'now' => $now,
            'tzLabel' => $prayer->tzLabel($loc['tz']),
            'today' => $prayer->forDate($now->startOfDay(), $loc['lat'], $loc['lng'], $loc['tz']),
            'next' => $prayer->next($now, $loc['lat'], $loc['lng'], $loc['tz']),
            'month' => $month,
            'rows' => $rows,
        ]);
    }

    public function location(Request $request, ContentRepository $content)
    {
        $back = (string) $request->query('kembali', '/');
        if (! str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }

        return view('pages.location', [
            'cities' => $content->cities(),
            'current' => Location::resolve($request, $content, remember: false),
            'back' => $back,
        ]);
    }
}
