<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ContentRepository;
use App\Services\HijriCalendar;
use App\Services\PrayerTimeService;
use App\Services\QiblaService;
use App\Support\Location;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 — dipakai juga oleh aplikasi Flutter nantinya.
 * Format: {"success": true, "data": ...}
 *
 * @2026-Alifiandra
 */
class ApiController extends Controller
{
    public function __construct(private ContentRepository $content)
    {
    }

    public function prayerTimes(Request $request, PrayerTimeService $prayer, HijriCalendar $hijri): JsonResponse
    {
        $request->validate([
            'kota' => ['nullable', 'string', 'max:60'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'tz' => ['nullable', 'numeric', 'between:-12,14'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $loc = Location::resolve($request, $this->content, remember: false);
        $date = $request->filled('tanggal') ? CarbonImmutable::createFromFormat('!Y-m-d', $request->query('tanggal')) : $prayer->nowAt($loc['tz'])->startOfDay();

        return $this->ok([
            'lokasi' => $loc,
            'zona' => $prayer->tzLabel($loc['tz']),
            'tanggal' => $date->format('Y-m-d'),
            'hijriah' => $hijri->fromGregorian($date),
            'jadwal' => $prayer->forDate($date, $loc['lat'], $loc['lng'], $loc['tz']),
            'metode' => 'Kemenag RI (Subuh 20°, Isya 18°, ihtiyat '.config('muslim.prayer.ihtiyat').' menit)',
        ]);
    }

    public function cities(): JsonResponse
    {
        return $this->ok($this->content->cities());
    }

    public function surahs(): JsonResponse
    {
        return $this->ok($this->content->surahs());
    }

    public function surah(int $nomor, Request $request): JsonResponse
    {
        $surah = $this->content->surah($nomor);
        if (! $surah) {
            return response()->json(['success' => false, 'message' => 'Surah tidak ditemukan.'], 404);
        }
        if (! $request->boolean('tafsir')) {
            $surah['ayat'] = array_map(fn ($a) => array_diff_key($a, ['tafsir' => 1]), $surah['ayat']);
        }

        return $this->ok($surah);
    }

    public function doa(): JsonResponse
    {
        return $this->ok($this->content->doa());
    }

    public function asmaulHusna(): JsonResponse
    {
        return $this->ok($this->content->asmaulHusna());
    }

    public function hijri(Request $request, HijriCalendar $hijri): JsonResponse
    {
        $request->validate(['tanggal' => ['nullable', 'date_format:Y-m-d']]);
        $date = $request->filled('tanggal') ? CarbonImmutable::createFromFormat('!Y-m-d', $request->query('tanggal')) : CarbonImmutable::now()->startOfDay();

        return $this->ok(['masehi' => $date->format('Y-m-d'), 'hijriah' => $hijri->fromGregorian($date), 'hari_besar' => array_map(fn ($e) => [...$e, 'tanggal' => $e['tanggal']->format('Y-m-d')], $hijri->upcomingEvents($date, 5))]);
    }

    public function qibla(Request $request, QiblaService $qibla): JsonResponse
    {
        $data = $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lng' => ['required', 'numeric', 'between:-180,180']]);
        $deg = $qibla->bearing((float) $data['lat'], (float) $data['lng']);

        return $this->ok(['arah_derajat' => round($deg, 2), 'arah' => $qibla->compassLabel($deg), 'jarak_km' => round($qibla->distanceKm((float) $data['lat'], (float) $data['lng']), 1)]);
    }

    private function ok(mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
