<?php

namespace App\Services\Push;

use App\Enums\AsrMethod;
use App\Models\PushSubscription;
use App\Services\Prayer\PrayerParameters;
use App\Services\Prayer\PrayerTimeCalculator;
use App\Services\Preferences\PreferenceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim pengingat adzan ke setiap langganan yang waktunya tiba (dipanggil tiap menit oleh penjadwal).
 * @2026-Alifiandra
 */
class AdzanReminder
{
    /** @var array<string, array<string, string>> */
    private array $memo = [];

    public function __construct(private PrayerTimeCalculator $calc, private PreferenceService $prefs, private WebPush $push)
    {
    }

    public function params(PushSubscription $s): PrayerParameters
    {
        $m = $this->prefs->methods()[$s->method] ?? null;
        $asr = AsrMethod::tryFrom($s->asr) ?? AsrMethod::Standard;

        return $m
            ? PrayerParameters::fromMethod($m['key'], $m['name'], $m['parameters'], $asr->shadowFactor(), $s->offsets_json ?? [])
            : new PrayerParameters(offsets: $s->offsets_json ?? []);
    }

    /** @return array<string, string> */
    public function times(PushSubscription $s, CarbonImmutable $localNow): array
    {
        $key = implode('|', [$s->lat, $s->lng, $s->tz, $s->method, $s->asr, json_encode($s->offsets_json), $localNow->toDateString()]);

        return $this->memo[$key] ??= $this->calc->forDate($localNow->startOfDay(), $s->lat, $s->lng, $s->tz, $this->params($s));
    }

    /** Pengingat yang jatuh tempo pada menit ini, atau null. @return array{prayer: string, time: string, key: string}|null */
    public function dueFor(PushSubscription $s, ?CarbonImmutable $utcNow = null): ?array
    {
        $local = ($utcNow ?? CarbonImmutable::now('UTC'))->setTimezone($this->calc->tzString($s->tz));
        $times = $this->times($s, $local);
        $hm = $local->format('H:i');
        foreach ((array) $s->prayers_json as $prayer) {
            if (! isset($times[$prayer], PushSubscription::PRAYERS[$prayer])) {
                continue;
            }
            $target = CarbonImmutable::createFromFormat('Y-m-d H:i', $local->toDateString().' '.$times[$prayer], $local->getTimezone())
                ->subMinutes($s->minutes_before)->format('H:i');
            $key = $local->toDateString().'|'.$prayer;
            if ($target === $hm && $s->last_sent_key !== $key) {
                return ['prayer' => $prayer, 'time' => $times[$prayer], 'key' => $key];
            }
        }

        return null;
    }

    /** @return array{title: string, body: string, url: string, tag: string} */
    public function message(PushSubscription $s, string $prayer, string $time): array
    {
        $name = PushSubscription::PRAYERS[$prayer];
        $where = $s->location_name ? ' · '.$s->location_name : '';
        if ($prayer === 'imsak') {
            return ['title' => 'Waktu Imsak '.$time, 'body' => 'Segera akhiri sahur. Subuh sebentar lagi'.$where.'.', 'url' => '/jadwal-sholat', 'tag' => 'adzan-imsak'];
        }
        $title = $s->minutes_before > 0 ? "{$s->minutes_before} menit lagi {$name}" : "Waktu {$name} telah tiba";

        return ['title' => $title, 'body' => "{$name} {$time}{$where}. Mari bersiap menunaikan sholat.", 'url' => $s->minutes_before > 0 ? '/jadwal-sholat' : '/jadwal-sholat?adzan='.$prayer, 'tag' => 'adzan-'.$prayer];
    }

    /** @return array{checked: int, sent: int, removed: int} */
    public function run(?CarbonImmutable $utcNow = null): array
    {
        $stats = ['checked' => 0, 'sent' => 0, 'removed' => 0];
        if (! $this->push->configured()) {
            return $stats;
        }
        PushSubscription::query()->orderBy('id')->chunkById(500, function ($subs) use ($utcNow, &$stats) {
            foreach ($subs as $s) {
                $stats['checked']++;
                $due = $this->dueFor($s, $utcNow);
                if (! $due) {
                    continue;
                }
                try {
                    $status = $this->push->send($s->endpoint, $s->p256dh, $s->auth, $this->message($s, $due['prayer'], $due['time']));
                } catch (\Throwable $e) {
                    Log::warning('Push adzan gagal', ['id' => $s->id, 'error' => $e->getMessage()]);
                    $status = 0;
                }
                if (in_array($status, [404, 410], true)) {
                    $s->delete(); // langganan sudah dicabut di perangkat
                    $stats['removed']++;
                    continue;
                }
                $ok = $status >= 200 && $status < 300;
                $s->forceFill([
                    'last_sent_key' => $due['key'],
                    'last_sent_at' => $ok ? now() : $s->last_sent_at,
                    'failures' => $ok ? 0 : $s->failures + 1,
                ])->save();
                if ($ok) {
                    $stats['sent']++;
                } elseif ($s->failures >= 30) {
                    $s->delete();
                    $stats['removed']++;
                }
            }
        });

        return $stats;
    }
}
