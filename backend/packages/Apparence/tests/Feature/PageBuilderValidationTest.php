<?php

declare(strict_types=1);

namespace Omersia\Apparence\Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Apparence\Models\EcommercePage;
use Omersia\Core\Models\Shop;
use Tests\TestCase;

class PageBuilderValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private Shop $shop;

    private EcommercePage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->createAdminUser();
        $this->shop = Shop::factory()->create();
        $this->page = EcommercePage::factory()->create([
            'shop_id' => $this->shop->id,
            'type' => 'home',
        ]);
    }

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

    public function it_accepts_valid_page_builder_json_with_numeric_widths(): void
    {
        $validJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 50,
                            'mobileWidth' => 100,
                            'widgets' => [],
                        ],
                        [
                            'id' => 'col-2',
                            'desktopWidth' => 50,
                            'mobileWidth' => 100,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function it_accepts_edge_case_widths_zero_and_hundred(): void
    {
        $validJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 0,
                            'mobileWidth' => 100,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function it_accepts_columns_without_width_properties(): void
    {
        $validJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function it_accepts_native_content_structure_for_category_pages(): void
    {
        $validJson = json_encode([
            'beforeNative' => [
                'sections' => [
                    [
                        'id' => 'section-1',
                        'columns' => [
                            [
                                'id' => 'col-1',
                                'desktopWidth' => 100,
                                'widgets' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'afterNative' => [
                'sections' => [],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function it_rejects_negative_desktop_width(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => -10,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_rejects_desktop_width_over_hundred(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 150,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_rejects_negative_mobile_width(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'mobileWidth' => -5,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_rejects_mobile_width_over_hundred(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'mobileWidth' => 200,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_rejects_string_injection_in_desktop_width(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => '50}; script{alert(1)}',
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_rejects_object_in_width_field(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => ['value' => 50],
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_validates_nested_columns_recursively(): void
    {
        $invalidJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 50,
                            'columns' => [
                                [
                                    'id' => 'nested-col-1',
                                    'desktopWidth' => 150, // Invalid
                                    'widgets' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_accepts_valid_nested_columns(): void
    {
        $validJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 100,
                            'columns' => [
                                [
                                    'id' => 'nested-col-1',
                                    'desktopWidth' => 50,
                                    'mobileWidth' => 100,
                                    'widgets' => [],
                                ],
                                [
                                    'id' => 'nested-col-2',
                                    'desktopWidth' => 50,
                                    'mobileWidth' => 100,
                                    'widgets' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function it_rejects_missing_sections_array(): void
    {
        $invalidJson = json_encode([
            'columns' => [
                [
                    'id' => 'col-1',
                    'desktopWidth' => 50,
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_rejects_invalid_json_string(): void
    {
        $invalidJson = 'not valid json {]';

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $invalidJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_accepts_decimal_widths_within_range(): void
    {
        $validJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 33.33,
                            'mobileWidth' => 66.67,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function it_rejects_xss_attempt_via_css_injection(): void
    {
        $xssJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => '50%}body{background:url(javascript:alert(1))}',
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $xssJson,
                'locale' => 'fr',
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHasErrors('content_json');
    }

    public function it_validates_multiple_sections_with_mixed_widths(): void
    {
        $validJson = json_encode([
            'sections' => [
                [
                    'id' => 'section-1',
                    'columns' => [
                        [
                            'id' => 'col-1',
                            'desktopWidth' => 50,
                            'mobileWidth' => 100,
                            'widgets' => [],
                        ],
                    ],
                ],
                [
                    'id' => 'section-2',
                    'columns' => [
                        [
                            'id' => 'col-2',
                            'desktopWidth' => 25,
                            'widgets' => [],
                        ],
                        [
                            'id' => 'col-3',
                            'desktopWidth' => 75,
                            'widgets' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->post(
            route('admin.apparence.ecommerce-pages.builder.update', ['page' => $this->page->id]),
            [
                'content_json' => $validJson,
                'locale' => 'fr',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }
}
