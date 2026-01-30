<?php

namespace Omersia\Api\Tests\Unit\Services;

use Omersia\Api\DTO\CartItemDTO;
use Omersia\Api\DTO\DiscountApplicationDTO;
use Omersia\Api\Services\DiscountEvaluationService;
use Omersia\Customer\Models\Customer;
use Omersia\Sales\Models\Discount;
use PHPUnit\Framework\TestCase;

class DiscountEvaluationServiceTest extends TestCase
{
    private DiscountEvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DiscountEvaluationService;
    }

    public function it_evaluates_order_discount_with_percentage()
    {
        // Créer un mock de Discount
        $discount = $this->createMock(Discount::class);
        $discount->method('__get')->willReturnMap([
            ['type', 'order'],
            ['value_type', 'percentage'],
            ['value', 10.0],
            ['customer_selection', 'all'],
            ['product_scope', 'all'],
            ['min_subtotal', null],
            ['min_quantity', null],
        ]);

        // Créer des items
        $items = [
            new CartItemDTO(id: 1, price: 100.0, qty: 2, name: 'Product 1'),
            new CartItemDTO(id: 2, price: 50.0, qty: 1, name: 'Product 2'),
        ];

        // Créer le DTO
        $dto = new DiscountApplicationDTO(
            discount: $discount,
            customer: null,
            items: $items,
            subtotal: 250.0,
            productIds: [1, 2]
        );

        // Évaluer
        $result = $this->service->evaluate($dto);

        // Assertions
        $this->assertTrue($result->ok);
        $this->assertEquals(25.0, $result->orderDiscountAmount); // 10% de 250
        $this->assertEquals(0.0, $result->productDiscountAmount);
        $this->assertEquals(25.0, $result->totalDiscount);
    }

    public function it_fails_when_customer_not_provided_for_group_discount()
    {
        // Créer un mock de CustomerGroup collection
        $customerGroups = $this->createMock(\Illuminate\Database\Eloquent\Collection::class);
        $customerGroups->method('pluck')->willReturn(collect([5, 6]));

        // Créer un mock de Discount qui requiert des groupes spécifiques
        $discount = $this->createMock(Discount::class);
        $discount->method('__get')->willReturnMap([
            ['customer_selection', 'groups'],
            ['customerGroups', $customerGroups],
            ['min_subtotal', null],
            ['min_quantity', null],
        ]);

        // Pas de customer fourni - devrait échouer
        $items = [new CartItemDTO(id: 1, price: 100.0, qty: 1, name: 'Product 1')];

        // Créer le DTO sans customer
        $dto = new DiscountApplicationDTO(
            discount: $discount,
            customer: null, // Aucun customer
            items: $items,
            subtotal: 100.0,
            productIds: [1]
        );

        // Évaluer
        $result = $this->service->evaluate($dto);

        // Assertions
        $this->assertFalse($result->ok);
        $this->assertStringContainsString('réservé à certains clients', $result->message);
    }

    public function it_evaluates_product_discount_with_fixed_amount()
    {
        // Créer un mock de Discount
        $discount = $this->createMock(Discount::class);
        $discount->method('__get')->willReturnMap([
            ['type', 'product'],
            ['value_type', 'fixed_amount'],
            ['value', 10.0],
            ['customer_selection', 'all'],
            ['product_scope', 'all'],
            ['code', 'TEST10'],
            ['min_subtotal', null],
            ['min_quantity', null],
        ]);

        // Créer des items
        $items = [
            new CartItemDTO(id: 1, price: 50.0, qty: 2, name: 'Product 1'), // 100 total
            new CartItemDTO(id: 2, price: 30.0, qty: 1, name: 'Product 2'), // 30 total
        ];

        // Créer le DTO
        $dto = new DiscountApplicationDTO(
            discount: $discount,
            customer: null,
            items: $items,
            subtotal: 130.0,
            productIds: [1, 2]
        );

        // Évaluer
        $result = $this->service->evaluate($dto);

        // Assertions
        $this->assertTrue($result->ok);
        $this->assertEquals(0.0, $result->orderDiscountAmount);
        $this->assertEquals(20.0, $result->productDiscountAmount); // 10 par ligne
        $this->assertEquals(20.0, $result->totalDiscount);
        $this->assertCount(2, $result->lineAdjustments);
    }

    public function it_fails_when_minimum_subtotal_not_reached()
    {
        // Créer un mock de Discount
        $discount = $this->createMock(Discount::class);
        $discount->method('__get')->willReturnMap([
            ['type', 'order'],
            ['customer_selection', 'all'],
            ['product_scope', 'all'],
            ['min_subtotal', 100.0],
            ['min_quantity', null],
        ]);

        // Créer des items
        $items = [new CartItemDTO(id: 1, price: 20.0, qty: 2, name: 'Product 1')]; // 40 total

        // Créer le DTO
        $dto = new DiscountApplicationDTO(
            discount: $discount,
            customer: null,
            items: $items,
            subtotal: 40.0,
            productIds: [1]
        );

        // Évaluer
        $result = $this->service->evaluate($dto);

        // Assertions
        $this->assertFalse($result->ok);
        $this->assertStringContainsString('Montant minimum', $result->message);
    }

    public function it_evaluates_buy_x_get_y_discount()
    {
        // Créer un mock de Discount avec toutes les propriétés nécessaires
        $discount = $this->getMockBuilder(Discount::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Assigner directement les propriétés publiques au mock
        $discount->type = 'buy_x_get_y';
        $discount->buy_quantity = 2;
        $discount->get_quantity = 1;
        $discount->customer_selection = 'all';
        $discount->product_scope = 'all';
        $discount->code = 'BUY2GET1';
        $discount->min_subtotal = null;
        $discount->min_quantity = null;

        // Créer des items (6 items = 2 groupes complets)
        $items = [
            new CartItemDTO(id: 1, price: 30.0, qty: 6, name: 'Product 1'), // 2 groupes complets, 2 offerts
        ];

        // Créer le DTO
        $dto = new DiscountApplicationDTO(
            discount: $discount,
            customer: null,
            items: $items,
            subtotal: 180.0,
            productIds: [1]
        );

        // Évaluer
        $result = $this->service->evaluate($dto);

        // Assertions
        $this->assertTrue($result->ok);
        $this->assertEquals(60.0, $result->productDiscountAmount); // 2 gratuits * 30
        $this->assertCount(1, $result->lineAdjustments);
        $this->assertTrue($result->lineAdjustments[0]['is_gift']);
    }
}
