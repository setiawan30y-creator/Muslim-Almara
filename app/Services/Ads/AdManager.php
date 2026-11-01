<?php

namespace App\Services\Ads;

use Illuminate\Http\Request;

/**
 * Penentu tampil/tidaknya iklan + persetujuan (consent) pengunjung.
 * Prinsip: iklan hanya di area daftar/navigasi; tidak di dalam teks Al-Qur'an, doa, dzikir,
 * panduan ibadah, pemutar audio, maupun halaman akun/admin/login.
 * @2026-Alifiandra
 */
class AdManager
{
    public const CONSENT_COOKIE = 'almara_ads_consent';

    public const CONSENTS = ['personalized', 'npa'];

    /** Rute yang sama sekali bebas iklan (bacaan ibadah, akun, admin). */
    public const BLOCKED_ROUTES = [
        'quran.show', 'quran.verse', 'quran.audio.*', 'doa.show', 'guide.show', 'guide.module',
        'tasbih', 'hafalan', 'hafalan.*', 'profile*', 'settings.*', 'admin.*', 'login', 'register', 'password.*', 'verification.*', 'offline',
        'privacy', 'terms',
    ];

    public function __construct(private Request $request) {}

    public function client(): ?string
    {
        $c = trim((string) config('almara.ads.client'));

        return preg_match('/^ca-pub-\d{10,20}$/', $c) ? $c : null;
    }

    public function enabled(): bool
    {
        return (bool) config('almara.ads.enabled') && $this->client() !== null;
    }

    public function slot(string $name): ?string
    {
        $id = trim((string) config("almara.ads.slots.$name"));

        return preg_match('/^\d{5,20}$/', $id) ? $id : null;
    }

    public function allowedHere(): bool
    {
        if (! $this->enabled()) {
            return false;
        }
        if (config('almara.ads.hide_for_members') && $this->request->user()) {
            return false;
        }
        $route = $this->request->route();

        return ! ($route && $this->request->routeIs(...self::BLOCKED_ROUTES));
    }

    public function shows(string $slot): bool
    {
        return $this->allowedHere() && $this->slot($slot) !== null;
    }

    public function consent(): ?string
    {
        $c = $this->request->cookie(self::CONSENT_COOKIE);

        return in_array($c, self::CONSENTS, true) ? $c : null;
    }

    /** Isi ads.txt: baris AdSense dari ADSENSE_CLIENT + baris tambahan. */
    public function adsTxt(): ?string
    {
        $lines = [];
        if ($c = $this->client()) {
            $lines[] = 'google.com, '.substr($c, 3).', DIRECT, f08c47fec0942fa0';
        }
        foreach (explode('|', (string) config('almara.ads.ads_txt_extra')) as $l) {
            $l = trim(preg_replace('/[\r\n]+/', ' ', $l));
            if ($l !== '' && preg_match('/^[\w.\-]+\s*,\s*[\w\-]+\s*,\s*(DIRECT|RESELLER)(\s*,\s*[\w]+)?$/i', $l)) {
                $lines[] = $l;
            }
        }

        return $lines ? implode("\n", $lines)."\n" : null;
    }
}
