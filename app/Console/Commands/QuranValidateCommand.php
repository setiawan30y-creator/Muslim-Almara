<?php

namespace App\Console\Commands;

use App\Services\Quran\QuranDataset;
use App\Services\Quran\QuranImporter;
use Illuminate\Console\Command;

// @2026-Alifiandra
class QuranValidateCommand extends Command
{
    protected $signature = 'almara:quran:validate {--dataset-only : Hanya periksa berkas dataset}';

    protected $description = "Periksa integritas dataset & database Al-Qur'an (jumlah, urutan, checksum).";

    public function handle(QuranImporter $importer): int
    {
        $ok = $importer->validate();
        $this->line('Checksum dataset: '.QuranDataset::checksum());
        foreach ($importer->errors as $e) {
            $this->error(" - {$e}");
        }
        if ($ok) {
            $this->info('Dataset: lulus.');
        }

        if (! $this->option('dataset-only')) {
            $problems = $importer->verifyDatabase();
            foreach ($problems as $p) {
                $this->warn(" - {$p}");
            }
            if (! $problems) {
                $this->info('Database: sesuai dataset.');
            }
            $ok = $ok && ! $problems;
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
