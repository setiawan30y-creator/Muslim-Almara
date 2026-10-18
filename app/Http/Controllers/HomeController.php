<?php

namespace App\Http\Controllers;

use App\Services\ContentRepository;
use App\Services\HijriCalendar;
use App\Services\PrayerTimeService;
use App\Support\Location;
use Illuminate\Http\Request;

// @2026-Alifiandra
class HomeController extends Controller
{
    public function __invoke(Request $request, PrayerTimeService $prayer, HijriCalendar $hijri, ContentRepository $content)
    {
        $loc = Location::resolve($request, $content);
        $now = $prayer->nowAt($loc['tz']);
        $times = $prayer->forDate($now->startOfDay(), $loc['lat'], $loc['lng'], $loc['tz']);

        return view('pages.home', [
            'loc' => $loc,
            'now' => $now,
            'tzLabel' => $prayer->tzLabel($loc['tz']),
            'times' => $times,
            'next' => $prayer->next($now, $loc['lat'], $loc['lng'], $loc['tz']),
            'hijri' => $hijri->fromGregorian($now->startOfDay()),
            'events' => $hijri->upcomingEvents($now, 3),
            'verse' => $content->verseOfTheDay($now->format('Y-m-d')),
        ]);
    }
}
