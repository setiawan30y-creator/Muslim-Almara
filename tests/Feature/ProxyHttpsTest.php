<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Akses HP lewat tunnel HTTPS: URL aset & manifest harus https — @2026-Alifiandra
class ProxyHttpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_tunnel_forwarded_https_produces_https_urls(): void
    {
        $this->seedAlmara();
        $headers = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'contoh-tunnel.trycloudflare.com', 'X-Forwarded-Port' => '443'];
        $res = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/', $headers)->assertOk();
        $this->assertStringContainsString('https://contoh-tunnel.trycloudflare.com/css/app.css', $res->getContent());
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/manifest.webmanifest', $headers)->assertOk();
    }

    public function test_untrusted_proxy_cannot_change_scheme(): void
    {
        $this->seedAlmara();
        $headers = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'contoh-tunnel.trycloudflare.com', 'X-Forwarded-Port' => '443'];
        $other = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->get('/', $headers)->assertOk();
        $this->assertStringNotContainsString('https://contoh-tunnel.trycloudflare.com', $other->getContent());
    }
}
