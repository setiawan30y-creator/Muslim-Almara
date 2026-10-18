<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;

// @2026-Alifiandra
class MakeAdminCommand extends Command
{
    protected $signature = 'almara:make-admin {email : Email akun yang sudah terdaftar} {--role=admin : admin|editor|reviewer}';

    protected $description = 'Berikan peran admin/editor/reviewer ke akun yang sudah terdaftar.';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Akun tidak ditemukan. Daftar dulu melalui halaman /daftar.');

            return self::FAILURE;
        }
        $role = Role::query()->where('name', $this->option('role'))->first();
        if (! $role) {
            $this->error('Peran tidak dikenal. Jalankan "php artisan db:seed" lebih dulu.');

            return self::FAILURE;
        }
        $user->roles()->syncWithoutDetaching([$role->id => ['assigned_at' => now()]]);
        Audit::log('role.assigned', $user, ['role' => $role->name, 'via' => 'artisan'], actor: null);
        $this->info("{$user->email} sekarang memiliki peran {$role->label}.");

        return self::SUCCESS;
    }
}
