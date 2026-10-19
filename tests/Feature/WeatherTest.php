<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Kartu & API cuaca (Open-Meteo) — @2026-Alifiandra
class WeatherTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOpenMeteo(): void
    {
        Http::fake(['*open-meteo.com/*' => Http::response([
            'current' => ['time' => '2026-10-19T09:00', 'temperature_2m' => 30.6, 'apparent_temperature' => 35.2, 'relative_humidity_2m' => 70,
                'weather_code' => 61, 'wind_speed_10m' => 9.4, 'is_day' => 1],
            'hourly' => [
                'time' => ['2026-10-19T08:00', '2026-10-19T09:00', '2026-10-19T10:00', '2026-10-19T11:00', '2026-10-19T19:00'],
                'temperature_2m' => [29, 30.6, 31.4, 32, 27], 'precipitation_probability' => [10, 20, 65, 40, 5],
                'weather_code' => [2, 61, 63, 3, 0], 'is_day' => [1, 1, 1, 1, 0],
            ],
            'daily' => ['temperature_2m_max' => [33.1], 'temperature_2m_min' => [25.4], 'precipitation_probability_max' => [70], 'uv_index_max' => [9.2]],
        ])]);
    }

    public function test_home_shows_weather_card_and_api_returns_forecast(): void
    {
        $this->seedAlmara();
        Cache::flush();
        $this->fakeOpenMeteo();

        $this->get('/')->assertOk()->assertSee('data-weather', false)->assertSee('Cuaca hari ini');
        $this->getJson('/api/v1/weather')->assertOk()
            ->assertJsonPath('data.current.temp', 31)
            ->assertJsonPath('data.current.label', 'Hujan ringan')
            ->assertJsonPath('data.current.icon', 'rain')
            ->assertJsonPath('data.today.max', 33)
            ->assertJsonPath('data.today.rain', 70)
            ->assertJsonPath('data.hours.0.time', '10:00')
            ->assertJsonPath('data.hours.2.icon', 'moon')
            ->assertJsonPath('data.attribution', 'Data cuaca: Open-Meteo.com (CC BY 4.0)')
            ->assertJsonPath('meta.location', 'Jakarta');

        // Di-cache per koordinat: permintaan kedua tidak memanggil penyedia lagi
        $this->getJson('/api/v1/weather')->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://api.open-meteo.com/v1/forecast') && ! str_contains($r->url(), 'apikey'));

        $this->getJson('/api/v1/weather?lat=-6.2&lng=106.8')->assertOk()->assertJsonPath('meta.location', 'Lokasi perangkat');
        $this->getJson('/api/v1/weather?lat=200&lng=106.8')->assertStatus(422);
    }

    public function test_commercial_key_uses_customer_endpoint_and_feature_can_be_disabled(): void
    {
        $this->seedAlmara();
        Cache::flush();
        $this->fakeOpenMeteo();
        config(['almara.weather.api_key' => 'kunci-uji']);
        $this->getJson('/api/v1/weather')->assertOk();
        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://customer-api.open-meteo.com/v1/forecast') && str_contains($r->url(), 'apikey=kunci-uji'));
        $this->assertStringNotContainsString('kunci-uji', $this->getJson('/api/v1/weather')->getContent());

        config(['almara.weather.enabled' => false]);
        $this->getJson('/api/v1/weather')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('data-weather', false);
    }

    public function test_provider_failure_returns_503(): void
    {
        $this->seedAlmara();
        Cache::flush();
        Http::fake(['*open-meteo.com/*' => Http::response(['error' => true], 500)]);
        $this->getJson('/api/v1/weather')->assertStatus(503);
    }
}
