<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun pengguna ALMARA MUSLIM. Semua data pribadi terikat ke user_id dan
 * hanya dapat diakses pemiliknya (Policy + query scope).
 *
 * @2026-Alifiandra
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /** @var array<string, bool>|null */
    private ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ---------------------------------------------------------------- peran

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('assigned_at');
    }

    public function hasRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    public function hasPermission(string $key): bool
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = [];
            foreach ($this->roles()->with('permissions')->get() as $role) {
                foreach ($role->permissions as $p) {
                    $this->permissionCache[$p->key] = true;
                }
            }
        }

        return isset($this->permissionCache[$key]);
    }

    public function flushPermissionCache(): void
    {
        $this->permissionCache = null;
        $this->unsetRelation('roles');
    }

    // ---------------------------------------------------------- preferensi

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function appearance(): HasOne
    {
        return $this->hasOne(UserAppearancePreference::class);
    }

    public function quranPreference(): HasOne
    {
        return $this->hasOne(QuranReadingPreference::class);
    }

    public function prayerPreference(): HasOne
    {
        return $this->hasOne(PrayerPreference::class);
    }

    // ------------------------------------------------------- data pribadi

    public function bookmarks(): HasMany
    {
        return $this->hasMany(UserBookmark::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(UserNote::class);
    }

    public function memorizations(): HasMany
    {
        return $this->hasMany(UserMemorization::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(UserCollection::class);
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(UserReadingProgress::class);
    }

    public function readingGoals(): HasMany
    {
        return $this->hasMany(UserReadingGoal::class);
    }

    public function activeReadingGoal(): HasOne
    {
        return $this->hasOne(UserReadingGoal::class)->where('status', 'active')->latestOfMany();
    }

    public function lastRead(): HasOne
    {
        return $this->hasOne(UserLastRead::class);
    }
}
