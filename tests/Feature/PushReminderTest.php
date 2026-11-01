<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Services\Push\AdzanReminder;
use App\Services\Push\WebPush;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Pengingat adzan (Web Push RFC 8291/8292) — @2026-Alifiandra
class PushReminderTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/contoh-langganan';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAlmara();
        $k = WebPush::generateKeys();
        config(['almara.push.public_key' => $k['public'], 'almara.push.private_key' => $k['private'], 'almara.push.subject' => 'mailto:admin@example.com']);
    }

    /** @return array{0: \OpenSSLAsymmetricKey, 1: string, 2: string} kunci peramban (privat, p256dh, auth) */
    private function browserKeys(): array
    {
        $ua = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $e = openssl_pkey_get_details($ua)['ec'];
        $pub = "\x04".str_pad($e['x'], 32, "\0", STR_PAD_LEFT).str_pad($e['y'], 32, "\0", STR_PAD_LEFT);

        return [$ua, WebPush::b64u($pub), WebPush::b64u(random_bytes(16))];
    }

    public function test_encryption_matches_rfc8291_test_vector(): void
    {
        $asPub = WebPush::b64uDecode('BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8');
        $asKey = openssl_pkey_get_private(WebPush::privateKeyPem(WebPush::b64uDecode('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw'), $asPub));
        $body = WebPush::encrypt('When I grow up, I want to be a watermelon',
            WebPush::b64uDecode('BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4'),
            WebPush::b64uDecode('BTBZMqHH6r4Tts7J_aSIgg'), WebPush::b64uDecode('DGv6ra1nlYgDCS1FRnbzlw'), $asKey);

        $this->assertSame('DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN', WebPush::b64u($body));
    }

    public function test_vapid_jwt_is_valid_es256(): void
    {
        $header = app(WebPush::class)->vapidHeader(self::ENDPOINT);
        $this->assertMatchesRegularExpression('/^vapid t=[\w-]+\.[\w-]+\.[\w-]+, k=[\w-]+$/', $header);
        preg_match('/t=([\w-]+)\.([\w-]+)\.([\w-]+)/', $header, $m);
        $claims = json_decode(WebPush::b64uDecode($m[2]), true);
        $this->assertSame('https://fcm.googleapis.com', $claims['aud']);
        $raw = WebPush::b64uDecode($m[3]);
        $this->assertSame(64, strlen($raw));
        // r||s → DER untuk diverifikasi OpenSSL
        $int = fn ($b) => (($b = ltrim($b, "\0")) !== '' && ord($b[0]) > 0x7F ? "\0".$b : ($b ?: "\0"));
        [$r, $s] = [$int(substr($raw, 0, 32)), $int(substr($raw, 32))];
        $der = "\x30".chr(4 + strlen($r) + strlen($s))."\x02".chr(strlen($r)).$r."\x02".chr(strlen($s)).$s;
        $pem = WebPush::publicKeyPem(WebPush::b64uDecode(config('almara.push.public_key')));
        $this->assertSame(1, openssl_verify($m[1].'.'.$m[2], $der, $pem, OPENSSL_ALGO_SHA256));
    }

    public function test_subscribe_validates_and_rejects_unknown_push_hosts(): void
    {
        [, $p256dh, $auth] = $this->browserKeys();
        $payload = ['endpoint' => self::ENDPOINT, 'keys' => ['p256dh' => $p256dh, 'auth' => $auth], 'prayers' => ['subuh', 'zuhur'], 'minutes_before' => 10];

        $this->getJson('/api/v1/push/config')->assertOk()->assertJsonPath('data.enabled', true);
        $this->postJson('/api/v1/push/subscriptions', ['endpoint' => 'https://evil.example/collect'] + $payload)->assertStatus(422);
        $this->postJson('/api/v1/push/subscriptions', ['endpoint' => 'http://127.0.0.1/x'] + $payload)->assertStatus(422);
        $this->postJson('/api/v1/push/subscriptions', ['prayers' => ['sarapan']] + $payload)->assertStatus(422);
        $this->postJson('/api/v1/push/subscriptions', ['keys' => ['p256dh' => 'pendek', 'auth' => $auth]] + $payload)->assertStatus(422);

        $this->postJson('/api/v1/push/subscriptions', $payload)->assertCreated()->assertJsonPath('data.location', 'Jakarta')->assertJsonPath('data.minutes_before', 10);
        $this->postJson('/api/v1/push/subscriptions', ['prayers' => ['magrib']] + $payload)->assertOk(); // perbarui, bukan duplikat
        $this->assertSame(1, PushSubscription::query()->count());
        $this->assertSame(['magrib'], PushSubscription::query()->first()->prayers_json);

        $this->deleteJson('/api/v1/push/subscriptions', ['endpoint' => self::ENDPOINT])->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertSame(0, PushSubscription::query()->count());

        config(['almara.push.public_key' => null]);
        $this->postJson('/api/v1/push/subscriptions', $payload)->assertStatus(503);
        $this->get('/pengaturan/jadwal-sholat')->assertOk()->assertSee('belum diaktifkan di server');
    }

    public function test_reminder_fires_once_at_prayer_time_and_cleans_expired(): void
    {
        [$ua, $p256dh, $auth] = $this->browserKeys();
        $this->postJson('/api/v1/push/subscriptions', ['endpoint' => self::ENDPOINT, 'keys' => ['p256dh' => $p256dh, 'auth' => $auth], 'prayers' => ['zuhur'], 'minutes_before' => 5])->assertCreated();
        $sub = PushSubscription::query()->firstOrFail();
        $reminder = app(AdzanReminder::class);

        $local = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $zuhur = $reminder->times($sub, $local)['zuhur'];
        $at = CarbonImmutable::createFromFormat('Y-m-d H:i', $local->toDateString().' '.$zuhur, 'Asia/Jakarta')->subMinutes(5)->setTimezone('UTC');

        Http::fake(['fcm.googleapis.com/*' => Http::sequence()->push('', 201)->push('', 410)]);
        $this->assertNull($reminder->dueFor($sub, $at->subMinute()));
        $this->assertSame(['sent' => 1], array_intersect_key($reminder->run($at), ['sent' => 1]));
        $this->assertSame(['sent' => 0], array_intersect_key($reminder->run($at), ['sent' => 1])); // tidak dobel

        Http::assertSent(function ($req) use ($ua, $p256dh, $auth) {
            if ($req->header('Content-Encoding')[0] !== 'aes128gcm' || ! str_starts_with($req->header('Authorization')[0], 'vapid t=')) {
                return false;
            }
            // Dekripsi seperti peramban → isi notifikasi benar
            $body = $req->body();
            $salt = substr($body, 0, 16);
            $asPub = substr($body, 21, 65);
            $ct = substr($body, 86);
            $uaPub = WebPush::b64uDecode($p256dh);
            $ecdh = openssl_pkey_derive(openssl_pkey_get_public(WebPush::publicKeyPem($asPub)), $ua, 32);
            $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0".$uaPub.$asPub, WebPush::b64uDecode($auth));
            $pt = openssl_decrypt(substr($ct, 0, -16), 'aes-128-gcm', hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt),
                OPENSSL_RAW_DATA, hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt), substr($ct, -16));
            $msg = json_decode(rtrim((string) $pt, "\x02"), true);

            return $msg['title'] === '5 menit lagi Zuhur' && str_contains($msg['body'], 'Jakarta');
        });

        // Langganan dicabut di perangkat → dihapus otomatis
        $sub->refresh()->forceFill(['last_sent_key' => null])->save();
        $this->assertSame(1, $reminder->run($at)['removed']);
        $this->assertSame(0, PushSubscription::query()->count());
        $this->artisan('almara:push:adzan')->assertSuccessful();
    }

    public function test_service_worker_handles_push_and_settings_ui_present(): void
    {
        $this->get('/service-worker.js')->assertOk()->assertSee("addEventListener('push'", false)->assertSee('notificationclick', false);
        $this->get('/pengaturan/jadwal-sholat')->assertOk()->assertSee('Pengingat adzan')->assertSee('data-push-on', false)->assertSee(config('almara.push.public_key'));
        $this->assertStringNotContainsString((string) config('almara.push.private_key'), $this->get('/pengaturan/jadwal-sholat')->getContent());
    }
}
