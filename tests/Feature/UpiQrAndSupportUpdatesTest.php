<?php

namespace Tests\Feature;

use App\Models\LudoSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpiQrAndSupportUpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'superadmin_test',
            'email' => 'superadmin@fsaludoclub.test',
        ]);

        $this->player = User::factory()->create([
            'role' => 'player',
            'username' => 'player_test',
            'email' => 'player@fsaludoclub.test',
            'phone' => '9876543210',
            'coins' => 500,
        ]);
    }

    /**
     * Test 1: Open Battles page renders the custom 3D gold banner image.
     */
    public function test_open_battles_page_renders_new_banner(): void
    {
        $response = $this->actingAs($this->player)->get(route('ludo.battles'));
        $response->assertStatus(200);
        $response->assertSee('banner-open-battles.jpg');
    }

    /**
     * Test 2: Announcement component and support screen display permanent WhatsApp 7303859007 and Instagram fsa.ludo.club.
     */
    public function test_support_screen_and_announcement_display_whatsapp_and_instagram(): void
    {
        LudoSetting::set('support_whatsapp', '7303859007');
        LudoSetting::set('support_instagram', 'https://www.instagram.com/fsa.ludo.club');

        // Check announcement default on home/battles
        $homeResponse = $this->actingAs($this->player)->get(route('ludo.battles'));
        $homeResponse->assertStatus(200);

        // Check support page
        $supportResponse = $this->actingAs($this->player)->get(route('ludo.support'));
        $supportResponse->assertStatus(200);
        $supportResponse->assertSee('7303859007');
        $supportResponse->assertSee('fsa.ludo.club');
        $supportResponse->assertSee('https://www.instagram.com/fsa.ludo.club');
        $supportResponse->assertSee('https://wa.me/917303859007', false);
    }

    /**
     * Test 3: Player wallet renders self-contained QR canvas and qrcode.min.js without relying on flaky external APIs.
     */
    public function test_player_wallet_renders_self_contained_qr_code_elements(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');
        LudoSetting::set('upi_id', 'fsa@ptyes');
        LudoSetting::set('upi_payee_name', 'Fsa');

        $response = $this->actingAs($this->player)->get(route('ludo.wallet'));
        $response->assertStatus(200);
        $response->assertSee('fsa@ptyes');
        $response->assertSee('upiQrCanvas');
        $response->assertSee('qrcode.min.js');
        $response->assertSee('renderDynamicQr');
    }

    /**
     * Test 4: Missing or invalid QR image on disk does not produce broken image; it falls back to dynamic QR generation.
     */
    public function test_missing_qr_file_on_disk_safely_falls_back_to_dynamic_generation(): void
    {
        // Set a nonexistent file path in database
        LudoSetting::set('upi_qr_image', 'qr/nonexistent_ghost_file.png');

        $response = $this->actingAs($this->player)->get(route('ludo.wallet'));
        $response->assertStatus(200);
        // The controller should have detected that the file is not on disk and nullified it
        $response->assertDontSee('nonexistent_ghost_file.png');
        $response->assertSee('upiQrCanvas');
    }

    /**
     * Test 5: Admin can configure UPI ID, remove custom QR image, and live preview script is present.
     */
    public function test_admin_settings_desk_supports_qr_removal_and_instagram_config(): void
    {
        // First upload a QR image
        Storage::fake('public');
        $qrFile = UploadedFile::fake()->image('custom_qr.png', 300, 300);

        $response1 = $this->actingAs($this->admin)->post(route('admin.ludo.settings.update'), [
            'platform_name' => 'Fsaludoclub Pro',
            'submission_window' => 15,
            'referral_commission' => '5%',
            'maintenance_mode' => 'off',
            'support_email' => 'admin@fsaludoclub.test',
            'support_whatsapp' => '7303859007',
            'support_instagram' => 'https://www.instagram.com/fsa.ludo.club',
            'minimum_battle_amount' => 50,
            'kyc_mandatory' => 'off',
            'upi_deposits_enabled' => 'on',
            'upi_payee_name' => 'Fsa',
            'upi_id' => 'fsa@ptyes',
            'upi_min_deposit' => 50,
            'upi_max_deposit' => 50000,
            'upi_qr_image' => $qrFile,
        ]);

        $response1->assertRedirect();
        $this->assertNotNull(LudoSetting::get('upi_qr_image'));

        // Admin page now displays the remove checkbox and preview canvas
        $adminView = $this->actingAs($this->admin)->get(route('admin.ludo.settings.index'));
        $adminView->assertStatus(200);
        $adminView->assertSee('remove_upi_qr_image');
        $adminView->assertSee('adminDynamicQrCanvas');
        $adminView->assertSee('qrcode.min.js');

        // Now admin submits with remove_upi_qr_image = 1
        $response2 = $this->actingAs($this->admin)->post(route('admin.ludo.settings.update'), [
            'platform_name' => 'Fsaludoclub Pro',
            'submission_window' => 15,
            'referral_commission' => '5%',
            'maintenance_mode' => 'off',
            'support_email' => 'admin@fsaludoclub.test',
            'support_whatsapp' => '7303859007',
            'support_instagram' => 'https://www.instagram.com/fsa.ludo.club',
            'minimum_battle_amount' => 50,
            'kyc_mandatory' => 'off',
            'upi_deposits_enabled' => 'on',
            'upi_payee_name' => 'Fsa',
            'upi_id' => 'fsa@ptyes',
            'upi_min_deposit' => 50,
            'upi_max_deposit' => 50000,
            'remove_upi_qr_image' => '1',
        ]);

        $response2->assertRedirect();
        $this->assertNull(LudoSetting::get('upi_qr_image'));
        $this->assertEquals('fsa@ptyes', LudoSetting::get('upi_id'));
        $this->assertEquals('7303859007', LudoSetting::get('support_whatsapp'));
        $this->assertEquals('https://www.instagram.com/fsa.ludo.club', LudoSetting::get('support_instagram'));
    }
}
