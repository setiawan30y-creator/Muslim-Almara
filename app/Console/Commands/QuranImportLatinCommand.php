<?php

namespace App\Console\Commands;

use App\Models\ContentSource;
use App\Models\QuranSurah;
use App\Models\QuranVerse;
use App\Services\Quran\QuranDataset;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Impor transliterasi Latin per ayat dari dataset yang hak penggunaannya sudah Anda miliki.
 * Format yang diterima:
 *   JSON objek  {"1": {"1": "Bismillāhir-raḥmānir-raḥīm", ...}, ...}
 *   JSON larik  [{"surah": 1, "ayah": 1, "text": "..."}, ...]
 *   CSV         surah,ayah,text  (baris judul opsional)
 * @2026-Alifiandra
 */
class QuranImportLatinCommand extends Command
{
    protected $signature = 'almara:quran:import-latin {file : Path berkas JSON/CSV}
        {--name= : Nama sumber, mis. "Transliterasi Kemenag RI"}
        {--license= : Dasar hak penggunaan (wajib), mis. "Izin tertulis No. ..."}
        {--dry-run : Validasi saja tanpa menyimpan}';

    protected $description = 'Impor transliterasi Latin per ayat dari dataset berlisensi.';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("Berkas tidak ditemukan: {$file}");

            return self::FAILURE;
        }
        if (! $this->option('license') && ! $this->option('dry-run')) {
            $this->error('Isi --license dengan dasar hak penggunaan dataset (blueprint: hanya konten bersumber & berizin).');

            return self::FAILURE;
        }

        $rows = $this->parse($file);
        $errors = [];
        foreach ($rows as $key => $text) {
            [$s, $a] = array_map('intval', explode(':', $key));
            if ($s < 1 || $s > 114 || $a < 1 || $a > QuranDataset::AYAH_COUNTS[$s - 1]) {
                $errors[] = "Rujukan tidak valid: {$key}";
            } elseif (trim($text) === '' || mb_strlen($text) > 5000) {
                $errors[] = "Teks kosong/terlalu panjang: {$key}";
            }
        }
        $this->line(count($rows).' ayat terbaca dari '.basename($file).' (lengkap: '.QuranDataset::VERSE_COUNT.').');
        if ($errors) {
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->warn('  '.$e);
            }
            $this->error(count($errors).' masalah ditemukan. Tidak ada yang disimpan.');

            return self::FAILURE;
        }
        if ($this->option('dry-run')) {
            $this->info('Validasi lolos (dry-run).');

            return self::SUCCESS;
        }

        $surahIds = QuranSurah::query()->pluck('id', 'number');
        $n = 0;
        DB::transaction(function () use ($rows, $surahIds, $file, &$n) {
            $source = ContentSource::query()->updateOrCreate(['key' => 'quran-transliteration'], [
                'name' => $this->option('name') ?: 'Transliterasi Latin',
                'content_type' => 'quran_transliteration',
                'license' => (string) $this->option('license'),
                'attribution' => $this->option('name') ?: 'Transliterasi Latin',
                'version' => date('Y.m.d'),
                'checksum' => hash_file('sha256', $file),
                'retrieved_at' => now(),
            ]);
            foreach ($rows as $key => $text) {
                [$s, $a] = array_map('intval', explode(':', $key));
                if (! isset($surahIds[$s])) {
                    continue;
                }
                $n += QuranVerse::query()->where('surah_id', $surahIds[$s])->where('ayah_number', $a)->update(['text_latin' => trim($text)]);
            }
            Audit::log('quran.latin_imported', $source, ['verses' => $n], useAuth: false);
        });
        Cache::forget('quran:latin_available');
        $this->info("{$n} ayat diperbarui. Aktifkan tampilan di Pengaturan → Al-Qur'an → Transliterasi Latin.");

        return self::SUCCESS;
    }

    /** @return array<string, string> "surah:ayah" => teks */
    private function parse(string $file): array
    {
        $raw = (string) file_get_contents($file);
        $out = [];
        if (str_ends_with(strtolower($file), '.csv')) {
            foreach (preg_split('/\R/u', $raw) as $line) {
                $c = str_getcsv($line);
                if (count($c) >= 3 && ctype_digit(trim($c[0])) && ctype_digit(trim($c[1]))) {
                    $out[(int) $c[0].':'.(int) $c[1]] = (string) $c[2];
                }
            }

            return $out;
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return [];
        }
        if (array_is_list($data)) {
            foreach ($data as $r) {
                if (isset($r['surah'], $r['ayah'], $r['text'])) {
                    $out[(int) $r['surah'].':'.(int) $r['ayah']] = (string) $r['text'];
                }
            }

            return $out;
        }
        foreach ($data as $s => $ayahs) {
            foreach ((array) $ayahs as $a => $t) {
                $out[(int) $s.':'.(int) $a] = (string) $t;
            }
        }

        return $out;
    }
}
