<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_pwa_static_files_exist(): void
    {
        $this->assertFileExists(public_path('manifest.json'));
        $this->assertFileExists(public_path('service-worker.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
        $this->assertFileExists(public_path('icons/icon-maskable-512.png'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
    }

    public function test_home_page_exposes_pwa_manifest_and_icons(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.json', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('/icons/apple-touch-icon.png', false);
    }

    public function test_login_page_keeps_pwa_meta_without_breaking_breeze(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/icons/favicon-32.png', false);
    }
}
