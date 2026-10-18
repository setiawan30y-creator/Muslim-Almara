<?php

namespace Tests\Unit;

use App\Services\Calendar\HijriCalendar;
use App\Services\Prayer\PrayerParameters;
use App\Services\Prayer\PrayerTimeCalculator;
use App\Services\Qibla\QiblaService;
use App\Services\Quran\QuranDataset;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Uji unit blueprint §16.1 — @2026-Alifiandra
class PrayerAndQiblaTest extends TestCase
{
    private function kemenag(array $offsets = [], int $asr = 1): PrayerParameters
    {
        return PrayerParameters::fromMethod('kemenag', 'Kemenag', ['fajr_angle' => 20, 'isha_angle' => 18, 'ihtiyat' => 2], $asr, $offsets);
    }

    public function test_jakarta_kemenag_times_are_ordered_and_match_reference(): void
    {
        $t = (new PrayerTimeCalculator)->forDate(CarbonImmutable::create(2026, 10, 18), -6.2088, 106.8456, 7, $this->kemenag());
        $this->assertSame('11:40', $t['zuhur']);
        $this->assertSame(array_values($t), collect($t)->values()->sort()->values()->all());
    }

    public function test_offsets_and_hanafi_asr_are_applied(): void
    {
        $c = new PrayerTimeCalculator;
        $d = CarbonImmutable::create(2026, 10, 18);
        $base = $c->forDate($d, -6.2088, 106.8456, 7, $this->kemenag());
        $off = $c->forDate($d, -6.2088, 106.8456, 7, $this->kemenag(['subuh' => 3, 'isya' => -2]));
        $mins = fn ($hm) => (int) substr($hm, 0, 2) * 60 + (int) substr($hm, 3);
        $this->assertSame(3, $mins($off['subuh']) - $mins($base['subuh']));
        $this->assertSame(-2, $mins($off['isya']) - $mins($base['isya']));
        $hanafi = $c->forDate($d, -6.2088, 106.8456, 7, $this->kemenag([], 2));
        $this->assertGreaterThan($mins($base['asar']), $mins($hanafi['asar']));
    }

    public function test_umm_al_qura_isha_is_fixed_interval_after_magrib(): void
    {
        $p = PrayerParameters::fromMethod('umm-al-qura', 'UQ', ['fajr_angle' => 18.5, 'isha_minutes' => 90]);
        $t = (new PrayerTimeCalculator)->forDate(CarbonImmutable::create(2026, 10, 18), 21.4225, 39.8262, 3, $p);
        $mins = fn ($hm) => (int) substr($hm, 0, 2) * 60 + (int) substr($hm, 3);
        $this->assertEqualsWithDelta(90, $mins($t['isya']) - $mins($t['magrib']), 1);
    }

    public function test_offsets_are_clamped(): void
    {
        $this->assertSame(['subuh' => 30, 'isya' => -30], PrayerParameters::cleanOffsets(['subuh' => 99, 'isya' => -99, 'zuhur' => 0, 'x' => 5]));
    }

    public static function qiblaCases(): array
    {
        return [
            'Jakarta' => [-6.2088, 106.8456, 295.15],
            'Banda Aceh' => [5.5483, 95.3238, 292.4],
            'Jayapura' => [-2.5337, 140.7181, 291.4],
            'London' => [51.5074, -0.1278, 118.99],
            'New York' => [40.7128, -74.0060, 58.48],
            'Kuala Lumpur' => [3.1390, 101.6869, 292.6],
        ];
    }

    #[DataProvider('qiblaCases')]
    public function test_qibla_bearing_reference_cities(float $lat, float $lng, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, (new QiblaService)->bearing($lat, $lng), 0.6);
    }

    public function test_hijri_conversion_and_adjustment(): void
    {
        $h = new HijriCalendar;
        $this->assertSame('1 Syawal 1447 H', $h->fromGregorian(CarbonImmutable::create(2026, 3, 20))['label']);
        $this->assertSame('2026-05-27', $h->toGregorian(1447, 12, 10)->format('Y-m-d'));
        $this->assertSame(1, (new HijriCalendar(1))->fromGregorian(CarbonImmutable::create(2026, 3, 19))['day']);
    }

    public function test_juz_boundaries(): void
    {
        $this->assertSame(1, QuranDataset::juzFor(2, 141));
        $this->assertSame(2, QuranDataset::juzFor(2, 142));
        $this->assertSame(30, QuranDataset::juzFor(78, 1));
        $this->assertSame(30, QuranDataset::juzFor(114, 6));
    }
}
