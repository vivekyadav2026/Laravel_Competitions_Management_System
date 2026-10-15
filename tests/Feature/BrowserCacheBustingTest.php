<?php

namespace Tests\Feature;

use App\Helpers\CacheBuster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BrowserCacheBustingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@fsaludoclub.com'],
            [
                'name' => 'Fsaludoclub Admin',
                'username' => 'fsaludoclub_superadmin',
                'password' => bcrypt('Admin@12345'),
                'role' => 'superadmin',
                'coins' => 10000,
            ]
        );

        $suffix = uniqid();
        $this->player = User::create([
            'name' => 'Test Player',
            'username' => 'test_player_' . $suffix,
            'email' => 'test_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);
    }

    public function test_player_html_pages_contain_strict_no_cache_response_headers()
    {
        $response = $this->actingAs($this->player)->get('/battles');

        $response->assertStatus(200);

        // Verify HTTP Headers
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('Sun, 02 Jan 1990 00:00:00 GMT', $response->headers->get('Expires'));

        // Verify HTML Head Meta Tags
        $response->assertSee('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">', false);
        $response->assertSee('<meta http-equiv="Pragma" content="no-cache">', false);
        $response->assertSee('<meta http-equiv="Expires" content="0">', false);
    }

    public function test_admin_pages_contain_strict_no_cache_response_headers()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ludo.matches.index'));

        $response->assertStatus(200);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));

        $response->assertSee('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">', false);
        $response->assertSee('<meta http-equiv="Pragma" content="no-cache">', false);
    }

    public function test_ajax_json_endpoints_return_no_cache_headers()
    {
        $response = $this->actingAs($this->player)->getJson(route('ludo.battles.syncState'));

        $response->assertStatus(200);

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function test_versioned_asset_helper_appends_timestamp_query_string()
    {
        $themeUrl = versioned_asset('css/ludo-theme.css');
        $this->assertStringContainsString('?v=', $themeUrl);

        $fullPath = public_path('css/ludo-theme.css');
        if (file_exists($fullPath)) {
            $expectedVersion = filemtime($fullPath);
            $this->assertStringEndsWith('?v=' . $expectedVersion, $themeUrl);
        }

        $jsUrl = versioned_asset('js/ludo-app.js');
        $this->assertStringContainsString('?v=', $jsUrl);
    }

    public function test_sign_in_page_has_anti_cache_meta_tags_and_versioned_css()
    {
        $response = $this->get('/showlogin');

        $response->assertStatus(200);
        $response->assertSee('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">', false);
        $response->assertSee('ludo-theme.css?v=', false);
    }
}
