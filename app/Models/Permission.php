<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// @2026-Alifiandra
class Permission extends Model
{
    /** Daftar izin platform beserta deskripsinya. */
    public const ALL = [
        'admin.access' => 'Membuka panel admin',
        'content.edit' => 'Membuat & mengubah draf konten (doa, dzikir)',
        'content.review' => 'Meninjau & menyetujui konten agama',
        'content.publish' => 'Menerbitkan & menangguhkan konten',
        'users.manage' => 'Mengelola pengguna & peran',
        'settings.manage' => 'Mengubah konfigurasi aplikasi',
        'audit.view' => 'Melihat log audit',
    ];

    protected $fillable = ['key', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
