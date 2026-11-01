<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\User;
use App\Services\Content\ContentWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Menerbitkan sekaligus konten berstatus "Menunggu review" (doa, dzikir, panduan) atas nama reviewer.
 * Tetap melewati aturan alur review (izin, larangan menyetujui konten sendiri) dan tercatat di
 * riwayat review + log audit. Jalankan HANYA setelah isinya diperiksa.
 * @2026-Alifiandra
 */
class ContentPublishCommand extends Command
{
    protected $signature = 'almara:content:publish {reviewer : Email akun reviewer/admin} {--type=all : doa|dzikir|panduan|all} {--force : Lewati konfirmasi}';

    protected $description = 'Setujui & terbitkan semua konten berstatus review (doa, dzikir, panduan) atas nama reviewer.';

    public function handle(ContentWorkflow $workflow): int
    {
        $reviewer = User::query()->where('email', $this->argument('reviewer'))->first();
        if (! $reviewer || ! $reviewer->hasPermission('content.review') || ! $reviewer->hasPermission('content.publish')) {
            $this->error('Akun tidak ditemukan atau tidak memiliki izin meninjau & menerbitkan (peran admin/reviewer).');

            return self::FAILURE;
        }
        $types = $this->option('type') === 'all' ? array_keys(ContentWorkflow::TYPES) : [$this->option('type')];
        if (array_diff($types, array_keys(ContentWorkflow::TYPES))) {
            $this->error('Jenis tidak dikenal. Pilih: doa, dzikir, panduan, atau all.');

            return self::FAILURE;
        }

        $pending = [];
        foreach ($types as $type) {
            $pending[$type] = ContentWorkflow::model($type)::query()->where('review_status', ContentStatus::Review->value)->orderBy('id')->get();
            $this->line(sprintf('  %-8s %d item menunggu review', $type, $pending[$type]->count()));
        }
        if (collect($pending)->sum->count() === 0) {
            $this->info('Tidak ada konten yang menunggu review.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Konten agama wajib diperiksa ahli. Anda menyatakan isi di atas TELAH diperiksa dan siap terbit?')) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        $ok = 0;
        $skipped = 0;
        foreach ($pending as $type => $items) {
            foreach ($items as $item) {
                try {
                    $workflow->transition($item, 'approve-publish', $reviewer, $type, 'Diterbitkan lewat perintah server setelah diperiksa');
                    $ok++;
                } catch (ValidationException|AuthorizationException $e) {
                    $skipped++;
                    $this->warn("  dilewati {$type}/{$item->slug}: ".($e instanceof ValidationException ? collect($e->errors())->flatten()->first() : $e->getMessage()));
                }
            }
        }
        $this->info("{$ok} konten diterbitkan, {$skipped} dilewati. Tercatat di riwayat review & log audit.");

        return self::SUCCESS;
    }
}
