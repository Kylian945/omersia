<?php

declare(strict_types=1);

namespace Omersia\Api\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Catalog\Models\Order;
use Omersia\Core\Models\Shop;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use Omersia\Sales\Models\DiscountUsage;
use Tests\TestCase;
use Tests\WithApiKey;

class DiscountUsageLimitsTest extends TestCase
{
    use RefreshDatabase;
    use WithApiKey;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpApiKey();
        $this->shop = Shop::factory()->create();
        config(['app.default_shop_id' => $this->shop->id]);
    }

    /** @test */
    public function it_rejects_discount_when_global_usage_limit_reached(): void
    {
        // Créer un discount avec une limite de 2 utilisations
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'LIMIT2',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 2,
            'is_active' => true,
        ]);

        // Créer 2 usages existants (limite atteinte)
        DiscountUsage::factory()->count(2)->create([
            'discount_id' => $discount->id,
            'usage_count' => 1,
        ]);

        // Tenter d'appliquer le discount
        $response = $this->postJson('/api/v1/cart/apply-discount', [
            'code' => 'LIMIT2',
            'items' => [
                ['id' => 1, 'name' => 'Produit', 'price' => 100.0, 'qty' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertStatus(422);
        $response->assertJson([
            'ok' => false,
            'message' => 'Ce code promo a atteint sa limite d\'utilisation.',
        ]);
    }

    /** @test */
    public function it_accepts_discount_when_global_usage_limit_not_reached(): void
    {
        // Créer un discount avec une limite de 5 utilisations
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'LIMIT5',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 5,
            'is_active' => true,
        ]);

        // Créer 3 usages existants (limite non atteinte)
        DiscountUsage::factory()->count(3)->create([
            'discount_id' => $discount->id,
            'usage_count' => 1,
        ]);

        // Tenter d'appliquer le discount
        $response = $this->postJson('/api/v1/cart/apply-discount', [
            'code' => 'LIMIT5',
            'items' => [
                ['id' => 1, 'name' => 'Produit', 'price' => 100.0, 'qty' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /** @test */
    public function it_rejects_discount_when_per_customer_usage_limit_reached(): void
    {
        $customer = Customer::factory()->create();

        // Créer un discount avec limite par client de 1
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'ONCE',
            'type' => 'order',
            'value_type' => 'fixed_amount',
            'value' => 20,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        // Le client a déjà utilisé ce code
        DiscountUsage::factory()->create([
            'discount_id' => $discount->id,
            'customer_id' => $customer->id,
            'usage_count' => 1,
        ]);

        // Tenter d'appliquer le discount en étant authentifié
        $response = $this->postJson('/api/v1/cart/apply-discount', [
            'code' => 'ONCE',
            'items' => [
                ['id' => 1, 'name' => 'Produit', 'price' => 100.0, 'qty' => 1],
            ],
        ], $this->authenticatedHeaders($customer));

        $response->assertStatus(422);
        $response->assertJson([
            'ok' => false,
            'message' => 'Vous avez déjà utilisé ce code promo le nombre maximum de fois autorisé.',
        ]);
    }

    /** @test */
    public function it_accepts_discount_for_different_customer_when_per_customer_limit_reached_for_another(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        // Créer un discount avec limite par client de 1
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'ONCEPERUSER',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 15,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        // Customer 1 a déjà utilisé ce code
        DiscountUsage::factory()->create([
            'discount_id' => $discount->id,
            'customer_id' => $customer1->id,
            'usage_count' => 1,
        ]);

        // Customer 2 devrait pouvoir l'utiliser
        $response = $this->postJson('/api/v1/cart/apply-discount', [
            'code' => 'ONCEPERUSER',
            'items' => [
                ['id' => 1, 'name' => 'Produit', 'price' => 100.0, 'qty' => 1],
            ],
        ], $this->authenticatedHeaders($customer2));

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /** @test */
    public function it_deactivates_discount_when_global_limit_reached_after_order(): void
    {
        // Créer un discount avec une limite de 1 utilisation
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'LASTUSE',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        $this->assertTrue($discount->is_active);

        // Créer une commande et enregistrer l'usage
        $order = Order::factory()->create([
            'customer_id' => null,
            'applied_discounts' => [$discount->id],
        ]);

        $order->recordDiscountUsage([$discount->id]);

        // Le discount devrait être désactivé
        $discount->refresh();
        $this->assertFalse($discount->is_active);
    }

    /** @test */
    public function it_does_not_deactivate_discount_when_global_limit_not_reached(): void
    {
        // Créer un discount avec une limite de 3 utilisations
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'MULTI',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 3,
            'is_active' => true,
        ]);

        // Première utilisation
        $order1 = Order::factory()->create(['applied_discounts' => [$discount->id]]);
        $order1->recordDiscountUsage([$discount->id]);

        $discount->refresh();
        $this->assertTrue($discount->is_active);

        // Deuxième utilisation
        $order2 = Order::factory()->create(['applied_discounts' => [$discount->id]]);
        $order2->recordDiscountUsage([$discount->id]);

        $discount->refresh();
        $this->assertTrue($discount->is_active);

        // Troisième utilisation (limite atteinte)
        $order3 = Order::factory()->create(['applied_discounts' => [$discount->id]]);
        $order3->recordDiscountUsage([$discount->id]);

        $discount->refresh();
        $this->assertFalse($discount->is_active);
    }

    /** @test */
    public function it_filters_out_automatic_discounts_that_reached_usage_limit(): void
    {
        // Créer un discount automatique avec limite atteinte
        $discountExhausted = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'automatic',
            'name' => 'Promo épuisée',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 1,
            'is_active' => true,
        ]);

        DiscountUsage::factory()->create([
            'discount_id' => $discountExhausted->id,
            'usage_count' => 1,
        ]);

        // Créer un discount automatique valide
        $discountValid = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'automatic',
            'name' => 'Promo valide',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 5,
            'usage_limit' => 100,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/cart/apply-automatic-discounts', [
            'items' => [
                ['id' => 1, 'name' => 'Produit', 'price' => 100.0, 'qty' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        // Vérifier que seul le discount valide est appliqué
        $promotions = $response->json('promotions');
        $this->assertCount(1, $promotions);
        $this->assertEquals('Promo valide', $promotions[0]['label']);
    }

    /** @test */
    public function it_counts_usage_count_field_correctly(): void
    {
        // Créer un discount avec limite de 5
        $discount = Discount::factory()->create([
            'shop_id' => $this->shop->id,
            'method' => 'code',
            'code' => 'BULK',
            'type' => 'order',
            'value_type' => 'percentage',
            'value' => 10,
            'usage_limit' => 5,
            'is_active' => true,
        ]);

        // Créer un usage avec usage_count de 3 (par exemple, commande de 3 articles)
        DiscountUsage::factory()->create([
            'discount_id' => $discount->id,
            'usage_count' => 3,
        ]);

        // Créer un autre usage avec usage_count de 2
        DiscountUsage::factory()->create([
            'discount_id' => $discount->id,
            'usage_count' => 2,
        ]);

        // Total = 5, limite atteinte
        $response = $this->postJson('/api/v1/cart/apply-discount', [
            'code' => 'BULK',
            'items' => [
                ['id' => 1, 'name' => 'Produit', 'price' => 100.0, 'qty' => 1],
            ],
        ], $this->apiHeaders());

        $response->assertStatus(422);
        $response->assertJson([
            'ok' => false,
            'message' => 'Ce code promo a atteint sa limite d\'utilisation.',
        ]);
    }
}
