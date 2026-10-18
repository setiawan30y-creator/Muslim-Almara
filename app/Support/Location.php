<?php

namespace App\Support;

use App\Services\Content\StaticContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Menentukan lokasi pengguna: query (?kota= atau ?lat=&lng=&tz=) → cookie → kota bawaan.
 * Lokasi hanya disimpan di cookie peramban pengguna (terenkripsi), tidak di database (blueprint §3.2, §6.3).
 * Sumber lokasi ditandai: kota manual, perangkat (GPS), atau bawaan aplikasi.
 *
 * @2026-Alifiandra
 */
class Location
{
    public const COOKIE = 'almara_lokasi';

    /** @return array{nama: string, slug: string|null, lat: float, lng: float, tz: float, custom: bool, basis: string} */
    public static function resolve(Request $request, StaticContent $content, bool $remember = true): array
    {
        $loc = null;

        if ($request->filled('kota') && ($city = $content->city((string) $request->query('kota')))) {
            $loc = self::fromCity($city);
        } elseif ($request->filled(['lat', 'lng'])) {
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $tz = $request->filled('tz') ? (float) $request->query('tz') : round($lng / 15);
                $tz = max(-12, min(14, $tz));
                $loc = ['nama' => 'Lokasi perangkat', 'slug' => null, 'lat' => round($lat, 3), 'lng' => round($lng, 3), 'tz' => $tz, 'custom' => true, 'basis' => 'device'];
            }
        }

        if ($loc && $remember) {
            Cookie::queue(self::COOKIE, json_encode($loc), 60 * 24 * 365);

            return $loc;
        }

        if (! $loc && ($raw = $request->cookie(self::COOKIE))) {
            $data = json_decode((string) $raw, true);
            if (is_array($data) && isset($data['lat'], $data['lng'], $data['tz'], $data['nama'])) {
                $loc = [
                    'nama' => (string) $data['nama'],
                    'slug' => $data['slug'] ?? null,
                    'lat' => (float) $data['lat'],
                    'lng' => (float) $data['lng'],
                    'tz' => (float) $data['tz'],
                    'custom' => (bool) ($data['custom'] ?? false),
                    'basis' => in_array($data['basis'] ?? null, ['device', 'manual'], true) ? $data['basis'] : 'manual',
                ];
            }
        }

        return $loc ?? ['basis' => 'default'] + self::fromCity($content->city(\App\Models\AppSetting::get('location.default_city', config('almara.default_city'))) ?? $content->cities()[0]);
    }

    /** @param array<string, mixed> $city */
    public static function fromCity(array $city): array
    {
        return ['nama' => $city['nama'], 'slug' => $city['slug'], 'lat' => (float) $city['lat'], 'lng' => (float) $city['lng'], 'tz' => (float) $city['tz'], 'custom' => false, 'basis' => 'manual'];
    }
}
