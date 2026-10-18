<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

// Peran & izin bawaan (blueprint §2). @2026-Alifiandra
class AccessControlSeeder extends Seeder
{
    public const ROLES = [
        Role::ADMIN => ['Admin platform', ['admin.access', 'content.edit', 'content.review', 'content.publish', 'audio.upload', 'users.manage', 'settings.manage', 'audit.view']],
        Role::EDITOR => ['Editor konten', ['admin.access', 'content.edit', 'audio.upload']],
        Role::REVIEWER => ['Reviewer agama', ['admin.access', 'content.review', 'content.publish']],
    ];

    public function run(): void
    {
        $ids = [];
        foreach (Permission::ALL as $key => $desc) {
            $ids[$key] = Permission::query()->updateOrCreate(['key' => $key], ['description' => $desc])->id;
        }
        foreach (self::ROLES as $name => [$label, $perms]) {
            $role = Role::query()->updateOrCreate(['name' => $name], ['label' => $label, 'scope' => 'platform']);
            $role->permissions()->sync(array_map(fn ($p) => $ids[$p], $perms));
        }
    }
}
