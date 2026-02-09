<?php

declare(strict_types=1);

namespace Omersia\Core\Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Admin user',
        ]);
        $user->roles()->attach($role);
        $user->refresh();

        return $user;
    }

    public function it_shows_create_form_when_no_shop_exists(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.shops.create'));

        $response->assertOk();
        $response->assertViewIs('admin::shops.create');
    }

    public function it_redirects_to_dashboard_when_shop_already_exists(): void
    {
        $user = $this->createAdminUser();
        Shop::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.shops.create'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function it_can_store_new_shop(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'My Shop',
            'code' => 'my-shop',
            'domain' => 'myshop.com',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success', 'Boutique créée avec succès.');

        $this->assertDatabaseHas('shops', [
            'name' => 'My Shop',
            'code' => 'my-shop',
            'default_locale' => 'fr',
        ]);
    }

    public function it_creates_primary_domain_when_storing_shop(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'My Shop',
            'code' => 'my-shop',
            'domain' => 'myshop.com',
        ]);

        $shop = Shop::where('code', 'my-shop')->first();

        $this->assertDatabaseHas('shop_domains', [
            'shop_id' => $shop->id,
            'domain' => 'myshop.com',
            'is_primary' => true,
        ]);
    }

    public function it_validates_required_fields(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), []);

        $response->assertSessionHasErrors(['name', 'code', 'domain']);
    }

    public function it_validates_name_is_string(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 123,
            'code' => 'test',
            'domain' => 'test.com',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function it_validates_name_max_length(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => str_repeat('a', 256),
            'code' => 'test',
            'domain' => 'test.com',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function it_validates_code_is_alpha_dash(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Test Shop',
            'code' => 'invalid code!',
            'domain' => 'test.com',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function it_accepts_valid_alpha_dash_code(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Test Shop',
            'code' => 'valid-code_123',
            'domain' => 'test.com',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseHas('shops', ['code' => 'valid-code_123']);
    }

    public function it_validates_code_max_length(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Test',
            'code' => str_repeat('a', 51),
            'domain' => 'test.com',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function it_validates_domain_is_required(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Test',
            'code' => 'test',
        ]);

        $response->assertSessionHasErrors(['domain']);
    }

    public function it_validates_domain_max_length(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Test',
            'code' => 'test',
            'domain' => str_repeat('a', 256).'.com',
        ]);

        $response->assertSessionHasErrors(['domain']);
    }

    public function it_prevents_creating_second_shop(): void
    {
        $user = $this->createAdminUser();
        Shop::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Second Shop',
            'code' => 'second-shop',
            'domain' => 'second.com',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertEquals(1, Shop::count());
    }

    public function it_sets_default_locale_to_french(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->post(route('admin.shops.store'), [
            'name' => 'Test Shop',
            'code' => 'test',
            'domain' => 'test.com',
        ]);

        $shop = Shop::first();
        $this->assertEquals('fr', $shop->default_locale);
    }
}
