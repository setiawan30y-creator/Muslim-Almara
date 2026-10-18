<?php

namespace App\Services\Quran;

use App\Models\ContentSource;
use App\Models\QuranSurah;
use App\Models\QuranTafsir;
use App\Models\QuranTranslation;
use App\Models\QuranVerse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Impor dataset Al-Qur'an ke database secara idempotent (blueprint §5.2):
 * validasi dulu, lalu upsert dalam transaksi. Teks tidak pernah diubah, hanya disalin.
 *
 * @2026-Alifiandra
 */
class QuranImporter
{
    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<string, int> */
    public array $stats = ['surahs' => 0, 'verses' => 0, 'translations' => 0, 'tafsirs' => 0];

    /**
     * Validasi dataset tanpa menyentuh database.
     *
     * @param  array<int>  $only  batasi ke nomor surah tertentu (untuk pengujian)
     */
    public function validate(array $only = []): bool
    {
        $this->errors = [];
        $index = $this->readJson('index.json');
        if (count($index) !== QuranDataset::SURAH_COUNT) {
            $this->errors[] = 'index.json berisi '.count($index).' surah, seharusnya '.QuranDataset::SURAH_COUNT.'.';
        }

        $total = 0;
        $seen = [];
        foreach ($index as $meta) {
            $n = (int) $meta['nomor'];
            if ($n < 1 || $n > 114 || isset($seen[$n])) {
                $this->errors[] = "Nomor surah tidak valid/duplikat: {$n}.";
            }
            $seen[$n] = true;
            $total += (int) $meta['jumlah_ayat'];

            if ($only && ! in_array($n, $only, true)) {
                continue;
            }
            $data = $this->readJson("{$n}.json");
            $ayat = $data['ayat'] ?? [];
            if (count($ayat) !== (int) $meta['jumlah_ayat']) {
                $this->errors[] = "Surah {$n}: jumlah ayat ".count($ayat).' ≠ '.$meta['jumlah_ayat'].'.';
            }
            $nums = array_column($ayat, 'nomor');
            if ($nums !== range(1, count($ayat))) {
                $this->errors[] = "Surah {$n}: penomoran ayat tidak berurutan/duplikat.";
            }
            foreach ($ayat as $a) {
                if (trim((string) ($a['arab'] ?? '')) === '' || trim((string) ($a['arti'] ?? '')) === '') {
                    $this->errors[] = "Surah {$n} ayat {$a['nomor']}: teks Arab/terjemahan kosong.";
                }
            }
        }

        if ($total !== QuranDataset::VERSE_COUNT) {
            $this->errors[] = "Total ayat {$total}, seharusnya ".QuranDataset::VERSE_COUNT.'.';
        }

        return $this->errors === [];
    }

    /** @param array<int> $only */
    public function import(array $only = [], bool $withTafsir = true): void
    {
        if (! $this->validate($only)) {
            throw new RuntimeException("Validasi dataset gagal:\n- ".implode("\n- ", $this->errors));
        }

        $checksum = QuranDataset::checksum();
        $version = QuranDataset::DATASET_VERSION;

        DB::transaction(function () use ($only, $withTafsir, $checksum, $version) {
            $sources = [];
            foreach (QuranDataset::SOURCES as $key => $s) {
                $sources[$key] = ContentSource::query()->updateOrCreate(['key' => $key], $s + [
                    'version' => $version,
                    'checksum' => $checksum,
                    'retrieved_at' => now(),
                    'validation_notes' => '114 surah, 6.236 ayat; penomoran berurutan; teks & terjemahan tidak kosong (almara:quran:validate).',
                ])->id;
            }

            foreach ($this->readJson('index.json') as $meta) {
                $n = (int) $meta['nomor'];
                if ($only && ! in_array($n, $only, true)) {
                    continue;
                }
                $surah = QuranSurah::query()->updateOrCreate(['number' => $n], [
                    'name_ar' => $meta['nama'],
                    'name_latin' => $meta['nama_latin'],
                    'name_translation' => $meta['arti'],
                    'revelation_type' => in_array($n, QuranDataset::MADANIYAH, true) ? 'madaniyah' : 'makkiyah',
                    'ayah_count' => (int) $meta['jumlah_ayat'],
                    'source_id' => $sources['quran-text-kemenag'],
                ]);
                $this->stats['surahs']++;

                $data = $this->readJson("{$n}.json");
                $now = now();
                $verseRows = [];
                foreach ($data['ayat'] as $a) {
                    $verseRows[] = [
                        'surah_id' => $surah->id,
                        'ayah_number' => (int) $a['nomor'],
                        'juz_number' => QuranDataset::juzFor($n, (int) $a['nomor']),
                        'page_number' => null,
                        'text_uthmani' => $a['arab'],
                        'source_id' => $sources['quran-text-kemenag'],
                        'version' => $version,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($verseRows, 300) as $chunk) {
                    QuranVerse::query()->upsert($chunk, ['surah_id', 'ayah_number'], ['juz_number', 'page_number', 'text_uthmani', 'source_id', 'version', 'updated_at']);
                }
                $this->stats['verses'] += count($verseRows);

                $ids = QuranVerse::query()->where('surah_id', $surah->id)->pluck('id', 'ayah_number');
                $tr = [];
                $tf = [];
                foreach ($data['ayat'] as $a) {
                    $vid = $ids[(int) $a['nomor']];
                    $tr[] = ['verse_id' => $vid, 'language' => 'id', 'translator' => 'Kementerian Agama RI', 'text' => $a['arti'],
                        'source_id' => $sources['quran-translation-kemenag-id'], 'version' => $version, 'created_at' => $now, 'updated_at' => $now];
                    if ($withTafsir && trim((string) ($a['tafsir'] ?? '')) !== '') {
                        $tf[] = ['verse_id' => $vid, 'work_name' => 'Tafsir Ringkas Kemenag', 'author' => 'Kementerian Agama RI', 'language' => 'id',
                            'text' => $a['tafsir'], 'source_id' => $sources['quran-tafsir-kemenag-id'], 'created_at' => $now, 'updated_at' => $now];
                    }
                }
                foreach (array_chunk($tr, 300) as $chunk) {
                    QuranTranslation::query()->upsert($chunk, ['verse_id', 'language', 'translator', 'version'], ['text', 'source_id', 'updated_at']);
                }
                foreach (array_chunk($tf, 100) as $chunk) {
                    QuranTafsir::query()->upsert($chunk, ['verse_id', 'work_name', 'language'], ['author', 'text', 'source_id', 'updated_at']);
                }
                $this->stats['translations'] += count($tr);
                $this->stats['tafsirs'] += count($tf);
            }
        });

        Cache::forget('quran:surahs');
    }

    /** Bandingkan isi database dengan dataset. @return array<int, string> */
    public function verifyDatabase(): array
    {
        $problems = [];
        $surahs = QuranSurah::query()->count();
        $verses = QuranVerse::query()->count();
        if ($surahs !== QuranDataset::SURAH_COUNT) {
            $problems[] = "Database berisi {$surahs} surah (seharusnya 114).";
        }
        if ($verses !== QuranDataset::VERSE_COUNT) {
            $problems[] = "Database berisi {$verses} ayat (seharusnya 6.236).";
        }
        $src = ContentSource::query()->where('key', 'quran-text-kemenag')->first();
        if (! $src) {
            $problems[] = 'Metadata sumber teks belum tercatat.';
        } elseif ($src->checksum !== QuranDataset::checksum()) {
            $problems[] = 'Checksum dataset berbeda dengan yang tercatat saat impor — jalankan impor ulang setelah review.';
        }
        $missingTr = QuranVerse::query()->whereDoesntHave('translations', fn ($q) => $q->where('language', 'id'))->count();
        if ($missingTr) {
            $problems[] = "{$missingTr} ayat belum memiliki terjemahan Indonesia.";
        }

        return $problems;
    }

    /** @return array<mixed> */
    private function readJson(string $file): array
    {
        $path = QuranDataset::path($file);
        if (! is_file($path)) {
            throw new RuntimeException("Berkas dataset tidak ditemukan: {$file}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
