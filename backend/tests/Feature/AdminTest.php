<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\HomepageBlock;
use App\Models\Offer;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        config(['anabe.admin_emails' => ['boss@example.com']]);
        Sanctum::actingAs(User::factory()->create(['email' => 'boss@example.com']));
    }

    private function block(string $type, int $position): HomepageBlock
    {
        return HomepageBlock::create(['type' => $type, 'title' => ucfirst($type), 'position' => $position, 'config' => []]);
    }

    public function test_only_admins_get_in(): void
    {
        $this->getJson('/api/admin/homepage-blocks')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/admin/homepage-blocks')->assertForbidden();

        $this->actingAsAdmin();
        $this->getJson('/api/admin/homepage-blocks')->assertOk()->assertJsonFragment(['types' => ['banner', 'bestsellers', 'deals', 'new_releases', 'platform_row']]);
    }

    public function test_a_new_platform_row_is_one_request_and_appears_on_the_homepage(): void
    {
        $this->actingAsAdmin();
        $this->block('banner', 1);

        $this->postJson('/api/admin/homepage-blocks', ['type' => 'platform_row', 'title' => 'PlayStation hits', 'config' => ['platform' => 'PlayStation']])
            ->assertCreated()
            ->assertJsonPath('data.position', 2);

        $this->getJson('/api/homepage')->assertJsonPath('data.1.title', 'PlayStation hits');
    }

    public function test_blocks_are_validated(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/homepage-blocks', ['type' => 'carousel_3d', 'title' => 'X'])->assertJsonValidationErrors(['type']);
        $this->postJson('/api/admin/homepage-blocks', ['type' => 'platform_row', 'title' => 'X'])->assertJsonValidationErrors(['config.platform']);
        $this->postJson('/api/admin/homepage-blocks', ['type' => 'platform_row', 'title' => 'X', 'config' => ['platform' => 'Dreamcast']])->assertJsonValidationErrors(['config.platform']);
    }

    public function test_blocks_can_be_switched_off_and_reordered(): void
    {
        $this->actingAsAdmin();
        $banner = $this->block('banner', 1);
        $deals = $this->block('deals', 2);
        $newReleases = $this->block('new_releases', 3);

        $this->patchJson("/api/admin/homepage-blocks/{$deals->id}", ['enabled' => false])->assertOk();
        $this->postJson('/api/admin/homepage-blocks/order', ['ids' => [$newReleases->id, $deals->id, $banner->id]])->assertNoContent();

        $this->getJson('/api/homepage')->assertJsonPath('data.*.type', ['new_releases', 'banner']);
        // A partial order is refused: every block must be placed.
        $this->postJson('/api/admin/homepage-blocks/order', ['ids' => [$banner->id]])->assertJsonValidationErrors(['ids']);
    }

    public function test_a_banner_can_be_switched_off(): void
    {
        $this->actingAsAdmin();
        $banner = Banner::create(['title' => 'Sale', 'subtitle' => '', 'image_path' => 'banners/a.svg', 'link_url' => '/store', 'position' => 1]);
        $block = $this->block('banner', 1);
        $this->getJson("/api/homepage/blocks/{$block->id}")->assertJsonCount(1, 'data');

        $this->patchJson("/api/admin/banners/{$banner->id}", ['active' => false])->assertOk();
        $this->patchJson("/api/admin/banners/{$banner->id}", ['link_url' => 'https://evil.example'])->assertJsonValidationErrors(['link_url']);

        // Visible immediately, despite the block's cache.
        $this->getJson("/api/homepage/blocks/{$block->id}")->assertJsonCount(0, 'data');
    }

    public function test_uploaded_keys_become_encrypted_stock(): void
    {
        $this->actingAsAdmin();
        $offer = Offer::factory()->create();

        $this->postJson("/api/admin/offers/{$offer->id}/keys", ['codes' => ['AAAAA-11111-BBBBB', 'CCCCC-22222-DDDDD']])
            ->assertCreated()
            ->assertJsonPath('data.stock', 2);

        $this->assertSame(['AAAAA-11111-BBBBB', 'CCCCC-22222-DDDDD'], ProductKey::orderBy('id')->get()->pluck('code')->all());
        $this->assertStringNotContainsString('AAAAA', DB::table('product_keys')->value('code'));
        $this->postJson("/api/admin/offers/{$offer->id}/keys", ['codes' => ['SAME', 'SAME']])->assertJsonValidationErrors(['codes.0']);
    }
}
