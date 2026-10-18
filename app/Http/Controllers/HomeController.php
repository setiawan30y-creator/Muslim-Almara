<?php

namespace App\Http\Controllers;

use App\Models\IslamicEvent;
use App\Services\Calendar\HijriCalendar;
use App\Services\Content\StaticContent;
use App\Services\Prayer\PrayerTimeCalculator;
use App\Services\Preferences\PreferenceService;
use App\Services\Quran\QuranService;
use App\Services\UserProgress\ReadingProgressService;
use App\Support\Location;
use Illuminate\Http\Request;

/**
 * Dashboard (blueprint §8.3). Kartu dapat diurutkan & disembunyikan pengguna.
 * @2026-Alifiandra
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, PrayerTimeCalculator $prayer, HijriCalendar $hijri, StaticContent $content,
        PreferenceService $prefs, QuranService $quran, ReadingProgressService $progress)
    {
        $loc = Location::resolve($request, $content);
        $params = $prefs->prayerParameters();
        $now = $prayer->nowAt($loc['tz']);
        $user = $request->user();

        return view('pages.home.index', [
            'loc' => $loc,
            'now' => $now,
            'tzLabel' => $prayer->tzLabel($loc['tz']),
            'params' => $params,
            'times' => $prayer->forDate($now->startOfDay(), $loc['lat'], $loc['lng'], $loc['tz'], $params),
            'next' => $prayer->next($now, $loc['lat'], $loc['lng'], $loc['tz'], $params),
            'hijri' => $hijri->fromGregorian($now->startOfDay()),
            'events' => $hijri->upcomingEvents($now, 3),
            'verse' => $quran->verseOfTheDay($now->format('Y-m-d')),
            'cards' => $prefs->homeCards(),
            'lastRead' => $user?->lastRead()->with('verse.surah')->first(),
            'stats' => $user ? $progress->stats($user, $now) : null,
            'notes' => $user ? $user->notes()->with('verse.surah')->latest('updated_at')->limit(3)->get() : collect(),
            'eventCount' => IslamicEvent::query()->count(),
        ]);
    }
}
