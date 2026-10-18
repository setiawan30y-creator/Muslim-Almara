<?php

namespace App\Services\Quran\Audio;

use App\Models\QuranAudioTrack;
use App\Models\QuranReciter;
use App\Services\Quran\QuranService;

/**
 * Katalog qari gabungan untuk pemutar:
 *   "almara:{slug}" → Qari ALMARA (rekaman sendiri yang sudah terbit)
 *   "qf:{id}"       → qari berlisensi Quran.Foundation (bila dikonfigurasi)
 * @2026-Alifiandra
 */
class AudioCatalog
{
    public const KEY_PATTERN = '/^(almara:[a-z0-9-]{1,60}|qf:\d{1,6})$/';

    /** @var array<int, array<int, int>>|null reciter_id => daftar surah terbit (memo per request) */
    private ?array $published = null;

    public function __construct(private QuranFoundationClient $qf, private QuranService $quran)
    {
    }

    /** @return array<int, array<int, int>> */
    private function publishedMap(): array
    {
        if ($this->published === null) {
            try {
                $this->published = QuranAudioTrack::query()->published()
                    ->whereHas('reciter', fn ($q) => $q->where('is_active', true))
                    ->get(['reciter_id', 'surah_number'])
                    ->groupBy('reciter_id')->map(fn ($g) => $g->pluck('surah_number')->unique()->values()->all())->all();
            } catch (\Throwable) {
                $this->published = [];
            }
        }

        return $this->published;
    }

    /**
     * @return array<int, array{key: string, name: string, style: ?string, provider: string, available: bool, attribution: ?string}>
     */
    public function reciters(?int $surah = null): array
    {
        $map = $this->publishedMap();
        $out = [];
        if ($map) {
            foreach (QuranReciter::query()->where('is_active', true)->whereIn('id', array_keys($map))->orderBy('sort_order')->orderBy('name')->get() as $r) {
                $out[] = [
                    'key' => $r->key(), 'name' => $r->name, 'style' => $r->style, 'provider' => 'almara',
                    'available' => $surah === null || in_array($surah, $map[$r->id] ?? [], true),
                    'attribution' => null,
                ];
            }
        }
        foreach ($this->qf->reciters() as $r) {
            $out[] = [
                'key' => 'qf:'.$r['id'], 'name' => $r['name'], 'style' => $r['style'], 'provider' => 'quran_foundation',
                'available' => true, 'attribution' => $this->qf->attribution(),
            ];
        }

        return $out;
    }

    /** Kunci valid dari preferensi; bila tidak tersedia → qari pertama yang tersedia untuk surah ini. */
    public function resolve(?string $key, ?int $surah = null): ?string
    {
        $list = $this->reciters($surah);
        foreach ($list as $r) {
            if ($r['key'] === $key && $r['available']) {
                return $key;
            }
        }
        foreach ($list as $r) {
            if ($r['available']) {
                return $r['key'];
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function forSurah(string $key, int $surah): ?array
    {
        if (! preg_match(self::KEY_PATTERN, $key) || $surah < 1 || $surah > 114) {
            return null;
        }
        $reciter = collect($this->reciters())->firstWhere('key', $key);
        if (! $reciter) {
            return null;
        }
        [$provider, $ref] = explode(':', $key, 2);

        if ($provider === 'almara') {
            $track = QuranAudioTrack::query()->published()->with('segments')
                ->whereHas('reciter', fn ($q) => $q->where('slug', $ref)->where('is_active', true))
                ->where('surah_number', $surah)->orderByDesc('version')->first();
            if (! $track) {
                return null;
            }

            return $this->payload($reciter, $surah, route('quran.audio.stream', $track), $track->duration_seconds ? $track->duration_seconds * 1000 : null,
                $track->segments->map(fn ($s) => ['ayah' => $s->ayah_number, 'start_ms' => $s->start_ms, 'end_ms' => $s->end_ms])->all(),
                ['version' => $track->version, 'offline_allowed' => (bool) config('almara.audio.allow_offline')]);
        }

        $audio = $this->qf->chapterAudio((int) $ref, $surah, (int) ($this->quran->surah($surah)?->ayah_count ?? 0));
        if (! $audio) {
            return null;
        }

        return $this->payload($reciter, $surah, $audio['url'], $audio['duration_ms'], $audio['segments'], [
            'offline_allowed' => false, // ketentuan penyedia: tidak untuk disimpan permanen
            'simulated' => $this->qf->isMock(),
        ]);
    }

    private function payload(array $reciter, int $surah, string $url, ?int $durationMs, array $segments, array $extra): array
    {
        return [
            'reciter' => array_intersect_key($reciter, array_flip(['key', 'name', 'style', 'provider'])),
            'surah' => $surah,
            'url' => $url,
            'duration_ms' => $durationMs,
            'segments' => array_values($segments),
            'attribution' => $reciter['attribution'],
        ] + $extra;
    }
}
