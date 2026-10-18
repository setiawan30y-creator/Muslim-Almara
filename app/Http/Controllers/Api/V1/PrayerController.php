<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AsrMethod;
use App\Services\Calendar\HijriCalendar;
use App\Services\Content\StaticContent;
use App\Services\Prayer\PrayerParameters;
use App\Services\Prayer\PrayerTimeCalculator;
use App\Services\Preferences\PreferenceService;
use App\Services\Qibla\QiblaService;
use App\Support\Location;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Jadwal sholat & kiblat. @2026-Alifiandra
class PrayerController extends ApiController
{
    public function methods(PreferenceService $prefs)
    {
        return $this->ok(array_values(array_map(fn ($m) => [
            'key' => $m['key'], 'name' => $m['name'], 'parameters' => $m['parameters'], 'source' => $m['source'],
        ], $prefs->methods())));
    }

    public function times(Request $request, PrayerTimeCalculator $calc, HijriCalendar $hijri, StaticContent $content, PreferenceService $prefs)
    {
        $methods = $prefs->methods();
        $rules = [
            'city' => ['nullable', 'string', 'max:60'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'tz' => ['nullable', 'numeric', 'between:-12,14'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'method' => ['nullable', Rule::in(array_keys($methods))],
            'asr' => ['nullable', Rule::enum(AsrMethod::class)],
        ];
        foreach (PrayerParameters::OFFSET_KEYS as $k) {
            $rules["offsets.{$k}"] = ['nullable', 'integer', 'between:-30,30'];
        }
        $data = $request->validate($rules);
        if (! empty($data['city'])) {
            $request->query->set('kota', $data['city']);
        }

        $loc = Location::resolve($request, $content, remember: false);
        $p = $prefs->prayerParameters();
        if (! empty($data['method']) || ! empty($data['asr']) || ! empty($data['offsets'])) {
            $s = $prefs->prayerSettings();
            $m = $methods[$data['method'] ?? $s['method']];
            $p = PrayerParameters::fromMethod($m['key'], $m['name'], $m['parameters'],
                AsrMethod::from($data['asr'] ?? $s['asr'])->shadowFactor(), $data['offsets'] ?? $s['offsets']);
        }
        $date = ! empty($data['date']) ? CarbonImmutable::createFromFormat('!Y-m-d', $data['date']) : $calc->nowAt($loc['tz'])->startOfDay();

        return $this->ok([
            'date' => $date->format('Y-m-d'),
            'hijri' => $hijri->fromGregorian($date),
            'timezone' => $calc->tzLabel($loc['tz']),
            'times' => $calc->forDate($date, $loc['lat'], $loc['lng'], $loc['tz'], $p),
        ], [
            'location' => ['name' => $loc['nama'], 'lat' => $loc['lat'], 'lng' => $loc['lng'], 'basis' => $loc['basis']],
            'method' => ['key' => $p->methodKey, 'name' => $p->methodName, 'description' => $p->describe()],
            'disclaimer' => 'Hasil perhitungan astronomis; dapat berbeda 1–2 menit dengan jadwal resmi setempat.',
        ]);
    }

    public function qibla(Request $request, QiblaService $qibla)
    {
        $d = $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lng' => ['required', 'numeric', 'between:-180,180']]);
        $deg = $qibla->bearing((float) $d['lat'], (float) $d['lng']);

        return $this->ok([
            'bearing' => round($deg, 2),
            'direction' => $qibla->compassLabel($deg),
            'distance_km' => round($qibla->distanceKm((float) $d['lat'], (float) $d['lng']), 1),
        ], ['reference' => 'Azimut great-circle dari utara sejati ke Ka\'bah (21.4225°, 39.8262°)']);
    }
}
