<?php

declare(strict_types=1);

namespace Omersia\Core\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Omersia\Core\Models\Module;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    public function it_can_create_module(): void
    {
        $module = Module::create([
            'slug' => 'test-module',
            'name' => 'Test Module',
            'version' => '1.0.0',
            'enabled' => true,
            'manifest' => ['key' => 'value'],
        ]);

        $this->assertDatabaseHas('modules', [
            'slug' => 'test-module',
            'name' => 'Test Module',
            'version' => '1.0.0',
            'enabled' => true,
        ]);
    }

    public function it_has_fillable_attributes(): void
    {
        $module = new Module;
        $fillable = $module->getFillable();

        $this->assertContains('slug', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('version', $fillable);
        $this->assertContains('enabled', $fillable);
        $this->assertContains('manifest', $fillable);
    }

    public function it_casts_enabled_to_boolean(): void
    {
        $module = Module::create([
            'slug' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'enabled' => 1,
        ]);

        $this->assertIsBool($module->enabled);
        $this->assertTrue($module->enabled);
    }

    public function it_casts_manifest_to_array(): void
    {
        $module = Module::create([
            'slug' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'enabled' => true,
            'manifest' => ['author' => 'John Doe', 'description' => 'A test module'],
        ]);

        $this->assertIsArray($module->manifest);
        $this->assertEquals('John Doe', $module->manifest['author']);
        $this->assertEquals('A test module', $module->manifest['description']);
    }

    public function it_can_store_complex_manifest(): void
    {
        $manifest = [
            'author' => 'Test Author',
            'description' => 'Test description',
            'dependencies' => ['module-a', 'module-b'],
            'config' => [
                'setting1' => 'value1',
                'setting2' => 'value2',
            ],
        ];

        $module = Module::create([
            'slug' => 'complex-module',
            'name' => 'Complex Module',
            'version' => '2.0.0',
            'enabled' => true,
            'manifest' => $manifest,
        ]);

        $this->assertEquals($manifest, $module->fresh()->manifest);
    }

    public function it_can_be_disabled(): void
    {
        $module = Module::create([
            'slug' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'enabled' => false,
        ]);

        $this->assertFalse($module->enabled);
    }

    public function it_stores_version_string(): void
    {
        $module = Module::create([
            'slug' => 'test',
            'name' => 'Test',
            'version' => '1.2.3',
            'enabled' => true,
        ]);

        $this->assertEquals('1.2.3', $module->version);
    }
}
