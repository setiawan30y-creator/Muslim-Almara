<?php

namespace App\Services\Adzan;

use App\Models\AdzanSound;

/**
 * Daftar suara adzan yang bisa dipilih pengguna:
 *  1. Rekaman yang diunggah & disetujui di panel admin (almara:{id})
 *  2. Berkas berlisensi dari URL di .env (ADZAN_URL / ADZAN_SUBUH_URL)
 *  3. "Nada pengingat" bawaan (bukan adzan) — selalu tersedia sebagai cadangan
 * @2026-Alifiandra
 */
class AdzanAudio
{
    public const CHIME = 'nada';

    /** @return array<int, array{key: string, name: string, kind: string, url: string, note: ?string}> */
    public function sounds(): array
    {
        $out = [];
        foreach (AdzanSound::query()->published()->orderBy('kind')->orderBy('name')->get() as $s) {
            $out[] = ['key' => $s->key(), 'name' => $s->name, 'kind' => $s->kind, 'url' => route('adzan.audio', $s), 'note' => $s->license_note];
        }
        foreach (['regular' => 'url', 'subuh' => 'subuh_url'] as $kind => $cfg) {
            $url = trim((string) config("almara.adzan.$cfg"));
            if ($url !== '' && preg_match('#^https://[^\s"\'<>]+$#i', $url)) {
                $out[] = ['key' => 'env:'.$kind, 'name' => (string) (config("almara.adzan.{$cfg}_name") ?: ($kind === 'subuh' ? 'Adzan Subuh' : 'Adzan')), 'kind' => $kind, 'url' => $url, 'note' => config('almara.adzan.license_note')];
            }
        }
        $out[] = ['key' => self::CHIME, 'name' => 'Nada pengingat (bukan adzan)', 'kind' => 'any', 'url' => route('adzan.chime'), 'note' => null];

        return $out;
    }

    /** Pilihan bawaan per jenis: rekaman adzan pertama yang tersedia, jika tidak ada → nada. */
    public function defaults(): array
    {
        $s = collect($this->sounds());
        $regular = $s->firstWhere('kind', 'regular')['key'] ?? self::CHIME;
        $subuh = $s->firstWhere('kind', 'subuh')['key'] ?? $regular;

        return ['regular' => $regular, 'subuh' => $subuh];
    }

    public function hasAdzan(): bool
    {
        return collect($this->sounds())->contains(fn ($s) => $s['key'] !== self::CHIME);
    }

    /** Nada lonceng lembut ±5 detik, WAV mono 16 kHz 16-bit (dibuat sekali lalu di-cache di disk). */
    public static function chimeWav(): string
    {
        $rate = 16000;
        $len = (int) ($rate * 5.2);
        $notes = [[0.0, 659.25], [0.9, 523.25], [1.8, 392.0], [3.0, 523.25]]; // E5 C5 G4 C5
        $buf = array_fill(0, $len, 0.0);
        foreach ($notes as [$start, $f]) {
            $s0 = (int) ($start * $rate);
            for ($i = 0; $i < (int) (2.2 * $rate) && $s0 + $i < $len; $i++) {
                $t = $i / $rate;
                $env = exp(-2.4 * $t) * min(1, $t * 200);
                $buf[$s0 + $i] += $env * (0.6 * sin(2 * M_PI * $f * $t) + 0.25 * sin(2 * M_PI * $f * 2.01 * $t) + 0.1 * sin(2 * M_PI * $f * 3.0 * $t));
            }
        }
        $pcm = '';
        foreach ($buf as $v) {
            $pcm .= pack('v', (int) round(max(-1, min(1, $v * 0.45)) * 32767) & 0xFFFF);
        }

        return 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16).'data'.pack('V', strlen($pcm)).$pcm;
    }
}
