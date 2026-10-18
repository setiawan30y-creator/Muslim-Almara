<?php

namespace App\Console\Commands;

use App\Services\Quran\QuranImporter;
use Illuminate\Console\Command;

// @2026-Alifiandra
class QuranImportCommand extends Command
{
    protected $signature = 'almara:quran:import
        {--only=* : Nomor surah tertentu saja (untuk pengujian)}
        {--no-tafsir : Lewati tafsir}
        {--dry-run : Validasi saja tanpa menulis database}';

    protected $description = "Validasi lalu impor dataset Al-Qur'an (idempotent, dalam transaksi).";

    public function handle(QuranImporter $importer): int
    {
        $only = array_map('intval', (array) $this->option('only'));

        if (! $importer->validate($only)) {
            $this->error('Validasi dataset gagal:');
            foreach ($importer->errors as $e) {
                $this->line(" - {$e}");
            }

            return self::FAILURE;
        }
        $this->info('Validasi dataset lulus (114 surah, 6.236 ayat).');

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $importer->import($only, ! $this->option('no-tafsir'));
        $this->table(['Surah', 'Ayat', 'Terjemahan', 'Tafsir'], [array_values($importer->stats)]);
        $this->info('Impor selesai. Jalankan ulang kapan saja; data tidak akan terduplikasi.');

        return self::SUCCESS;
    }
}
