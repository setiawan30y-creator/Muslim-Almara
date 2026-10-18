<?php

namespace Tests\Unit;

use App\Services\HijriCalendar;
use App\Services\PrayerTimeService;
use App\Services\QiblaService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

// @2026-Alifiandra
class ServicesTest extends TestCase
{
    public function test_prayer_times_jakarta_are_in_expected_order(): void
    {
        $t = (new PrayerTimeService)->forDate(CarbonImmutable::create(2026, 10, 18), -6.2088, 106.8456, 7);
        $this->assertSame('11:40', $t['zuhur']);
        $this->assertSame(array_values($t), collect($t)->values()->sort()->values()->all());
    }

    public function test_hijri_conversion_round_trip(): void
    {
        $h = new HijriCalendar;
        $this->assertSame('1 Syawal 1447 H', $h->fromGregorian(CarbonImmutable::create(2026, 3, 20))['label']);
        $this->assertSame('2026-05-27', $h->toGregorian(1447, 12, 10)->format('Y-m-d'));
        $this->assertSame(1, (new HijriCalendar(1))->fromGregorian(CarbonImmutable::create(2026, 3, 19))['day']);
    }

    public function test_qibla_bearing(): void
    {
        $this->assertEqualsWithDelta(295.15, (new QiblaService)->bearing(-6.2088, 106.8456), 0.05);
    }
}
