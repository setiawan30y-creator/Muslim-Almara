<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Content\ContentWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ringkasan status konten & akun admin — untuk memeriksa mengapa konten belum tampil.
 * @2026-Alifiandra
 */
class ContentStatusCommand extends Command
{
    protected $signature = 'almara:content:status';

    protected $description = 'Tampilkan jumlah doa, dzikir, panduan per status dan akun yang bisa menerbitkan.';

    public function handle(): int
    {
        $this->line('Database: '.config('database.default').' / '.config('database.connections.'.config('database.default').'.database'));
        foreach (ContentWorkflow::TYPES as $type => $class) {
            $table = (new $class)->getTable();
            if (! Schema::hasTable($table)) {
                $this->error("  {$type}: tabel {$table} belum ada — jalankan php artisan migrate");

                continue;
            }
            $counts = DB::table($table)->selectRaw('review_status, count(*) n')->groupBy('review_status')->pluck('n', 'review_status')->all();
            $this->line(sprintf('  %-8s %s', $type, $counts ? json_encode($counts) : 'KOSONG — jalankan seeder'));
        }
        $users = User::query()->with('roles')->get();
        $this->line('Akun terdaftar: '.$users->count());
        foreach ($users as $u) {
            $this->line('  - '.$u->email.' ['.($u->roles->pluck('name')->implode(', ') ?: 'pengguna').']'
                .($u->hasPermission('content.publish') && $u->hasPermission('content.review') ? ' → bisa menerbitkan' : ''));
        }

        return self::SUCCESS;
    }
}
