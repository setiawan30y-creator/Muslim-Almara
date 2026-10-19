<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Content\StaticContent;
use App\Services\Weather\WeatherService;
use App\Support\Location;
use Illuminate\Http\Request;

/**
 * GET /api/v1/weather[?lat=&lng=] — cuaca lokasi pengguna (bawaan: lokasi tersimpan/kota).
 * @2026-Alifiandra
 */
class WeatherController extends ApiController
{
    public function __invoke(Request $request, WeatherService $weather, StaticContent $content)
    {
        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);
        if (! $weather->enabled()) {
            return response()->json(['message' => 'Fitur cuaca dinonaktifkan.', 'errors' => (object) []], 404);
        }
        $loc = Location::resolve($request, $content, false);
        $lat = isset($data['lat']) ? (float) $data['lat'] : $loc['lat'];
        $lng = isset($data['lng']) ? (float) $data['lng'] : $loc['lng'];
        $out = $weather->forecast($lat, $lng);
        if (! $out) {
            return response()->json(['message' => 'Data cuaca sedang tidak tersedia.', 'errors' => (object) []], 503);
        }

        return $this->ok($out, ['location' => isset($data['lat']) ? 'Lokasi perangkat' : $loc['nama']])
            ->header('Cache-Control', 'public, max-age=600');
    }
}
