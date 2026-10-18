<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// PWA (blueprint §9.1, §16.4) — @2026-Alifiandra
class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_valid(): void
    {
        $res = $this->get('/manifest.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', $res->headers->get('Content-Type'));
        $json = json_decode($res->getContent(), true);
        $this->assertSame(config('app.name'), $json['name']);
        $this->assertSame('standalone', $json['display']);
        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_excludes_private_paths(): void
    {
        $res = $this->get('/service-worker.js')->assertOk();
        $this->assertStringContainsString('javascript', $res->headers->get('Content-Type'));
        $body = $res->getContent();
        foreach (['admin', 'profil', 'api\/v1\/me', 'no-store', 'X-Almara-Private', 'CLEAR_PRIVATE'] as $needle) {
            $this->assertStringContainsString($needle, $body);
        }
        $this->get('/sw.js')->assertOk();
    }

    public function test_offline_page_and_manifest_link(): void
    {
        $this->seedAlmara();
        $this->get('/offline')->assertOk()->assertSee('Anda sedang offline');
        $this->get('/')->assertOk()->assertSee('rel="manifest"', false);
    }
}
