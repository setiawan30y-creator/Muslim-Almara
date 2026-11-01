<?php

namespace App\Http\Controllers;

/**
 * Digital Asset Links untuk Trusted Web Activity (aplikasi Android dari PWA di Play Store).
 * Isi TWA_PACKAGE_NAME & TWA_SHA256_FINGERPRINTS di .env setelah membuat paket Android.
 * @2026-Alifiandra
 */
class TwaController extends Controller
{
    public function __invoke()
    {
        $package = config('almara.twa.package');
        $prints = array_values(array_filter(array_map('trim', explode(',', (string) config('almara.twa.fingerprints')))));
        $body = $package && $prints ? [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => ['namespace' => 'android_app', 'package_name' => $package, 'sha256_cert_fingerprints' => $prints],
        ]] : [];

        return response()->json($body, 200, ['Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_SLASHES);
    }
}
