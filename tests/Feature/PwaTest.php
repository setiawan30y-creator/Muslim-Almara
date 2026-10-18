<?php

namespace Tests\Feature;

use Tests\TestCase;

// @2026-Alifiandra
class PwaTest extends TestCase
{
    public function test_manifest_is_valid(): void
    {
        $res = $this->get('/manifest.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', $res->headers->get('Content-Type'));
        $json = json_decode($res->getContent(), true);
        $this->assertSame('standalone', $json['display']);
        $this->assertCount(3, $json['icons']);
        foreach ($json['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_is_served_as_javascript(): void
    {
        $res = $this->get('/sw.js')->assertOk();
        $this->assertStringContainsString('javascript', $res->headers->get('Content-Type'));
        $this->assertStringContainsString("const VERSION = '", $res->getContent());
        $this->assertStringContainsString("'/css/app.css'", $res->getContent());
    }

    public function test_offline_page_and_manifest_link(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Anda sedang offline');
        $this->get('/')->assertOk()->assertSee('rel="manifest"', false);
    }
}
