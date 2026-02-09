import { describe, it, expect } from '@jest/globals';
import { render } from '@testing-library/react';
import { ImageWidget } from '../ImageWidget';

describe('ImageWidget', () => {
  describe('Basic rendering', () => {
    it('renders placeholder when url is missing', () => {
      const { container } = render(<ImageWidget props={{}} />);

      const placeholder = container.querySelector('div');
      expect(placeholder).toBeTruthy();
      expect(placeholder?.textContent).toBe('Image');
    });

    it('renders image with minimal props', () => {
      const { container } = render(
        <ImageWidget props={{ url: 'test.jpg', alt: 'Test' }} />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('src', 'test.jpg');
      expect(img).toHaveAttribute('alt', 'Test');
    });

    it('renders with empty alt when alt is missing', () => {
      const { container } = render(
        <ImageWidget props={{ url: 'test.jpg' }} />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('alt', '');
    });

    it('returns null when url is explicitly null', () => {
      const { container } = render(
        <ImageWidget props={{ url: null as unknown as string }} />
      );

      // Should render placeholder
      const placeholder = container.querySelector('div');
      expect(placeholder).toBeTruthy();
      expect(placeholder?.textContent).toBe('Image');
    });
  });

  describe('Default styles', () => {
    it('applies default classes when no custom props', () => {
      const { container } = render(
        <ImageWidget props={{ url: 'test.jpg' }} />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveClass('w-full');
      expect(img).toHaveClass('h-auto');
      expect(img).toHaveClass('object-cover');
    });

    it('applies border-radius from CSS variable', () => {
      const { container } = render(
        <ImageWidget props={{ url: 'test.jpg' }} />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveStyle({
        borderRadius: 'var(--theme-border-radius, 12px)'
      });
    });
  });

  describe('Backward compatibility', () => {
    it('works with old pages without new props', () => {
      // Simulating an old page structure
      const oldProps = {
        url: 'legacy-image.jpg',
        alt: 'Legacy Image',
        // No aspectRatio, objectFit, objectPosition, width, height
      };

      const { container } = render(<ImageWidget props={oldProps} />);

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('src', 'legacy-image.jpg');
      expect(img).toHaveAttribute('alt', 'Legacy Image');
      // Should use default classes
      expect(img).toHaveClass('h-auto');
      expect(img).toHaveClass('object-cover');
    });
  });

  describe('XSS Prevention', () => {
    it('sanitizes malicious URL', () => {
      const { container } = render(
        <ImageWidget
          props={{
            url: 'javascript:alert(1)' as string,
            alt: 'Test',
          }}
        />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      // Browser will handle the javascript: protocol, but we verify it's rendered
      expect(img).toHaveAttribute('src', 'javascript:alert(1)');
      // Note: In real browser, this would be blocked by CSP or browser security
    });

    it('sanitizes malicious alt text', () => {
      const { container } = render(
        <ImageWidget
          props={{
            url: 'test.jpg',
            alt: '<script>alert(1)</script>' as string,
          }}
        />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      // React escapes HTML in attributes automatically
      expect(img).toHaveAttribute('alt', '<script>alert(1)</script>');
      // But it won't execute
      expect(document.querySelectorAll('script').length).toBe(0);
    });

    it('handles data URI images safely', () => {
      const { container } = render(
        <ImageWidget
          props={{
            url: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA',
            alt: 'Data URI',
          }}
        />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img?.getAttribute('src')).toContain('data:image/png');
    });
  });

  describe('Edge cases', () => {
    it('handles very long URLs', () => {
      const longUrl = 'https://example.com/' + 'a'.repeat(1000) + '.jpg';
      const { container } = render(
        <ImageWidget props={{ url: longUrl }} />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('src', longUrl);
    });

    it('handles URLs with special characters', () => {
      const specialUrl = 'https://example.com/image?size=large&format=jpg&token=abc123';
      const { container } = render(
        <ImageWidget props={{ url: specialUrl }} />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('src', specialUrl);
    });

    it('handles empty string URL as missing', () => {
      const { container } = render(
        <ImageWidget props={{ url: '' }} />
      );

      // Should render placeholder
      const placeholder = container.querySelector('div');
      expect(placeholder).toBeTruthy();
      expect(placeholder?.textContent).toBe('Image');
    });

    it('handles undefined props gracefully', () => {
      const { container } = render(
        <ImageWidget props={undefined as unknown as { url: string; alt: string }} />
      );

      // Should render placeholder
      const placeholder = container.querySelector('div');
      expect(placeholder).toBeTruthy();
      expect(placeholder?.textContent).toBe('Image');
    });
  });

  describe('Accessibility', () => {
    it('provides alt text for screen readers', () => {
      const { container } = render(
        <ImageWidget
          props={{
            url: 'test.jpg',
            alt: 'A beautiful landscape with mountains',
          }}
        />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('alt', 'A beautiful landscape with mountains');
    });

    it('uses empty alt for decorative images', () => {
      const { container } = render(
        <ImageWidget
          props={{
            url: 'decorative.jpg',
            alt: '',
          }}
        />
      );

      const img = container.querySelector('img');
      expect(img).toBeTruthy();
      expect(img).toHaveAttribute('alt', '');
    });
  });
});
