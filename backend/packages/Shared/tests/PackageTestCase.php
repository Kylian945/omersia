<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base test case for all package tests
 * Extends Tests\TestCase to inherit database safety checks (SQLite in-memory only)
 */
abstract class PackageTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup commun pour les tests de packages
    }

    /**
     * Créer un shop de test
     */
    protected function createTestShop(): \Omersia\Core\Models\Shop
    {
        return \Omersia\Core\Models\Shop::factory()->create([
            'name' => 'Test Shop',
            'code' => 'test-shop',
            'default_locale' => 'fr',
            'default_currency' => 'EUR',
        ]);
    }

    /**
     * Créer un customer de test
     */
    protected function createTestCustomer(): \Omersia\Customer\Models\Customer
    {
        return \Omersia\Customer\Models\Customer::factory()->create();
    }
}
