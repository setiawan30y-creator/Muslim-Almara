<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Metode perhitungan waktu sholat. @2026-Alifiandra
class PrayerMethod extends Model
{
    protected $fillable = ['key', 'name', 'parameters_json', 'source', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['parameters_json' => 'array', 'is_active' => 'boolean'];
    }

    public function summary(): string
    {
        $p = $this->parameters_json;
        $isha = isset($p['isha_minutes']) ? $p['isha_minutes'].' menit setelah Magrib' : ($p['isha_angle'] ?? '-').'°';

        return 'Subuh '.$p['fajr_angle'].'°, Isya '.$isha.(! empty($p['ihtiyat']) ? ', ihtiyat '.$p['ihtiyat'].' menit' : '');
    }
}
