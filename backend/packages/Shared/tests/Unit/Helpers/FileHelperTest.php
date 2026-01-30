<?php

declare(strict_types=1);

namespace Omersia\Shared\Tests\Unit\Helpers;

use Omersia\Shared\Helpers\FileHelper;
use Tests\TestCase;

class FileHelperTest extends TestCase
{
    public function it_sanitizes_simple_filename(): void
    {
        $result = FileHelper::sanitizeFilename('test.jpg');

        $this->assertStringStartsWith('test-', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_converts_filename_to_slug(): void
    {
        $result = FileHelper::sanitizeFilename('My Test File.jpg');

        $this->assertStringContainsString('my-test-file', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_removes_special_characters(): void
    {
        $result = FileHelper::sanitizeFilename('test@#$%file!.jpg');

        $this->assertStringContainsString('test-at-file', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_adds_random_identifier(): void
    {
        $result1 = FileHelper::sanitizeFilename('test.jpg');
        $result2 = FileHelper::sanitizeFilename('test.jpg');

        $this->assertNotEquals($result1, $result2);
        $this->assertMatchesRegularExpression('/test-[a-zA-Z0-9]{8}\.jpg/', $result1);
    }

    public function it_normalizes_extension_to_lowercase(): void
    {
        $result = FileHelper::sanitizeFilename('test.JPG');

        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_preserves_allowed_image_extensions(): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        foreach ($extensions as $ext) {
            $result = FileHelper::sanitizeFilename("test.{$ext}");
            $this->assertStringEndsWith(".{$ext}", $result);
        }
    }

    public function it_preserves_pdf_extension(): void
    {
        $result = FileHelper::sanitizeFilename('document.pdf');

        $this->assertStringEndsWith('.pdf', $result);
    }

    public function it_replaces_disallowed_extensions_with_bin(): void
    {
        $result = FileHelper::sanitizeFilename('malicious.exe');

        $this->assertStringEndsWith('.bin', $result);
    }

    public function it_handles_file_without_extension(): void
    {
        $result = FileHelper::sanitizeFilename('filename');

        $this->assertStringEndsWith('.bin', $result);
    }

    public function it_limits_filename_length(): void
    {
        $longName = str_repeat('a', 200).'.jpg';
        $result = FileHelper::sanitizeFilename($longName);

        $nameWithoutExtension = pathinfo($result, PATHINFO_FILENAME);

        // Should be limited + random 8 chars
        $this->assertLessThanOrEqual(109, strlen($nameWithoutExtension));
    }

    public function it_handles_multiple_dots_in_filename(): void
    {
        $result = FileHelper::sanitizeFilename('my.file.name.jpg');

        $this->assertStringContainsString('my-file-name', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_handles_unicode_characters(): void
    {
        $result = FileHelper::sanitizeFilename('café-photo.jpg');

        $this->assertStringContainsString('cafe-photo', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_handles_spaces_and_underscores(): void
    {
        $result = FileHelper::sanitizeFilename('my_test file.jpg');

        $this->assertStringContainsString('my-test-file', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }

    public function it_handles_consecutive_special_characters(): void
    {
        $result = FileHelper::sanitizeFilename('test---file___name.jpg');

        $this->assertStringContainsString('test-file-name', $result);
        $this->assertStringEndsWith('.jpg', $result);
    }
}
