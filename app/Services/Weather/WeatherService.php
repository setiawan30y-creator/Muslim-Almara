<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cuaca terkini & prakiraan singkat dari Open-Meteo (https://open-meteo.com, data CC BY 4.0).
 * Dipanggil dari server dan di-cache per koordinat (dibulatkan ±1 km) agar hemat kuota
 * dan alamat IP pengguna tidak dikirim ke penyedia.
 * @2026-Alifiandra
 */
class WeatherService
{
    /** Kode cuaca WMO → [label, ikon]. */
    public const CODES = [
        0 => ['Cerah', 'sun'], 1 => ['Cerah berawan', 'cloud-sun'], 2 => ['Berawan', 'cloud-sun'], 3 => ['Mendung', 'cloud'],
        45 => ['Berkabut', 'fog'], 48 => ['Berkabut', 'fog'],
        51 => ['Gerimis ringan', 'rain'], 53 => ['Gerimis', 'rain'], 55 => ['Gerimis lebat', 'rain'], 56 => ['Gerimis beku', 'rain'], 57 => ['Gerimis beku', 'rain'],
        61 => ['Hujan ringan', 'rain'], 63 => ['Hujan sedang', 'rain'], 65 => ['Hujan lebat', 'rain'], 66 => ['Hujan beku', 'rain'], 67 => ['Hujan beku', 'rain'],
        71 => ['Salju ringan', 'cloud'], 73 => ['Salju', 'cloud'], 75 => ['Salju lebat', 'cloud'], 77 => ['Butiran salju', 'cloud'],
        80 => ['Hujan lokal ringan', 'rain'], 81 => ['Hujan lokal', 'rain'], 82 => ['Hujan lokal lebat', 'storm'],
        85 => ['Hujan salju', 'cloud'], 86 => ['Hujan salju lebat', 'cloud'],
        95 => ['Badai petir', 'storm'], 96 => ['Badai petir disertai es', 'storm'], 99 => ['Badai petir disertai es', 'storm'],
    ];

    public function enabled(): bool
    {
        return (bool) config('almara.weather.enabled', true);
    }

    public function commercialKey(): bool
    {
        return (bool) config('almara.weather.api_key');
    }

    private function endpoint(): string
    {
        if ($url = config('almara.weather.url')) {
            return $url;
        }

        return $this->commercialKey() ? 'https://customer-api.open-meteo.com/v1/forecast' : 'https://api.open-meteo.com/v1/forecast';
    }

    public static function describe(?int $code, bool $isDay = true): array
    {
        [$label, $icon] = self::CODES[$code ?? -1] ?? ['Tidak diketahui', 'cloud'];
        if (! $isDay && in_array($icon, ['sun', 'cloud-sun'], true)) {
            $icon = 'moon';
        }

        return ['label' => $label, 'icon' => $icon];
    }

    /** @return array<string, mixed>|null */
    public function forecast(float $lat, float $lng): ?array
    {
        if (! $this->enabled() || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }
        $lat = round($lat, 2);
        $lng = round($lng, 2);
        $key = "weather:{$lat}:{$lng}";
        if (is_array($hit = Cache::get($key))) {
            return $hit;
        }

        try {
            $res = Http::acceptJson()->timeout((int) config('almara.weather.timeout', 6))->get($this->endpoint(), array_filter([
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,weather_code,wind_speed_10m,is_day',
                'hourly' => 'temperature_2m,precipitation_probability,weather_code,is_day',
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max,uv_index_max',
                'timezone' => 'auto',
                'forecast_days' => 2,
                'apikey' => config('almara.weather.api_key'),
            ]));
        } catch (\Throwable $e) {
            Log::warning('Cuaca: gagal menghubungi penyedia', ['error' => $e->getMessage()]);

            return null;
        }
        if (! $res->successful() || ! is_array($res->json('current'))) {
            Log::warning('Cuaca: respons tidak valid', ['status' => $res->status()]);

            return null;
        }

        $out = $this->shape($res->json());
        Cache::put($key, $out, max(300, (int) config('almara.weather.cache_seconds', 1800)));

        return $out;
    }

    /** @param array<string, mixed> $j */
    private function shape(array $j): array
    {
        $c = $j['current'];
        $now = (string) ($c['time'] ?? '');
        $hours = [];
        $h = $j['hourly'] ?? [];
        foreach ((array) ($h['time'] ?? []) as $i => $t) {
            if ($t <= $now) {
                continue;
            }
            $hours[] = [
                'time' => substr($t, 11, 5),
                'temp' => isset($h['temperature_2m'][$i]) ? (int) round($h['temperature_2m'][$i]) : null,
                'rain' => $h['precipitation_probability'][$i] ?? null,
            ] + self::describe($h['weather_code'][$i] ?? null, (bool) ($h['is_day'][$i] ?? 1));
            if (count($hours) === 6) {
                break;
            }
        }
        $d = $j['daily'] ?? [];

        return [
            'current' => [
                'temp' => (int) round((float) ($c['temperature_2m'] ?? 0)),
                'feels_like' => isset($c['apparent_temperature']) ? (int) round($c['apparent_temperature']) : null,
                'humidity' => $c['relative_humidity_2m'] ?? null,
                'wind' => isset($c['wind_speed_10m']) ? (int) round($c['wind_speed_10m']) : null,
                'time' => substr($now, 11, 5),
            ] + self::describe(isset($c['weather_code']) ? (int) $c['weather_code'] : null, (bool) ($c['is_day'] ?? 1)),
            'today' => [
                'max' => isset($d['temperature_2m_max'][0]) ? (int) round($d['temperature_2m_max'][0]) : null,
                'min' => isset($d['temperature_2m_min'][0]) ? (int) round($d['temperature_2m_min'][0]) : null,
                'rain' => $d['precipitation_probability_max'][0] ?? null,
                'uv' => isset($d['uv_index_max'][0]) ? round((float) $d['uv_index_max'][0], 1) : null,
            ],
            'hours' => $hours,
            'attribution' => 'Data cuaca: Open-Meteo.com (CC BY 4.0)',
            'fetched_at' => now()->toIso8601String(),
        ];
    }
}
