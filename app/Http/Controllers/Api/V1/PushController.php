<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PushSubscription;
use App\Services\Content\StaticContent;
use App\Services\Preferences\PreferenceService;
use App\Services\Push\AdzanReminder;
use App\Services\Push\WebPush;
use App\Support\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengingat adzan (Web Push).
 * POST   /api/v1/push/subscriptions        — simpan/perbarui langganan perangkat ini
 * DELETE /api/v1/push/subscriptions        — {endpoint} berhenti berlangganan
 * POST   /api/v1/push/subscriptions/test   — {endpoint} kirim notifikasi uji
 * @2026-Alifiandra
 */
class PushController extends ApiController
{
    public function config(WebPush $push)
    {
        return $this->ok(['enabled' => $push->configured(), 'public_key' => $push->publicKey(), 'prayers' => PushSubscription::PRAYERS]);
    }

    public function store(Request $request, WebPush $push, PreferenceService $prefs, StaticContent $content)
    {
        abort_unless($push->configured(), 503, 'Pengingat adzan belum diaktifkan di server.');
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url'],
            'keys.p256dh' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
            'keys.auth' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/'],
            'prayers' => ['required', 'array', 'min:1'],
            'prayers.*' => [Rule::in(array_keys(PushSubscription::PRAYERS))],
            'minutes_before' => ['nullable', 'integer', Rule::in([0, 5, 10, 15, 30])],
        ], [], ['prayers' => 'waktu sholat']);
        if (! WebPush::isAllowedEndpoint($data['endpoint'])) {
            return response()->json(['message' => 'Layanan notifikasi peramban ini tidak didukung.', 'errors' => ['endpoint' => ['Tidak didukung.']]], 422);
        }
        try {
            $ok = strlen(WebPush::b64uDecode($data['keys']['p256dh'])) === 65 && strlen(WebPush::b64uDecode($data['keys']['auth'])) === 16;
        } catch (\Throwable) {
            $ok = false;
        }
        if (! $ok) {
            return response()->json(['message' => 'Kunci langganan tidak valid.', 'errors' => ['keys' => ['Tidak valid.']]], 422);
        }

        $loc = Location::resolve($request, $content, false);
        $s = $prefs->prayerSettings();
        $sub = PushSubscription::query()->updateOrCreate(['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])], [
            'user_id' => $request->user()?->id,
            'endpoint' => $data['endpoint'],
            'p256dh' => $data['keys']['p256dh'],
            'auth' => $data['keys']['auth'],
            'lat' => round($loc['lat'], 2),
            'lng' => round($loc['lng'], 2),
            'tz' => $loc['tz'],
            'location_name' => mb_substr($loc['nama'], 0, 80),
            'method' => $s['method'],
            'asr' => $s['asr'],
            'offsets_json' => $s['offsets'],
            'prayers_json' => array_values(array_unique($data['prayers'])),
            'minutes_before' => (int) ($data['minutes_before'] ?? 0),
            'failures' => 0,
        ]);

        return $this->ok(['prayers' => $sub->prayers_json, 'minutes_before' => $sub->minutes_before, 'location' => $sub->location_name, 'method' => $sub->method], [], $sub->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:1000']]);
        $n = PushSubscription::query()->where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))->delete();

        return $this->ok(['deleted' => $n > 0]);
    }

    public function test(Request $request, WebPush $push, AdzanReminder $reminder)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:1000']]);
        $sub = PushSubscription::query()->where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))->firstOrFail();
        $status = $push->send($sub->endpoint, $sub->p256dh, $sub->auth, [
            'title' => 'Pengingat adzan aktif',
            'body' => 'Anda akan diingatkan pada waktu '.collect($sub->prayers_json)->map(fn ($p) => PushSubscription::PRAYERS[$p] ?? $p)->implode(', ').'.',
            'url' => '/jadwal-sholat', 'tag' => 'adzan-test',
        ], 300);
        if (in_array($status, [404, 410], true)) {
            $sub->delete();
        }

        return $status >= 200 && $status < 300
            ? $this->ok(['sent' => true])
            : response()->json(['message' => 'Notifikasi uji gagal dikirim (kode '.$status.').', 'errors' => (object) []], 502);
    }
}
