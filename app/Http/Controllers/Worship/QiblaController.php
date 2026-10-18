<?php

namespace App\Http\Controllers\Worship;

use App\Http\Controllers\Controller;
use App\Services\Content\StaticContent;
use App\Services\Qibla\QiblaService;
use App\Support\Location;
use Illuminate\Http\Request;

// @2026-Alifiandra
class QiblaController extends Controller
{
    public function __invoke(Request $request, QiblaService $qibla, StaticContent $content)
    {
        $loc = Location::resolve($request, $content);
        $deg = $qibla->bearing($loc['lat'], $loc['lng']);

        return view('pages.qibla.index', [
            'loc' => $loc,
            'bearing' => $deg,
            'label' => $qibla->compassLabel($deg),
            'distance' => $qibla->distanceKm($loc['lat'], $loc['lng']),
        ]);
    }
}
