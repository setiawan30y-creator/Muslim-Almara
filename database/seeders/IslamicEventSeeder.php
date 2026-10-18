<?php

namespace Database\Seeders;

use App\Models\ContentSource;
use App\Models\IslamicEvent;
use App\Services\Calendar\HijriCalendar;
use Illuminate\Database\Seeder;

// Peristiwa Islam tahunan; tanggal Masehi dihitung (urfi), bukan pengumuman resmi. @2026-Alifiandra
class IslamicEventSeeder extends Seeder
{
    public function run(): void
    {
        $src = ContentSource::query()->updateOrCreate(['key' => 'hijri-tabular'], [
            'name' => 'Kalender Hijriah aritmetika (urfi)',
            'content_type' => 'calendar',
            'license' => 'Perhitungan internal',
            'attribution' => 'Tanggal Hijriah dihitung secara aritmetika; dapat berbeda ±1 hari dengan penetapan resmi (sidang isbat).',
            'version' => '1',
        ]);
        foreach (HijriCalendar::EVENTS as [$m, $d, $title, $desc]) {
            IslamicEvent::query()->updateOrCreate(['hijri_month' => $m, 'hijri_day' => $d, 'title' => $title], [
                'date_basis' => 'calculated', 'description' => $desc, 'source_id' => $src->id,
            ]);
        }
    }
}
