<?php

namespace Database\Seeders;

use App\Models\PrayerMethod;
use Illuminate\Database\Seeder;

/**
 * Metode perhitungan yang terdokumentasi. Parameter sudut mengikuti publikasi
 * masing-masing otoritas (dirangkum oleh PrayTimes.org); validasi terhadap jadwal
 * resmi setempat tetap diperlukan (blueprint §5.1 butir 4).
 * @2026-Alifiandra
 */
class PrayerMethodSeeder extends Seeder
{
    public const METHODS = [
        ['kemenag', 'Kementerian Agama RI', ['fajr_angle' => 20, 'isha_angle' => 18, 'ihtiyat' => 2], 'Kementerian Agama RI — Subuh 20°, Isya 18°, ihtiyat 2 menit'],
        ['mwl', 'Muslim World League', ['fajr_angle' => 18, 'isha_angle' => 17], 'Muslim World League (Rabithah Alam Islami)'],
        ['umm-al-qura', 'Umm al-Qura, Makkah', ['fajr_angle' => 18.5, 'isha_minutes' => 90], 'Umm al-Qura University, Makkah (Isya 90 menit setelah Magrib; 120 menit pada Ramadan)'],
        ['egypt', 'Egyptian General Authority of Survey', ['fajr_angle' => 19.5, 'isha_angle' => 17.5], 'Egyptian General Authority of Survey'],
        ['isna', 'Islamic Society of North America', ['fajr_angle' => 15, 'isha_angle' => 15], 'Islamic Society of North America (ISNA)'],
        ['karachi', 'University of Islamic Sciences, Karachi', ['fajr_angle' => 18, 'isha_angle' => 18], 'University of Islamic Sciences, Karachi'],
        ['jakim', 'JAKIM Malaysia', ['fajr_angle' => 20, 'isha_angle' => 18], 'Jabatan Kemajuan Islam Malaysia (JAKIM)'],
        ['muis', 'MUIS Singapura', ['fajr_angle' => 20, 'isha_angle' => 18], 'Majlis Ugama Islam Singapura (MUIS)'],
    ];

    public function run(): void
    {
        foreach (self::METHODS as $i => [$key, $name, $params, $source]) {
            PrayerMethod::query()->updateOrCreate(['key' => $key], [
                'name' => $name,
                'parameters_json' => $params + ['imsak_minutes' => 10, 'duha_altitude' => 4.5],
                'source' => $source,
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }
    }
}
