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
    public function index(Request $request, PrayerTimeCalculator $prayer, HijriCalendar $hijri, StaticContent $content, PreferenceService $prefs, ?array $cityLoc = null)
    {
        $loc = $cityLoc ?? Location::resolve($request, $content);
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
            'cityPage' => $cityLoc !== null,
            'cities' => $content->cities(),
        ]);
    }

    /** Halaman jadwal per kota (SEO): /jadwal-sholat/{kota} — tidak mengubah lokasi tersimpan pengguna. */
    public function city(Request $request, string $kota, PrayerTimeCalculator $prayer, HijriCalendar $hijri, StaticContent $content, PreferenceService $prefs)
    {
        $city = $content->city($kota);
        abort_if(! $city, 404);

        return $this->index($request, $prayer, $hijri, $content, $prefs, Location::fromCity($city));
    }

    /** Imsakiyah Ramadan (tahun Hijriah berjalan/berikutnya) untuk lokasi pengguna atau kota tertentu. */
    public function imsakiyah(Request $request, PrayerTimeCalculator $prayer, HijriCalendar $hijri, StaticContent $content, PreferenceService $prefs, ?string $kota = null)
    {
        $city = $kota !== null ? $content->city($kota) : null;
        abort_if($kota !== null && ! $city, 404);
        $loc = $city ? Location::fromCity($city) : Location::resolve($request, $content);
        $now = $prayer->nowAt($loc['tz']);
        $h = $hijri->fromGregorian($now->startOfDay());
        $year = (int) $h['year'];
        $end = $hijri->toGregorian($year, 9, $hijri->daysInMonth($year, 9));
        if ($now->startOfDay()->gt($end)) {
            $year++; // Ramadan tahun ini sudah lewat → tampilkan Ramadan berikutnya
        }
        $start = $hijri->toGregorian($year, 9, 1);
        $days = $hijri->daysInMonth($year, 9);
        $params = $prefs->prayerParameters();
        $rows = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $start->addDays($i);
            $rows[] = ['ramadan' => $i + 1, 'tanggal' => $d, 'waktu' => $prayer->forDate($d, $loc['lat'], $loc['lng'], $loc['tz'], $params)];
        }

        return view('pages.prayer.imsakiyah', [
            'loc' => $loc, 'year' => $year, 'rows' => $rows, 'params' => $params, 'now' => $now,
            'tzLabel' => $prayer->tzLabel($loc['tz']), 'cities' => $content->cities(), 'cityPage' => $city !== null,
        ]);
    }

    /** Kalender puasa sunnah 90 hari ke depan (perhitungan urfi) + hari terlarang berpuasa. */
    public function fasting(Request $request, PrayerTimeCalculator $prayer, HijriCalendar $hijri, StaticContent $content)
    {
        $loc = Location::resolve($request, $content);
        $today = $prayer->nowAt($loc['tz'])->startOfDay();
        $days = [];
        for ($i = 0; $i < 90; $i++) {
            $d = $today->addDays($i);
            $h = $hijri->fromGregorian($d);
            $tags = \App\Services\Calendar\FastingCalendar::classify($d, (int) $h['month'], (int) $h['day']);
            if ($tags) {
                $days[] = ['tanggal' => $d, 'hijri' => $h, 'tags' => $tags];
            }
        }

        return view('pages.prayer.fasting', ['days' => $days, 'kinds' => \App\Services\Calendar\FastingCalendar::KINDS, 'today' => $today]);
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
